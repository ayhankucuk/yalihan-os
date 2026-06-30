import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_post_api_ai_search_with_filters():
    url = f"{BASE_URL}/api/ai/search"
    headers = {
        "Content-Type": "application/json"
    }
    payload = {
        "query": "bodrumda 5 milyon altı villa",
        "filters": {
            "beds": 3
        }
    }
    try:
        response = requests.post(url, json=payload, headers=headers, timeout=TIMEOUT)
        assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"

        result_list = response.json()
        assert isinstance(result_list, list), "Response is not a list."

        # Validate each Ilan in results
        for ilan in result_list:
            assert isinstance(ilan, dict), "Ilan item is not a dictionary."
            # Validate required fields presence and types
            assert "id" in ilan, "Ilan missing 'id'"
            assert isinstance(ilan["id"], (int, str)), "'id' field type invalid"
            assert "title" in ilan, "Ilan missing 'title'"
            assert isinstance(ilan["title"], str), "'title' must be string"
            assert "price" in ilan, "Ilan missing 'price'"
            assert isinstance(ilan["price"], (int, float)), "'price' must be int or float"
            assert "location" in ilan, "Ilan missing 'location'"
            assert isinstance(ilan["location"], str), "'location' must be string"

            # Validate opportunity_score if present (not required but if included)
            if "opportunity_score" in ilan:
                score = ilan["opportunity_score"]
                assert isinstance(score, (int, float)), "'opportunity_score' must be numeric"
                assert 0 <= score <= 100, "'opportunity_score' out of expected range 0-100"

            # Validate buyer matching info if present
            if "buyer_matches" in ilan:
                buyer_matches = ilan["buyer_matches"]
                assert isinstance(buyer_matches, list), "'buyer_matches' should be a list"
                for match in buyer_matches:
                    assert "buyer_id" in match, "BuyerMatch missing 'buyer_id'"
                    assert isinstance(match["buyer_id"], (int, str)), "'buyer_id' type invalid"
                    assert "score" in match, "BuyerMatch missing 'score'"
                    score = match["score"]
                    assert isinstance(score, (int, float)), "'score' must be numeric"
                    assert 0 <= score <= 100, "'score' out of expected range 0-100"
                    assert "match_reasons" in match, "BuyerMatch missing 'match_reasons'"
                    assert isinstance(match["match_reasons"], list), "'match_reasons' must be a list"

        # Additional check: all results must have beds=3 from filters if beds info is present
        for ilan in result_list:
            # beds info could be nested inside ilan metadata or details, if structure known
            # No schema provided, so only check if beds field present then value is 3
            beds_value = ilan.get("beds")
            if beds_value is not None:
                assert beds_value == 3, f"Expected beds=3, got {beds_value}"

    except requests.Timeout:
        assert False, "Request timed out"
    except requests.RequestException as e:
        assert False, f"Request error: {e}"

test_post_api_ai_search_with_filters()