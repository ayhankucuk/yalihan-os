import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_get_api_advisor_listings_ilan_buyer_matches_without_auth():
    # For testing unauthorized access, we need a valid ilan id.
    # Since no ID was provided, we first create a listing, then use its ID.
    # After test, delete the created listing.

    ilan_id = None
    headers = {
        "Content-Type": "application/json"
    }
    try:
        # Create a new listing via public endpoint (since no auth mentioned for creation, will try public v2 listing POST. 
        # But PRD does not specify listing creation API. So fallback: request GET on /api/v1/v2-ilanlar to get a listing id.
        # If no listing present, the test cannot continue.
        resp_listings = requests.get(f"{BASE_URL}/api/v1/v2-ilanlar", headers=headers, timeout=TIMEOUT)
        resp_listings.raise_for_status()
        listings = resp_listings.json()
        if not isinstance(listings, list) or len(listings) == 0:
            raise Exception("No listings available to test buyer matches endpoint without auth.")
        ilan_id = listings[0].get("id")
        if not ilan_id:
            raise Exception("Listing from v2-ilanlar does not have an 'id' field.")
        
        # Now, call the buyer-matches endpoint without Authorization header
        url = f"{BASE_URL}/api/advisor/listings/{ilan_id}/buyer-matches"
        response = requests.get(url, headers=headers, timeout=TIMEOUT)

        # Validate 401 Unauthorized response
        assert response.status_code == 401, f"Expected 401 Unauthorized, got {response.status_code}"
        
        # Further validate JSON response contains an error regarding unauthorized access
        try:
            resp_json = response.json()
        except Exception:
            resp_json = None
        assert resp_json is not None, "Response is not a valid JSON"
        
        # Commonly, unauthorized errors have keys like detail, error, message
        error_keys = {"detail", "error", "message"}
        assert any(key in resp_json for key in error_keys), "Unauthorized response JSON does not contain error detail"
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

test_get_api_advisor_listings_ilan_buyer_matches_without_auth()