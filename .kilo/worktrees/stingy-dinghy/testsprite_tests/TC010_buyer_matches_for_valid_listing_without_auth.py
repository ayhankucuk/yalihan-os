import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_buyer_matches_for_valid_listing_without_auth():
    listing_payload = {
        "baslik": "Test Listing Title",
        "aciklama": "Test Listing Description",
        "alan_m2": 120,
        "birim_fiyat": 3500,
        "il": "Istanbul",
        "ilce": "Kadikoy",
        "mahalle": "Moda",
        "lat": 40.9876,
        "lng": 29.0203
    }

    listing_id = None

    try:
        # Step 1: Create a listing
        create_resp = requests.post(
            f"{BASE_URL}/api/v1/ilanlar",
            json=listing_payload,
            timeout=TIMEOUT
        )
        assert create_resp.status_code == 201, f"Expected 201 Created, got {create_resp.status_code}"
        create_data = create_resp.json()
        listing_id = create_data.get("id")
        assert listing_id is not None, "Created listing ID is missing"

        # Step 4: Verify blank title/description returns 422
        invalid_payloads = [
            dict(listing_payload, baslik=""),
            dict(listing_payload, aciklama="")
        ]
        for inv_payload in invalid_payloads:
            inv_resp = requests.post(
                f"{BASE_URL}/api/v1/ilanlar",
                json=inv_payload,
                timeout=TIMEOUT
            )
            assert inv_resp.status_code == 422, f"Expected 422 for blank title/description, got {inv_resp.status_code}"

        # Step 5: Publish the listing via PATCH /api/v1/ilanlar/{id}/publish
        publish_resp = requests.patch(
            f"{BASE_URL}/api/v1/ilanlar/{listing_id}/publish",
            json={"status": "Aktif"},
            timeout=TIMEOUT
        )
        assert publish_resp.status_code == 200, f"Expected 200 OK on publish, got {publish_resp.status_code}"

        # Step 6: Test GET /api/advisor/listings/{id}/buyer-matches WITHOUT Authorization header
        buyer_matches_resp = requests.get(
            f"{BASE_URL}/api/advisor/listings/{listing_id}/buyer-matches",
            timeout=TIMEOUT
        )
        assert buyer_matches_resp.status_code == 401, f"Expected 401 Unauthorized, got {buyer_matches_resp.status_code}"

        print("PASS: buyer-matches-for-valid-listing-without-auth")
    except AssertionError as e:
        print(f"FAIL: buyer-matches-for-valid-listing-without-auth - {e}")
    except requests.RequestException as e:
        print(f"FAIL: buyer-matches-for-valid-listing-without-auth - Request error: {e}")
    finally:
        if listing_id:
            try:
                requests.delete(f"{BASE_URL}/api/v1/ilanlar/{listing_id}", timeout=TIMEOUT)
            except Exception:
                pass  # Cleanup best effort

test_buyer_matches_for_valid_listing_without_auth()