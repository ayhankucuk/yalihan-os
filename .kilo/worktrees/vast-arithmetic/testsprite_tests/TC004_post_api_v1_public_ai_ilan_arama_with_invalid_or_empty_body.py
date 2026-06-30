import requests

def test_post_api_v1_public_ai_ilan_arama_with_invalid_or_empty_body():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/v1/public-ai/ilan-arama"
    headers = {"Content-Type": "application/json"}

    # Test with empty body
    try:
        response_empty = requests.post(url, headers=headers, json={}, timeout=30)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response_empty.status_code == 500, f"Expected status code 500 but got {response_empty.status_code}"
    try:
        json_empty = response_empty.json()
    except ValueError:
        assert False, "Response is not valid JSON"
    assert isinstance(json_empty, dict), "Response JSON is not an object"
    assert json_empty.get("success") is False, "Expected success to be False"
    assert "message" in json_empty and isinstance(json_empty["message"], str) and json_empty["message"].strip() != "", "Expected non-empty error message in response"

    # Test with invalid body (wrong data type)
    invalid_body = {"query": 12345}  # query should be string, here it is int
    try:
        response_invalid = requests.post(url, headers=headers, json=invalid_body, timeout=30)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response_invalid.status_code == 500, f"Expected status code 500 but got {response_invalid.status_code}"
    try:
        json_invalid = response_invalid.json()
    except ValueError:
        assert False, "Response is not valid JSON"
    assert isinstance(json_invalid, dict), "Response JSON is not an object"
    assert json_invalid.get("success") is False, "Expected success to be False"
    assert "message" in json_invalid and isinstance(json_invalid["message"], str) and json_invalid["message"].strip() != "", "Expected non-empty error message in response"


test_post_api_v1_public_ai_ilan_arama_with_invalid_or_empty_body()