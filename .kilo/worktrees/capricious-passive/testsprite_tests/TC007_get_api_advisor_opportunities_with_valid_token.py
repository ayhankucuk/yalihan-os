import requests

BASE_URL = "http://localhost:8002"
LOGIN_URL = f"{BASE_URL}/api/v1/auth/login"
OPPORTUNITIES_URL = f"{BASE_URL}/api/advisor/opportunities"
TIMEOUT = 30

# Replace these with valid test user credentials
VALID_USERNAME = "testadvisor@example.com"
VALID_PASSWORD = "StrongPassword123!"

def test_get_api_advisor_opportunities_with_valid_token():
    try:
        # Step 1: Authenticate and get a valid JWT token
        login_payload = {
            "email": VALID_USERNAME,
            "password": VALID_PASSWORD
        }
        login_headers = {
            "Content-Type": "application/json"
        }
        login_response = requests.post(LOGIN_URL, json=login_payload, headers=login_headers, timeout=TIMEOUT)
        assert login_response.status_code == 200, f"Login failed with status {login_response.status_code}"

        login_json = login_response.json()
        assert isinstance(login_json, dict), "Login response is not a JSON object"
        assert "token" in login_json, "Login response JSON does not contain 'token'"

        token = login_json["token"]
        assert isinstance(token, str) and len(token) > 0, "Token is not a valid non-empty string"

        # Step 2: Call the /api/advisor/opportunities endpoint with Authorization header
        auth_headers = {
            "Authorization": f"Bearer {token}"
        }
        opportunities_response = requests.get(OPPORTUNITIES_URL, headers=auth_headers, timeout=TIMEOUT)
        assert opportunities_response.status_code == 200, f"Opportunities request failed with status {opportunities_response.status_code}"

        opportunities_json = opportunities_response.json()
        assert isinstance(opportunities_json, list), "Opportunities response is not a list"

        # Validate that each opportunity record includes opportunity_score and is between 0 and 100
        for opportunity in opportunities_json:
            assert isinstance(opportunity, dict), "Opportunity record is not a JSON object"
            assert "opportunity_score" in opportunity, "Opportunity record missing 'opportunity_score'"
            score = opportunity["opportunity_score"]
            # opportunity_score should be numeric and between 0 and 100 inclusive
            assert (isinstance(score, int) or isinstance(score, float)), "opportunity_score is not numeric"
            assert 0 <= score <= 100, f"opportunity_score {score} is out of valid range 0-100"

    except requests.exceptions.RequestException as e:
        assert False, f"HTTP request failed: {str(e)}"


test_get_api_advisor_opportunities_with_valid_token()
