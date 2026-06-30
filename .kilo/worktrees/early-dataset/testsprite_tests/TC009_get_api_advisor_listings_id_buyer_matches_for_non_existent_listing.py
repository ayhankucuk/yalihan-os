import requests

BASE_URL = "http://localhost:8002"
LOGIN_ENDPOINT = "/api/v1/auth/login"
BUYER_MATCHES_ENDPOINT_TEMPLATE = "/api/advisor/listings/{id}/buyer-matches"
TIMEOUT = 30

def test_get_api_advisor_listings_id_buyer_matches_nonexistent_listing():
    # Step 1: Authenticate to get a valid token
    login_url = BASE_URL + LOGIN_ENDPOINT
    login_payload = {
        "email": "advisor_user",
        "password": "advisor_password"
    }
    headers = {"Content-Type": "application/json"}
    try:
        login_response = requests.post(login_url, json=login_payload, headers=headers, timeout=TIMEOUT)
        assert login_response.status_code == 200, f"Login failed with status {login_response.status_code}"
        try:
            json_response = login_response.json()
        except requests.JSONDecodeError:
            assert False, "Login response is not a valid JSON"
        token = json_response.get("token") or json_response.get("access_token")
        assert token, "Authentication token not found in login response"
    except requests.RequestException as e:
        assert False, f"Login request exception: {str(e)}"

    # Step 2: Use a non-existent listing ID (e.g. very large number unlikely to exist)
    non_existent_listing_id = 999999999
    buyer_matches_url = BASE_URL + BUYER_MATCHES_ENDPOINT_TEMPLATE.format(id=non_existent_listing_id)
    auth_headers = {
        "Authorization": f"Bearer {token}"
    }

    try:
        response = requests.get(buyer_matches_url, headers=auth_headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"GET buyer matches request exception: {str(e)}"
    
    # Step 3: Assert that the status code is 404 Not Found
    assert response.status_code == 404, f"Expected 404 for non-existent listing, got {response.status_code}"

test_get_api_advisor_listings_id_buyer_matches_nonexistent_listing()
