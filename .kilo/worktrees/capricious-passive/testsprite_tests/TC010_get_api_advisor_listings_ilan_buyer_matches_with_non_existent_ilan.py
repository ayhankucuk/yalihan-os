import requests

BASE_URL = "http://localhost:8002"
LOGIN_URL = f"{BASE_URL}/api/v1/auth/login"
BUYER_MATCHES_URL_TEMPLATE = f"{BASE_URL}/api/advisor/listings/{{ilan}}/buyer-matches"

VALID_USER = {
    "email": "testuser@example.com",
    "password": "correct_password"
}

def test_get_advisor_listings_buyer_matches_with_non_existent_ilan():
    session = requests.Session()
    try:
        # Login to get token
        login_resp = session.post(LOGIN_URL, json=VALID_USER, timeout=30)
        assert login_resp.status_code == 200, f"Login failed with status {login_resp.status_code}"
        login_json = login_resp.json()
        assert "token" in login_json or "access_token" in login_json, "Token not found in login response"
        token = login_json.get("token") or login_json.get("access_token")
        assert isinstance(token, str) and len(token) > 0, "Invalid token received"

        headers = {
            "Authorization": f"Bearer {token}"
        }

        # Use a non-existent listing ID (ilan)
        non_existent_ilan = "nonexistent-ilan-123456789"

        buyer_matches_url = BUYER_MATCHES_URL_TEMPLATE.format(ilan=non_existent_ilan)
        resp = session.get(buyer_matches_url, headers=headers, timeout=30)

        assert resp.status_code == 404, f"Expected 404 Not Found, got {resp.status_code}"

        content_type = resp.headers.get('Content-Type', '')
        assert 'application/json' in content_type, f"Expected 'application/json' content type, got '{content_type}'"

        resp_text = resp.text.strip()
        assert resp_text, "Empty response body for 404 error"

        try:
            resp_json = resp.json()
        except Exception as e:
            assert False, f"Response body is not valid JSON: {str(e)}"

        assert isinstance(resp_json, dict), "Response is not a JSON object"
        error_message = resp_json.get("message") or resp_json.get("error") or resp_json.get("detail")
        assert error_message and isinstance(error_message, str), "No valid error message in 404 response"

    finally:
        session.close()

test_get_advisor_listings_buyer_matches_with_non_existent_ilan()
