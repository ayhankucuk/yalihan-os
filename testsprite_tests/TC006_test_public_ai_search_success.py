import requests

def test_public_ai_search_success():
    base_url = "http://localhost:8002"
    endpoint = "/api/v1/public-ai/ilan-arama"
    url = base_url + endpoint
    headers = {
        "Content-Type": "application/json"
    }
    payload = {
        "query": "bodrumda 5 milyon altı villa"
    }
    try:
        response = requests.post(url, json=payload, headers=headers, timeout=30)
        assert response.status_code == 200, f"Expected 200 OK but got {response.status_code}"
        json_data = response.json()

        # Validate presence of data
        assert "data" in json_data, "Missing 'data' in response"
        results = json_data["data"]
        assert isinstance(results, list), "'data' should be a list"
        assert len(results) > 0, "Results list should not be empty"

        # Sample checks on result entries
        sample_result = results[0]
        # Expecting at least an id, title or description, and relevance score
        assert "id" in sample_result, "Result missing 'id'"
        assert any(k in sample_result for k in ("title", "description")), "Result missing 'title' or 'description'"
        assert "relevance_score" in sample_result, "Result missing 'relevance_score'"

        # relevance_score should be numeric and within 0-100
        relevance = sample_result["relevance_score"]
        assert isinstance(relevance, (int, float)), "'relevance_score' should be numeric"
        assert 0 <= relevance <= 100, "'relevance_score' should be between 0 and 100"

    except requests.exceptions.RequestException as e:
        assert False, f"Request failed: {e}"
    except ValueError:
        assert False, "Response is not valid JSON"

test_public_ai_search_success()
