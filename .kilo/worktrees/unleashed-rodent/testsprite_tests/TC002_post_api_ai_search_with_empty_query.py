import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_post_api_ai_search_with_empty_query():
    url = f"{BASE_URL}/api/ai/search"
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    payloads = [
        {"query": ""},          # empty query
        {},                     # missing query field
        {"query": None},        # invalid None query
        {"query": "   "},       # whitespace query
    ]

    for payload in payloads:
        try:
            response = requests.post(url, json=payload, headers=headers, timeout=TIMEOUT)
        except requests.RequestException as e:
            assert False, f"Request failed: {e}"

        assert response.status_code == 400, f"Expected 400 for payload {payload}, got {response.status_code}"

        try:
            json_resp = response.json()
        except ValueError:
            assert False, "Response is not valid JSON"

        # Expect a validation error mentioning missing or invalid query
        error_msg = json_resp.get("error") or json_resp.get("message") or json_resp.get("detail") or ""
        assert any(term in error_msg.lower() for term in ["query", "missing", "invalid", "required"]), \
            f"Validation error message is missing or does not mention query field. Response: {json_resp}"

test_post_api_ai_search_with_empty_query()