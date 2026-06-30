import requests

def test_advisor_opportunities_unauthorized():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/advisor/opportunities"
    headers = {
        # Intentionally no Authorization header to test unauthorized access
    }
    try:
        response = requests.get(url, headers=headers, timeout=30)
    except requests.RequestException as e:
        assert False, f"Request failed with exception: {e}"

    assert response.status_code == 401, (
        f"Expected status code 401 Unauthorized but got {response.status_code}. "
        f"Response content: {response.text}"
    )

test_advisor_opportunities_unauthorized()