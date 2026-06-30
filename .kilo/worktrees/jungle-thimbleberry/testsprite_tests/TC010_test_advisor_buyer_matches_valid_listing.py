import requests

BASE_URL = "http://localhost:8002"
AUTH_LOGIN_PATH = "/api/v1/auth/login"
BUYER_MATCHES_PATH_TEMPLATE = "/api/advisor/listings/{id}/buyer-matches"
LISTING_CREATE_PATH = "/api/v1/admin/listings"  # Assumed admin listing creation endpoint for ID creation

ADMIN_USERNAME = "admin@example.com"
ADMIN_PASSWORD = "admin-password"

def test_advisor_buyer_matches_valid_listing():
    token = None
    listing_id = None

    try:
        # Step 1: Authenticate and get JWT token
        auth_response = requests.post(
            BASE_URL + AUTH_LOGIN_PATH,
            json={"email": ADMIN_USERNAME, "password": ADMIN_PASSWORD},
            timeout=30
        )
        assert auth_response.status_code == 200, f"Authentication failed with status {auth_response.status_code}"
        auth_data = auth_response.json()
        token = auth_data.get("token") or auth_data.get("access_token")
        assert token, "JWT token not found in auth response"

        headers = {"Authorization": f"Bearer {token}"}

        # Step 2: Create a new listing with canonical visible status 'yayinda'
        # Since PRD says listing creation in admin wizard is entry point and no listing create endpoint given,
        # but for test purposes, try creating through backend admin API (assuming API exists)
        # If no API exists, skip and raise error for test environment limitation.
        listing_payload = {
            "title": "Test Listing for Buyer Matches",
            "description": "Test description",
            "price": 1000000,
            "status": "yayinda"
        }
        create_listing_resp = requests.post(
            BASE_URL + LISTING_CREATE_PATH,
            json=listing_payload,
            headers=headers,
            timeout=30
        )
        assert create_listing_resp.status_code in (200, 201), \
            f"Listing creation failed with status {create_listing_resp.status_code}"
        listing_data = create_listing_resp.json()
        listing_id = listing_data.get("id")
        assert listing_id is not None, "Created listing ID not found"

        # Step 3: Request buyer matches for the created listing
        buyer_matches_resp = requests.get(
            BASE_URL + BUYER_MATCHES_PATH_TEMPLATE.format(id=listing_id),
            headers=headers,
            timeout=30
        )
        assert buyer_matches_resp.status_code == 200, f"Buyer matches request failed with status {buyer_matches_resp.status_code}"
        buyer_matches_data = buyer_matches_resp.json()

        assert isinstance(buyer_matches_data, dict) or isinstance(buyer_matches_data, list), "Response is not a list or dict"
        if isinstance(buyer_matches_data, dict):
            matches = buyer_matches_data.get("buyer_matches") or buyer_matches_data.get("matches") or buyer_matches_data.get("data")
            if matches is None:
                matches = [buyer_matches_data]
        else:
            matches = buyer_matches_data
        
        assert isinstance(matches, list), "Buyer matches should be a list"

        # Verify each match has match_score between 0 and 100
        for match in matches:
            assert "match_score" in match, "match_score field missing in a buyer match"
            score = match["match_score"]
            assert isinstance(score, (int, float)), "match_score is not numeric"
            assert 0 <= score <= 100, f"match_score {score} out of valid range 0-100"

    finally:
        # Cleanup: delete the created listing if possible
        if listing_id and token:
            try:
                del_resp = requests.delete(
                    BASE_URL + f"/api/v1/admin/listings/{listing_id}",
                    headers={"Authorization": f"Bearer {token}"},
                    timeout=30
                )
                # Allow 200 or 204 for successful deletion
                assert del_resp.status_code in (200, 204)
            except Exception:
                pass


test_advisor_buyer_matches_valid_listing()