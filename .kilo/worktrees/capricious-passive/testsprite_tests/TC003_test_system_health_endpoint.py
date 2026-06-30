import requests

def test_system_health_endpoint():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/v1/health"
    headers = {
        "Accept": "application/json"
    }
    try:
        response = requests.get(url, headers=headers, timeout=30)
        # Assert status code is 200 OK
        assert response.status_code == 200, f"Expected status 200, got {response.status_code}"
        
        json_data = response.json()
        # Validate that JSON is a dict and contains 'status' key
        assert isinstance(json_data, dict), "Response is not a JSON object"
        assert "status" in json_data, "Response JSON does not contain 'status' key indicating system status"
        # Check that status value is a non-empty string
        assert isinstance(json_data["status"], str) and json_data["status"].strip() != "", "System status value is not a valid string"
    except requests.RequestException as e:
        assert False, f"Request to /api/v1/health failed: {e}"
    except ValueError:
        assert False, "Response is not a valid JSON"

test_system_health_endpoint()
