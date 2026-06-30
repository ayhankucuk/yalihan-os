import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_get_advisor_listing_buyer_matches_invalid_or_missing_token():
    # Since we need a valid listing ID, create a new listing first (simulate creation if necessary).
    # The PRD does not provide an endpoint to create a listing directly, so we assume an existing ID.
    # Use a known dummy listing ID since no creation endpoint or data given.
    listing_id = 1

    url = f"{BASE_URL}/api/advisor/listings/{listing_id}/buyer-matches"

    # Case 1: Missing Authorization header
    response_missing_token = requests.get(url, timeout=TIMEOUT)
    assert response_missing_token.status_code == 401, f"Expected 401 for missing token, got {response_missing_token.status_code}"

    # Case 2: Invalid Authorization token
    headers_invalid_token = {
        "Authorization": "Bearer invalid_or_expired_token"
    }
    response_invalid_token = requests.get(url, headers=headers_invalid_token, timeout=TIMEOUT)
    assert response_invalid_token.status_code == 401, f"Expected 401 for invalid token, got {response_invalid_token.status_code}"

test_get_advisor_listing_buyer_matches_invalid_or_missing_token()