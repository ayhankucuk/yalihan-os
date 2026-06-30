import requests

BASE_URL = "http://localhost:8002"


def test_post_api_v1_auth_login_with_valid_credentials():
    url = f"{BASE_URL}/api/v1/auth/login"
    payload = {
        "email": "valid_user@example.com",
        "password": "ValidPassword123!"
    }
    headers = {
        "Content-Type": "application/json"
    }
    try:
        response = requests.post(url, json=payload, headers=headers, timeout=30)
        response.raise_for_status()
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"

        content_type = response.headers.get('Content-Type', '')
        assert 'application/json' in content_type, f"Expected JSON response but got {content_type}"

        try:
            json_data = response.json()
        except ValueError as e:
            assert False, f"Response is not valid JSON: {e}"

        assert ("token" in json_data or "access_token" in json_data), "Response JSON does not contain token"
        token = json_data.get("token") or json_data.get("access_token")
        assert isinstance(token, str) and len(token) > 0, "Token is not a valid non-empty string"
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"


test_post_api_v1_auth_login_with_valid_credentials()