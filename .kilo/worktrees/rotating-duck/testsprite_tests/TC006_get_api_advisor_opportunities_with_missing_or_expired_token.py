import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_get_api_advisor_opportunities_missing_or_expired_token():
    url = f"{BASE_URL}/api/advisor/opportunities"

    # Case 1: Missing Authorization header
    try:
        response = requests.get(url, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"
    assert response.status_code == 401, f"Expected 401 Unauthorized, got {response.status_code}"

    # Case 2: Expired or invalid token
    headers = {
        "Authorization": "Bearer expired.or.invalid.token"
    }
    try:
        response = requests.get(url, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"
    assert response.status_code == 401, f"Expected 401 Unauthorized with invalid token, got {response.status_code}"


test_get_api_advisor_opportunities_missing_or_expired_token()