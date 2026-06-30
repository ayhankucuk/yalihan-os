import requests
import traceback

BASE_URL = "http://localhost:8002"
LOGIN_URL = f"{BASE_URL}/api/v1/auth/login"
ILAN_SEARCH_URL = f"{BASE_URL}/api/v1/public-ai/ilan-arama"
BUYER_MATCHES_URL_TEMPLATE = f"{BASE_URL}/api/advisor/listings/{{ilan}}/buyer-matches"
TIMEOUT = 30

# Replace with valid advisor credentials
VALID_CREDENTIALS = {
    "email": "advisor@example.com",
    "password": "securepassword"
}

def test_get_api_advisor_listings_ilan_buyer_matches_with_valid_token():
    token = None
    ilan_id = None

    try:
        # Step 1: Login to get JWT token
        login_resp = requests.post(LOGIN_URL, json=VALID_CREDENTIALS, timeout=TIMEOUT)
        assert login_resp.status_code == 200, f"Login failed with status {login_resp.status_code}: {login_resp.text}"
        login_data = login_resp.json()
        assert "token" in login_data or "access_token" in login_data, "Token not found in login response"
        token = login_data.get("token") or login_data.get("access_token")
        assert isinstance(token, str) and len(token) > 0, "Invalid token received"

        headers = {"Authorization": f"Bearer {token}"}

        # Step 2: Use the public AI search to find at least one listing to get a valid ilan ID
        search_body = {"query": "bodrumda 5 milyon altı villa"}
        search_resp = requests.post(ILAN_SEARCH_URL, json=search_body, timeout=TIMEOUT)
        assert search_resp.status_code == 200, f"Search failed with status {search_resp.status_code}: {search_resp.text}"
        listings = search_resp.json()
        assert isinstance(listings, list), "Listings is not a list"
        assert len(listings) > 0, "No listings found from search"
        # Extract ilan ID from first listing; assume ilan ID is under "id" or "ilan_id"
        possible_keys = ['id', 'ilan_id']
        ilan_id = None
        for key in possible_keys:
            if key in listings[0]:
                ilan_id = listings[0][key]
                break
        assert ilan_id is not None, "No valid ilan id found in listing"

        # Step 3: Call GET /api/advisor/listings/{ilan}/buyer-matches with auth header
        buyer_matches_url = BUYER_MATCHES_URL_TEMPLATE.format(ilan=ilan_id)
        resp = requests.get(buyer_matches_url, headers=headers, timeout=TIMEOUT)
        assert resp.status_code == 200, f"Buyer matches request failed with status {resp.status_code}: {resp.text}"
        matches = resp.json()
        assert isinstance(matches, list), "Buyer matches response is not a list"

        # Validate that each item has required fields like match score and buyer info
        for match in matches:
            assert "match_score" in match, "match_score missing in buyer match"
            assert isinstance(match["match_score"], (int, float)), "match_score is not a number"
            assert 0 <= match["match_score"] <= 100, "match_score out of range 0-100"
            # Optionally check for ranked order descending by match_score
        # Optional: Verify list is ordered descending by match_score
        scores = [m["match_score"] for m in matches]
        assert scores == sorted(scores, reverse=True), "Buyer matches are not ordered by descending match_score"

    except Exception:
        traceback.print_exc()
        raise
    # No resource creation/deletion needed as listing is taken from existing data

test_get_api_advisor_listings_ilan_buyer_matches_with_valid_token()