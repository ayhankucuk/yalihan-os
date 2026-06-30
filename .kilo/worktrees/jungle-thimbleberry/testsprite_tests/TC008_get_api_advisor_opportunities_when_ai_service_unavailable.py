import requests

BASE_URL = "http://localhost:8002"
LOGIN_ENDPOINT = f"{BASE_URL}/api/auth/login"
OPPORTUNITIES_ENDPOINT = f"{BASE_URL}/api/advisor/opportunities"
TIMEOUT = 30

def test_get_api_advisor_opportunities_when_ai_service_unavailable():
    # Use valid credentials to authenticate and obtain JWT token
    login_payload = {
        "username": "valid_user",
        "password": "valid_password"
    }

    try:
        login_resp = requests.post(LOGIN_ENDPOINT, json=login_payload, timeout=TIMEOUT)
        login_resp.raise_for_status()
        token = login_resp.json().get("token") or login_resp.json().get("access_token")
        assert token and isinstance(token, str), "Login response does not contain a valid token"
    except requests.RequestException as e:
        assert False, f"Login request failed: {str(e)}"

    headers = {
        "Authorization": f"Bearer {token}"
    }

    try:
        resp = requests.get(OPPORTUNITIES_ENDPOINT, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request to {OPPORTUNITIES_ENDPOINT} failed: {str(e)}"

    assert resp.status_code == 500, f"Expected status code 500, got {resp.status_code}"
    try:
        json_resp = resp.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    assert isinstance(json_resp, dict), "Error response is expected to be a JSON object"
    error_detail = json_resp.get("error") or json_resp.get("detail") or json_resp.get("message")
    assert error_detail is not None, "Response JSON does not contain error detail"
    assert "service-unavailable" in str(error_detail).lower(), f"Error detail does not indicate service-unavailable, got: {error_detail}"

test_get_api_advisor_opportunities_when_ai_service_unavailable()