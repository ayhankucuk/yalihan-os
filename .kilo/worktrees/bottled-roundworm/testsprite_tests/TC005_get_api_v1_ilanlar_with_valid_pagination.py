import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_get_api_v1_ilanlar_with_valid_pagination():
    url = f"{BASE_URL}/api/v1/ilanlar"
    params = {"page": 1}  # valid pagination parameter
    headers = {
        "Accept": "application/json"
    }
    try:
        response = requests.get(url, headers=headers, params=params, timeout=TIMEOUT)
        assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"
        json_data = response.json()
        # Adjust assertion to cover response wrapper or list directly
        if isinstance(json_data, dict) and 'data' in json_data:
            ilan_list = json_data['data']
        else:
            ilan_list = json_data
        assert isinstance(ilan_list, list), "Response JSON 'data' field or body is not a list"
        if len(ilan_list) > 0:
            ilan = ilan_list[0]
            assert isinstance(ilan, dict), "Each item in response list should be a dict"
            expected_keys = ["id", "title", "price", "location"]
            assert any(key in ilan for key in expected_keys), "Ilan object missing expected keys"
    except requests.exceptions.RequestException as e:
        assert False, f"Request failed: {e}"

test_get_api_v1_ilanlar_with_valid_pagination()
