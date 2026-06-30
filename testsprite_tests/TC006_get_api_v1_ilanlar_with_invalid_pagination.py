import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_get_api_v1_ilanlar_with_invalid_pagination():
    url = f"{BASE_URL}/api/v1/ilanlar"
    params = {"page": -1}
    headers = {
        "Accept": "application/json"
    }
    try:
        response = requests.get(url, headers=headers, params=params, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response.status_code == 400, f"Expected status code 400, got {response.status_code}"

    try:
        json_data = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    # According to the instructions and context, expect a validation error related to pagination
    # Validate JSON structure minimally for Context7 compliance (assumed error response contains 'message' and 'errors')
    assert isinstance(json_data, dict), "Response JSON is not an object"
    assert "message" in json_data, "'message' not found in error response"
    # errors key may contain details on what was wrong with pagination
    assert "errors" in json_data, "'errors' not found in error response"
    assert "page" in json_data["errors"], "'page' validation error not found in errors"

test_get_api_v1_ilanlar_with_invalid_pagination()