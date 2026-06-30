import requests

def test_get_api_advisor_opportunities_without_auth():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/advisor/opportunities"
    headers = {
        # No Authorization header included intentionally
        "Accept": "application/json"
    }
    try:
        response = requests.get(url, headers=headers, timeout=30)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    # Assert status code 401 Unauthorized
    assert response.status_code == 401, f"Expected status 401 but got {response.status_code}"

    # Response should probably be a JSON error message
    content_type = response.headers.get("Content-Type", "")
    assert "application/json" in content_type.lower(), "Response content-type is not JSON"

    try:
        json_data = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    # The error details can vary, but at minimum check presence of error or message keys
    assert any(key in json_data for key in ("error", "message", "detail")), \
        "JSON error message missing expected keys"

test_get_api_advisor_opportunities_without_auth()