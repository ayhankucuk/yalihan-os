import requests

def test_post_api_v1_auth_login_with_invalid_credentials():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/v1/auth/login"
    headers = {
        "Content-Type": "application/json"
    }
    # Intentionally invalid credentials
    payload = {
        "username": "invalid_user",
        "password": "wrong_password"
    }

    try:
        response = requests.post(url, json=payload, headers=headers, timeout=30)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response.status_code == 401, f"Expected status code 401, got {response.status_code}"

test_post_api_v1_auth_login_with_invalid_credentials()