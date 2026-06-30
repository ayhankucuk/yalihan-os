import requests

def test_ai_health_endpoint_success():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/v1/ai/health"
    headers = {
        "Accept": "application/json"
    }
    try:
        response = requests.get(url, headers=headers, timeout=30)
        assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"
        json_data = response.json()
        assert isinstance(json_data, dict), "Response is not a JSON object"
        # Accept any keys with any values, ensure it contains expected top-level keys
        assert "status" in json_data, f"Response missing 'status' key: {json_data}"
        assert "context7_meta" in json_data, f"Response missing 'context7_meta' key: {json_data}"

    except requests.Timeout:
        assert False, "Request timed out"
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"
    except ValueError:
        assert False, "Response is not valid JSON"

test_ai_health_endpoint_success()