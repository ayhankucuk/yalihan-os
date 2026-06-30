import requests

BASE_URL = "http://localhost:8002"
LOGIN_ENDPOINT = "/api/v1/auth/login"
OPPORTUNITIES_ENDPOINT = "/api/advisor/opportunities"
TIMEOUT = 30

VALID_ADMIN_CREDENTIALS = {
    "email": "admin@example.com",
    "password": "correct_password"
}

def get_jwt_token():
    resp = requests.post(
        BASE_URL + LOGIN_ENDPOINT,
        json=VALID_ADMIN_CREDENTIALS,
        timeout=TIMEOUT
    )
    resp.raise_for_status()
    data = resp.json()
    token = data.get("token") or data.get("access_token")  # Accept either key name
    assert token and isinstance(token, str), "JWT token missing or invalid in login response"
    return token

def test_advisor_opportunities_authorized():
    token = get_jwt_token()
    headers = {
        "Authorization": f"Bearer {token}"
    }
    response = requests.get(
        BASE_URL + OPPORTUNITIES_ENDPOINT,
        headers=headers,
        timeout=TIMEOUT
    )
    assert response.status_code == 200, f"Expected 200 OK, got {response.status_code}"
    json_data = response.json()
    assert isinstance(json_data, list), "Response is not a list"
    for item in json_data:
        assert isinstance(item, dict), "Each opportunity item should be a dict"
        assert "opportunity_score" in item, "opportunity_score missing in opportunity item"
        score = item["opportunity_score"]
        assert isinstance(score, (int, float)), "opportunity_score should be a number"
        assert 0 <= score <= 100, f"opportunity_score {score} out of valid range 0-100"

test_advisor_opportunities_authorized()