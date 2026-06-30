import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_get_api_advisor_opportunities_with_invalid_token():
    url = f"{BASE_URL}/api/advisor/opportunities"
    # Using an obviously invalid JWT token (this simulates expired or malformed token)
    invalid_token = "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.invalid.token"
    headers = {
        "Authorization": invalid_token,
        "Accept": "application/json"
    }
    try:
        response = requests.get(url, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"HTTP request failed: {e}"

    # The response should be 401 Unauthorized due to invalid/expired token
    assert response.status_code == 401, f"Expected 401 Unauthorized, got {response.status_code}"
    
    # The response should be JSON with error message indicating token invalid or expired
    try:
        data = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    # Check that the error message or detail indicates invalid/expired token or session required
    error_messages = []
    if isinstance(data, dict):
        for key in ['error', 'message', 'detail', 'description']:
            if key in data and isinstance(data[key], str):
                error_messages.append(data[key].lower())
    else:
        assert False, "Expected JSON object in error response"

    # At least one error message should mention invalid or expired token or indicate login/session required
    assert any(
        "invalid" in msg or "expired" in msg or "unauthorized" in msg or "gerekli" in msg or "giriş" in msg
        for msg in error_messages
    ), \
        f"Error message does not indicate invalid or expired token or login required: {error_messages}"

test_get_api_advisor_opportunities_with_invalid_token()
