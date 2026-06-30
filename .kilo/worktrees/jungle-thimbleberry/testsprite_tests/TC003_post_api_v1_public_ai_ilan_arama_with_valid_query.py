import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30


def test_post_api_v1_public_ai_ilan_arama_with_valid_query():
    url = f"{BASE_URL}/api/v1/public-ai/ilan-arama"
    headers = {
        "Content-Type": "application/json"
    }
    payload = {
        "query": "bodrumda 5 milyon altı villa"
    }

    response = None
    try:
        response = requests.post(url, json=payload, headers=headers, timeout=TIMEOUT)
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"

        data = response.json()
        assert data.get("success") is True, "Expected 'success' to be True"
        assert isinstance(data.get("results"), list), "Expected 'results' to be an array"
        assert isinstance(data.get("count"), int), "Expected 'count' to be a number"
        # Per instruction note, results array expected to be empty due to naming drift
        assert data.get("results") == [], "Expected 'results' to be an empty list due to naming drift"
    except requests.RequestException as e:
        assert False, f"Request failed: {str(e)}"
    except ValueError:
        assert False, "Response is not valid JSON"


test_post_api_v1_public_ai_ilan_arama_with_valid_query()