import requests

def test_ai_health_endpoint_failure():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/v1/ai/health"
    timeout = 30
    try:
        response = requests.get(url, timeout=timeout)
    except requests.RequestException as e:
        assert False, f"Request to {url} failed with exception: {e}"
    else:
        # Assert the status code is 503 Service Unavailable, indicating AI subsystem degraded
        assert response.status_code == 503, (
            f"Expected status code 503 but got {response.status_code}. "
            f"Response text: {response.text}"
        )

test_ai_health_endpoint_failure()