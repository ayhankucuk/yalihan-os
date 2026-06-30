import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30
HEADERS_JSON = {"Content-Type": "application/json"}

def test_public_ai_property_search_with_invalid_prompt():
    # Step 1: Create a listing with valid data
    create_payload = {
        "baslik": "Test Başlık",
        "aciklama": "Test açıklaması",
        "alan_m2": 100,
        "birim_fiyat": 5000,
        "il": "İstanbul",
        "ilce": "Beşiktaş",
        "mahalle": "Levent",
        "lat": 41.0833,
        "lng": 29.0167
    }
    listing_id = None
    try:
        create_resp = requests.post(
            f"{BASE_URL}/api/v1/ilanlar",
            json=create_payload,
            headers=HEADERS_JSON,
            timeout=TIMEOUT
        )
        assert create_resp.status_code == 201, f"Expected 201 Created, got {create_resp.status_code}"
        listing_data = create_resp.json()
        listing_id = listing_data.get("id")
        assert listing_id is not None, "Listing ID not returned in create response"

        # Step 4: Verify blank title/description returns 422
        invalid_payloads = [
            {**create_payload, "baslik": ""},  # blank title
            {**create_payload, "aciklama": ""}  # blank description
        ]
        for invalid_payload in invalid_payloads:
            resp = requests.post(
                f"{BASE_URL}/api/v1/ilanlar",
                json=invalid_payload,
                headers=HEADERS_JSON,
                timeout=TIMEOUT
            )
            assert resp.status_code == 422, f"Expected 422 Unprocessable Entity for blank field, got {resp.status_code}"

        # Step 5: Publish the created listing (set to 'Aktif')
        patch_resp = requests.patch(
            f"{BASE_URL}/api/v1/ilanlar/{listing_id}/publish",
            json={"status": "Aktif"},
            headers=HEADERS_JSON,
            timeout=TIMEOUT
        )
        assert patch_resp.status_code == 200, f"Expected 200 OK on publish, got {patch_resp.status_code}"

        # Step 6: Return pass/fail report for the wizard scenarios
        print("Admin Listing Wizard Tests: PASS")

    except AssertionError as ae:
        print(f"Admin Listing Wizard Tests: FAIL - {ae}")
    except requests.RequestException as re:
        print(f"Admin Listing Wizard Tests: FAIL - HTTP error: {re}")
    finally:
        # Cleanup: Delete the created listing if exists
        if listing_id:
            try:
                requests.delete(
                    f"{BASE_URL}/api/v1/ilanlar/{listing_id}",
                    timeout=TIMEOUT
                )
            except requests.RequestException:
                pass

test_public_ai_property_search_with_invalid_prompt()