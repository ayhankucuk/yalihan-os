import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_auth_login_and_admin_listing_wizard():
    # Step 1: Auth Login with valid credentials
    login_url = f"{BASE_URL}/api/v1/auth/login"
    login_payload = {
        "email": "admin@example.com",
        "password": "validpassword123"
    }
    login_resp = requests.post(login_url, json=login_payload, timeout=TIMEOUT)
    assert login_resp.status_code == 200, f"Login failed with status {login_resp.status_code}"
    login_data = login_resp.json()
    assert "token" in login_data or "access_token" in login_data, "Auth token not found in login response"
    token = login_data.get("token") or login_data.get("access_token")
    headers = {"Authorization": f"Bearer {token}"}

    # Step 2: Create a listing with all required fields
    create_url = f"{BASE_URL}/api/v1/ilanlar"
    listing_payload = {
        "baslik": "Listing Title",
        "aciklama": "This is a description of the listing.",
        "alan_m2": 150,
        "birim_fiyat": 2500,
        "il": "Istanbul",
        "ilce": "Besiktas",
        "mahalle": "Levent",
        "lat": 41.05,
        "lng": 29.03
    }

    listing_resp = requests.post(create_url, json=listing_payload, headers=headers, timeout=TIMEOUT)
    assert listing_resp.status_code == 201, f"Expected 201 Created, got {listing_resp.status_code}"
    listing_data = listing_resp.json()
    listing_id = listing_data.get("id")
    assert listing_id is not None, "Created listing ID not found in response"

    try:
        # Step 3: Verify that blank title returns 422
        invalid_payload_title = listing_payload.copy()
        invalid_payload_title["baslik"] = ""
        resp_blank_title = requests.post(create_url, json=invalid_payload_title, headers=headers, timeout=TIMEOUT)
        assert resp_blank_title.status_code == 422, f"Expected 422 for blank title, got {resp_blank_title.status_code}"

        # Step 4: Verify that blank description returns 422
        invalid_payload_desc = listing_payload.copy()
        invalid_payload_desc["aciklama"] = ""
        resp_blank_desc = requests.post(create_url, json=invalid_payload_desc, headers=headers, timeout=TIMEOUT)
        assert resp_blank_desc.status_code == 422, f"Expected 422 for blank description, got {resp_blank_desc.status_code}"

        # Step 5: Publish listing by PATCH /api/v1/ilanlar/{id}/publish to set status 'Aktif'
        publish_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}/publish"
        publish_payload = {"status": "Aktif"}
        publish_resp = requests.patch(publish_url, json=publish_payload, headers=headers, timeout=TIMEOUT)
        assert publish_resp.status_code == 200, f"Expected 200 OK on publish, got {publish_resp.status_code}"

    finally:
        # Cleanup: Delete the created listing to avoid test pollution
        delete_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}"
        requests.delete(delete_url, headers=headers, timeout=TIMEOUT)

test_auth_login_and_admin_listing_wizard()