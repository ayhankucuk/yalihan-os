import requests

def test_public_ai_search_bad_request():
    base_url = "http://localhost:8002"
    url = f"{base_url}/api/v1/public-ai/ilan-arama"
    headers = {
        "Content-Type": "application/json"
    }
    malformed_json = '{"query": "bodrumda 5 milyon altı villa"'  # missing closing brace to simulate malformed JSON

    try:
        response = requests.post(url, headers=headers, data=malformed_json, timeout=30)
    except requests.exceptions.RequestException as e:
        assert False, f"Request failed with exception: {e}"

    assert response.status_code in [400, 422], f"Expected 400 or 422 Bad Request but got {response.status_code}"

    try:
        error_response = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    assert "error" in error_response or "message" in error_response or "validation" in error_response, \
        "Error response does not contain validation error details"

test_public_ai_search_bad_request()
