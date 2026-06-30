import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

# Valid admin user credentials for authentication (should be changed to valid ones in real use)
ADMIN_EMAIL = "admin@example.com"
ADMIN_PASSWORD = "adminpassword"

def test_advisor_opportunities_with_valid_auth():
    # Step 1: Authenticate to get token
    login_url = f"{BASE_URL}/api/v1/auth/login"
    login_payload = {"email": ADMIN_EMAIL, "password": ADMIN_PASSWORD}
    login_headers = {"Content-Type": "application/json"}

    login_response = requests.post(login_url, json=login_payload, headers=login_headers, timeout=TIMEOUT)
    assert login_response.status_code == 200, f"Login failed with status {login_response.status_code}"
    token = login_response.json().get("token")
    assert token, "Auth token not found in login response"

    auth_headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    # Variables for created listing (to delete later)
    listing_id = None

    try:
        # Step 2: Create a listing via POST /api/v1/ilanlar
        create_listing_url = f"{BASE_URL}/api/v1/ilanlar"
        listing_data = {
            "baslik": "Sample Listing Title",
            "aciklama": "Sample description for testing.",
            "alan_m2": 120,
            "birim_fiyat": 5000,
            "il": "Istanbul",
            "ilce": "Kadikoy",
            "mahalle": "Moda",
            "lat": 40.987654,
            "lng": 29.123456
        }
        create_resp = requests.post(create_listing_url, json=listing_data, headers={"Content-Type": "application/json"}, timeout=TIMEOUT)
        assert create_resp.status_code == 201, f"Create listing failed with status {create_resp.status_code}"
        created_listing = create_resp.json()
        listing_id = created_listing.get("id")
        assert listing_id, "Created listing ID not found"

        # Step 3: Verify blank title returns 422
        invalid_listing_data = listing_data.copy()
        invalid_listing_data["baslik"] = ""
        invalid_resp = requests.post(create_listing_url, json=invalid_listing_data, headers={"Content-Type": "application/json"}, timeout=TIMEOUT)
        assert invalid_resp.status_code == 422, f"Expected 422 for blank title but got {invalid_resp.status_code}"

        # Step 4: Verify blank description returns 422
        invalid_listing_data = listing_data.copy()
        invalid_listing_data["aciklama"] = ""
        invalid_resp = requests.post(create_listing_url, json=invalid_listing_data, headers={"Content-Type": "application/json"}, timeout=TIMEOUT)
        assert invalid_resp.status_code == 422, f"Expected 422 for blank description but got {invalid_resp.status_code}"

        # Step 5: Publish the created listing with PATCH /api/v1/ilanlar/{id}/publish set to 'Aktif'
        publish_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}/publish"
        publish_payload = {"status": "Aktif"}
        publish_resp = requests.patch(publish_url, json=publish_payload, headers={"Content-Type": "application/json"}, timeout=TIMEOUT)
        assert publish_resp.status_code == 200, f"Publishing listing failed with status {publish_resp.status_code}"

        # Step 6: Call GET /api/advisor/opportunities with valid Authorization header
        advisor_url = f"{BASE_URL}/api/advisor/opportunities"
        advisor_resp = requests.get(advisor_url, headers=auth_headers, timeout=TIMEOUT)
        assert advisor_resp.status_code == 200, f"Advisor opportunities request failed with status {advisor_resp.status_code}"
        opportunities = advisor_resp.json()
        assert isinstance(opportunities, list), "Opportunities response is not a list"

        # Verify each opportunity has opportunity_score and scored components
        for opp in opportunities:
            assert "opportunity_score" in opp, "opportunity_score missing in opportunity"
            # scored components may be under a 'scored' key or separate keys, we check for 'scored' dict presence
            scored = opp.get("scored")
            assert scored is not None and isinstance(scored, dict), "Scored components missing or invalid in opportunity"

        print("PASS: advisor-opportunities-with-valid-auth")
    except AssertionError as ae:
        print(f"FAIL: advisor-opportunities-with-valid-auth - {ae}")
        raise
    except Exception as e:
        print(f"FAIL: advisor-opportunities-with-valid-auth - Unexpected error: {e}")
        raise
    finally:
        # Cleanup: delete the created listing if created
        if listing_id:
            try:
                delete_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}"
                # Assume admin auth required for delete - re-use auth_headers
                del_resp = requests.delete(delete_url, headers=auth_headers, timeout=TIMEOUT)
                # No assertion on delete, just attempt cleanup
            except Exception:
                pass

test_advisor_opportunities_with_valid_auth()