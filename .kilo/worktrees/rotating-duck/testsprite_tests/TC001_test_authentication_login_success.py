import requests

BASE_URL = "http://localhost:8002"
LOGIN_ENDPOINT = "/api/v1/auth/login"
TIMEOUT = 30

def test_authentication_login_success():
    url = BASE_URL + LOGIN_ENDPOINT
    headers = {
        "Content-Type": "application/json"
    }
    # Valid credentials - replace with known valid admin credentials
    payload = {
        "email": "admin@example.com",
        "password": "correct_password"
    }

    try:
        response = requests.post(url, json=payload, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request to {url} failed: {e}"

    assert response.status_code == 200, f"Expected 200 OK, got {response.status_code}"

    try:
        response_json = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    assert "token" in response_json, "Response JSON missing 'token'"
    assert isinstance(response_json["token"], str) and len(response_json["token"]) > 0, "'token' should be a non-empty string"

    assert "user" in response_json, "Response JSON missing 'user'"
    user_info = response_json["user"]
    assert isinstance(user_info, dict), "'user' should be a JSON object"

    # Basic user info validation
    assert "id" in user_info, "'user' object missing 'id'"
    assert "email" in user_info, "'user' object missing 'email'"
    assert user_info["email"] == payload["email"], "Returned user email does not match login email"

test_authentication_login_success()