import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

# Use valid admin credentials for login (replace with correct test credentials)
ADMIN_EMAIL = "admin@example.com"
ADMIN_PASSWORD = "adminpassword"

def test_buyer_matches_for_nonexistent_listing():
    session = requests.Session()
    try:
        # Step 1: Login to get a valid Authorization token
        login_url = f"{BASE_URL}/api/v1/auth/login"
        login_payload = {
            "email": ADMIN_EMAIL,
            "password": ADMIN_PASSWORD
        }
        login_resp = session.post(login_url, json=login_payload, timeout=TIMEOUT)
        assert login_resp.status_code == 200, f"Login failed with status {login_resp.status_code}"
        token = login_resp.json().get("token")
        assert token and isinstance(token, str), "No token received in login response"
        headers = {
            "Authorization": f"Bearer {token}"
        }

        # Step 2: Create a listing with required fields
        create_listing_url = f"{BASE_URL}/api/v1/ilanlar"
        listing_data = {
            "baslik": "Test Listing Title",
            "aciklama": "Test Listing Description",
            "alan_m2": 100,
            "birim_fiyat": 5000,
            "il": "Istanbul",
            "ilce": "Kadikoy",
            "mahalle": "Moda",
            "lat": 40.987,
            "lng": 29.028
        }
        create_resp = session.post(create_listing_url, json=listing_data, timeout=TIMEOUT)
        assert create_resp.status_code == 201, f"Listing creation failed with status {create_resp.status_code}"
        listing_id = create_resp.json().get("id")
        assert listing_id is not None, "No listing id returned from creation"

        # Step 3: Verify blank title returns 422
        invalid_data_title = listing_data.copy()
        invalid_data_title["baslik"] = ""
        invalid_resp_title = session.post(create_listing_url, json=invalid_data_title, timeout=TIMEOUT)
        assert invalid_resp_title.status_code == 422, f"Expected 422 for blank title, got {invalid_resp_title.status_code}"

        # Step 4: Verify blank description returns 422
        invalid_data_descr = listing_data.copy()
        invalid_data_descr["aciklama"] = ""
        invalid_resp_descr = session.post(create_listing_url, json=invalid_data_descr, timeout=TIMEOUT)
        assert invalid_resp_descr.status_code == 422, f"Expected 422 for blank description, got {invalid_resp_descr.status_code}"

        # Step 5: Publish the listing (PATCH)
        publish_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}/publish"
        publish_data = {
            "status": "Aktif"
        }
        publish_resp = session.patch(publish_url, json=publish_data, timeout=TIMEOUT)
        assert publish_resp.status_code == 200, f"Publishing listing failed with status {publish_resp.status_code}"

        # Step 6: Use a non-existent listing ID for buyer matches endpoint
        non_existent_id = 999999999  # Some large ID unlikely to exist
        buyer_matches_url = f"{BASE_URL}/api/advisor/listings/{non_existent_id}/buyer-matches"
        bm_resp = session.get(buyer_matches_url, headers=headers, timeout=TIMEOUT)
        assert bm_resp.status_code == 404, f"Expected 404 for non-existent listing, got {bm_resp.status_code}"

        print("TEST PASSED: TC009 - buyer-matches-for-nonexistent-listing")

    except AssertionError as e:
        print(f"TEST FAILED: TC009 - buyer-matches-for-nonexistent-listing: {e}")

    finally:
        # Clean up: delete the created listing if listing_id exists
        if 'listing_id' in locals() and listing_id:
            try:
                delete_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}"
                # Assuming DELETE /api/v1/ilanlar/{id} is supported for cleanup
                del_resp = session.delete(delete_url, headers=headers, timeout=TIMEOUT)
                if del_resp.status_code not in (200,204):
                    print(f"Warning: failed to delete listing {listing_id}, status: {del_resp.status_code}")
            except Exception as cleanup_err:
                print(f"Warning: exception during cleanup of listing {listing_id}: {cleanup_err}")

test_buyer_matches_for_nonexistent_listing()