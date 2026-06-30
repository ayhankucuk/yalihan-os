import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

# Credentials for advisor authentication - replace with valid test user credentials
ADVISOR_EMAIL = "admin@example.com"
ADVISOR_PASSWORD = "AdminPass123!"

def test_buyer_matches_for_valid_listing_with_auth():
    # Step 1: Authenticate advisor to get Authorization token
    login_url = f"{BASE_URL}/api/v1/auth/login"
    login_payload = {
        "email": ADVISOR_EMAIL,
        "password": ADVISOR_PASSWORD
    }
    try:
        login_resp = requests.post(login_url, json=login_payload, timeout=TIMEOUT)
        assert login_resp.status_code == 200, f"Login failed: {login_resp.status_code}, {login_resp.text}"
        token = login_resp.json().get("token")
        assert token, "Auth token missing in login response"
    except Exception as e:
        assert False, f"Authentication request failed: {e}"

    headers_auth = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    # Variables for listing creation
    create_listing_url = f"{BASE_URL}/api/v1/ilanlar"
    publish_listing_url_template = f"{BASE_URL}/api/v1/ilanlar/{{}}/publish"

    listing_payload_valid = {
        "baslik": "Deneme Listing Başlık",
        "aciklama": "Deneme açıklama for listing created by test",
        "alan_m2": 100,
        "birim_fiyat": 2000,
        "il": "Istanbul",
        "ilce": "Kadikoy",
        "mahalle": "Fenerbahce",
        "lat": 40.9794,
        "lng": 29.1392
    }

    listing_payload_blank_baslik = listing_payload_valid.copy()
    listing_payload_blank_baslik["baslik"] = ""

    listing_payload_blank_aciklama = listing_payload_valid.copy()
    listing_payload_blank_aciklama["aciklama"] = ""

    listing_id = None
    pass_report = {
        "create_listing_valid": False,
        "create_listing_blank_title_422": False,
        "create_listing_blank_description_422": False,
        "publish_listing": False
    }

    try:
        # Step 2 & 3: Create valid listing and verify 201 Created
        create_resp = requests.post(create_listing_url, json=listing_payload_valid, timeout=TIMEOUT)
        pass_report["create_listing_valid"] = (create_resp.status_code == 201)
        assert pass_report["create_listing_valid"], f"Expected 201 Created but got {create_resp.status_code}"

        listing_id = create_resp.json().get("id")
        assert listing_id is not None, "Listing ID missing in creation response"

        # Step 4: Verify blank title returns 422
        resp_blank_title = requests.post(create_listing_url, json=listing_payload_blank_baslik, timeout=TIMEOUT)
        pass_report["create_listing_blank_title_422"] = (resp_blank_title.status_code == 422)
        assert pass_report["create_listing_blank_title_422"], f"Expected 422 for blank title but got {resp_blank_title.status_code}"

        # Verify blank description returns 422
        resp_blank_desc = requests.post(create_listing_url, json=listing_payload_blank_aciklama, timeout=TIMEOUT)
        pass_report["create_listing_blank_description_422"] = (resp_blank_desc.status_code == 422)
        assert pass_report["create_listing_blank_description_422"], f"Expected 422 for blank description but got {resp_blank_desc.status_code}"

        # Step 5 & 6: Publish the created listing (PATCH /api/v1/ilanlar/{id}/publish) to 'Aktif'
        publish_url = publish_listing_url_template.format(listing_id)
        publish_payload = {"status": "Aktif"}
        publish_resp = requests.patch(publish_url, json=publish_payload, timeout=TIMEOUT)
        pass_report["publish_listing"] = (publish_resp.status_code == 200)
        assert pass_report["publish_listing"], f"Expected 200 OK on publish but got {publish_resp.status_code}"

        # Step 7: Test GET /api/advisor/listings/{id}/buyer-matches with valid auth and listing id
        buyer_matches_url = f"{BASE_URL}/api/advisor/listings/{listing_id}/buyer-matches"
        buyer_matches_resp = requests.get(buyer_matches_url, headers=headers_auth, timeout=TIMEOUT)
        assert buyer_matches_resp.status_code == 200, f"Expected 200 OK for buyer matches but got {buyer_matches_resp.status_code}"

        resp_json = buyer_matches_resp.json()
        assert isinstance(resp_json, list), f"Expected buyer matches response to be a list but got {type(resp_json)}"

        # Each item should include buyer match info and match scores (basic validation)
        for match in resp_json:
            assert "buyer_id" in match or "id" in match, "buyer_id or id expected in buyer match item"
            assert "match_score" in match, "match_score missing in buyer match item"
            score = match.get("match_score")
            assert isinstance(score, (int, float)) and 0 <= score <= 100, "match_score should be number between 0 and 100"

        # If all assertions passed
        print("Test TC008 PASSED:", pass_report)

    finally:
        # Clean up: delete the created listing if exists
        if listing_id is not None:
            try:
                delete_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}"
                del_resp = requests.delete(delete_url, headers=headers_auth, timeout=TIMEOUT)
                # Accept 200 OK or 204 No Content as success for delete
                if del_resp.status_code not in (200, 204, 404):
                    print(f"Warning: unexpected response deleting listing {listing_id}: {del_resp.status_code}")
            except Exception as e:
                print(f"Error during cleanup deleting listing {listing_id}: {e}")

test_buyer_matches_for_valid_listing_with_auth()