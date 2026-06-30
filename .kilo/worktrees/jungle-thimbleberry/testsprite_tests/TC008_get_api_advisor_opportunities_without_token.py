import requests

BASE_URL = "http://localhost:8002"

def test_get_api_advisor_opportunities_without_token():
    url = f"{BASE_URL}/api/advisor/opportunities"
    try:
        response = requests.get(url, timeout=30)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response.status_code == 401, f"Expected status code 401, got {response.status_code}"
    try:
        json_data = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    # Validate presence of error message (assuming error message key could be 'error' or 'message')
    error_keys = ['error', 'message', 'detail']
    assert any(key in json_data for key in error_keys), f"Response JSON does not contain error message keys {error_keys}"

test_get_api_advisor_opportunities_without_token()