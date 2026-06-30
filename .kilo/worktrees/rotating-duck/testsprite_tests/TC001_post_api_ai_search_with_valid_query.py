import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30
HEADERS = {"Content-Type": "application/json"}

def test_post_api_ai_search_with_valid_query():
    url = f"{BASE_URL}/api/ai/search"
    payload = {"query": "bodrumda 5 milyon altı villa"}

    try:
        response = requests.post(url, json=payload, headers=HEADERS, timeout=TIMEOUT)
        response.raise_for_status()
    except requests.exceptions.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"

    try:
        data = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    assert isinstance(data, list), f"Expected response to be a list, got {type(data)}"

    # Validate each Ilan object in the list
    for item in data:
        assert isinstance(item, dict), "Each Ilan should be a dict"
        for key in ("id", "title", "price", "location"):
            assert key in item, f"Missing key '{key}' in Ilan object"
        # Additional sanity checks
        assert isinstance(item["id"], (int, str)), "'id' should be int or str"
        assert isinstance(item["title"], str) and item["title"], "'title' should be a non-empty string"
        assert (isinstance(item["price"], (int, float)) and item["price"] >= 0) or item["price"] is None, "'price' should be a non-negative number or None"
        assert isinstance(item["location"], str) and item["location"], "'location' should be a non-empty string"

if __name__ != "__main__":
    test_post_api_ai_search_with_valid_query()