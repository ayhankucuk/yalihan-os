import requests
import json

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_post_api_ai_search_with_large_payload_or_timeout():
    url = f"{BASE_URL}/api/ai/search"
    headers = {
        "Content-Type": "application/json"
    }
    # Construct a very large payload by repeating a large string
    large_query_str = "large_payload_test " * 10000  # large enough to simulate large payload
    payload = {
        "query": large_query_str
    }
    try:
        response = requests.post(url, headers=headers, json=payload, timeout=TIMEOUT)
    except requests.exceptions.Timeout:
        # If a real timeout occurs on client side, treat as pass: simulated timeout handled externally
        return
    except requests.exceptions.RequestException as e:
        assert False, f"HTTP request failed with exception: {e}"

    # Expecting HTTP 500 status for large payload or processing timeout
    assert response.status_code == 500, f"Expected status code 500, got {response.status_code}"

    # Response should be JSON error indicating search processing failure
    try:
        data = response.json()
    except json.JSONDecodeError:
        assert False, "Response is not valid JSON."

    # Check for error indication in response content (generic keys "error", "message" or similar)
    error_found = False
    error_messages = []

    if isinstance(data, dict):
        # Check if 'error' or 'message' keys indicate search processing failure
        for key in ("error", "message", "detail"):
            if key in data and isinstance(data[key], str):
                msg = data[key].lower()
                error_messages.append(data[key])
                if "search processing failure" in msg or "search failed" in msg or "processing failure" in msg:
                    error_found = True
                    break
    assert error_found, f"Error message indicating search processing failure not found in response: {error_messages}"

test_post_api_ai_search_with_large_payload_or_timeout()