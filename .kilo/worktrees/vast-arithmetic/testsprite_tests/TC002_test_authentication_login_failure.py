import requests

def test_authentication_login_failure():
    base_url = "http://localhost:8002"
    login_url = f"{base_url}/api/v1/auth/login"
    headers = {
        "Content-Type": "application/json"
    }
    payload = {
        "email": "invalid_user@example.com",
        "password": "wrong_password"
    }

    try:
        response = requests.post(login_url, json=payload, headers=headers, timeout=30)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response.status_code == 401, f"Expected status code 401, got {response.status_code}"

    try:
        resp_json = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    # The error message should be present and informative
    assert "error" in resp_json or "message" in resp_json, "Expected error message in response"

test_authentication_login_failure()