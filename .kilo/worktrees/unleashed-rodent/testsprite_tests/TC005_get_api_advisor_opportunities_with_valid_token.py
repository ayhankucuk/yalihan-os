import requests

BASE_URL = "http://localhost:8002"
LOGIN_ENDPOINT = f"{BASE_URL}/api/auth/login"
OPPORTUNITIES_ENDPOINT = f"{BASE_URL}/api/advisor/opportunities"
TIMEOUT = 30

def test_get_api_advisor_opportunities_with_valid_token():
    # Credentials for login - assumed valid for test execution
    auth_payload = {
        "username": "testuser",
        "password": "testpassword"
    }
    # Authenticate to get JWT token
    try:
        auth_response = requests.post(LOGIN_ENDPOINT, json=auth_payload, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Authentication request failed: {e}"

    assert auth_response.status_code == 200, f"Authentication failed with status {auth_response.status_code}"
    auth_json = auth_response.json()
    token = auth_json.get("token")
    assert token, "JWT token not found in authentication response"

    headers = {
        "Authorization": f"Bearer {token}"
    }

    try:
        response = requests.get(OPPORTUNITIES_ENDPOINT, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Opportunities request failed: {e}"

    assert response.status_code == 200, f"Expected 200 OK, got {response.status_code}"

    try:
        opportunities = response.json()
    except ValueError:
        assert False, "Response is not a valid JSON"

    assert isinstance(opportunities, list), "Response JSON is not a list"

    for opp in opportunities:
        assert isinstance(opp, dict), "Opportunity item is not a dictionary"
        # Validate required fields in each Opportunity object
        assert "opportunity_score" in opp, "'opportunity_score' missing in opportunity object"
        assert isinstance(opp["opportunity_score"], (int, float)), "'opportunity_score' is not a number"
        assert 0 <= opp["opportunity_score"] <= 100, "'opportunity_score' out of expected range 0-100"
        # The reason metadata can be named as 'reason' or contain multiple explanatory fields but 'reason' must exist
        assert "reason" in opp, "'reason' metadata missing in opportunity object"
        reason = opp["reason"]
        assert isinstance(reason, (str, dict, list)), "'reason' should be a string or structured data"

test_get_api_advisor_opportunities_with_valid_token()