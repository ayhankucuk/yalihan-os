import requests

BASE_URL = "http://localhost:8002"

def test_post_api_v1_public_ai_ilan_arama_with_invalid_body():
    url = f"{BASE_URL}/api/v1/public-ai/ilan-arama"
    headers = {
        "Content-Type": "application/json"
    }

    # Test with empty body
    response_empty = requests.post(url, headers=headers, json=None, timeout=30)
    assert response_empty.status_code == 400, f"Expected 400 for empty body, got {response_empty.status_code}"
    json_empty = response_empty.json()
    assert isinstance(json_empty, dict), "Expected JSON response with error details for empty body"
    assert ("error" in json_empty or "errors" in json_empty or "message" in json_empty), "Expected validation error details in response for empty body"

    # Test with malformed body (not a valid JSON object, e.g. a string)
    malformed_payload = "this is not a json object"
    response_malformed = requests.post(url, headers=headers, data=malformed_payload, timeout=30)
    assert response_malformed.status_code == 400, f"Expected 400 for malformed body, got {response_malformed.status_code}"
    try:
        json_malformed = response_malformed.json()
        assert isinstance(json_malformed, dict), "Expected JSON response with error details for malformed body"
        assert ("error" in json_malformed or "errors" in json_malformed or "message" in json_malformed), "Expected validation error details in response for malformed body"
    except ValueError:
        # If server response is not valid JSON, also consider it a failure
        assert False, "Response for malformed body is not valid JSON"


test_post_api_v1_public_ai_ilan_arama_with_invalid_body()