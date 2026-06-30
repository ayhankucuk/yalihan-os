import requests


def test_phase7_admin_listing_wizard():
    base_url = "http://localhost:8002"
    headers = {"Content-Type": "application/json"}
    timeout = 30

    # Listing data for creation
    listing_data = {
        "baslik": "Test Title",
        "aciklama": "Test Description",
        "alan_m2": 100,
        "birim_fiyat": 5000,
        "il": "Istanbul",
        "ilce": "Kadikoy",
        "mahalle": "Acibadem",
        "lat": 40.989,
        "lng": 29.067
    }

    # Step 1 & 3: Create a listing
    try:
        create_resp = requests.post(
            f"{base_url}/api/v1/ilanlar",
            headers=headers,
            json=listing_data,
            timeout=timeout
        )
        assert create_resp.status_code == 201, f"Expected 201 Created, got {create_resp.status_code}"
        created_listing = create_resp.json()
        listing_id = created_listing.get("id")
        assert listing_id, "Created listing ID missing"
    except Exception as e:
        raise AssertionError(f"Failed to create listing: {e}")

    try:
        # Step 4: Verify blank title returns 422
        invalid_data_title = listing_data.copy()
        invalid_data_title["baslik"] = ""
        resp_blank_title = requests.post(
            f"{base_url}/api/v1/ilanlar",
            headers=headers,
            json=invalid_data_title,
            timeout=timeout
        )
        assert resp_blank_title.status_code == 422, f"Expected 422 for blank title, got {resp_blank_title.status_code}"

        # Verify blank description returns 422
        invalid_data_desc = listing_data.copy()
        invalid_data_desc["aciklama"] = ""
        resp_blank_desc = requests.post(
            f"{base_url}/api/v1/ilanlar",
            headers=headers,
            json=invalid_data_desc,
            timeout=timeout
        )
        assert resp_blank_desc.status_code == 422, f"Expected 422 for blank description, got {resp_blank_desc.status_code}"

        # Step 5 & 6: Publish the listing by PATCH /api/v1/ilanlar/{id}/publish with status 'Aktif'
        patch_payload = {"status": "Aktif"}
        patch_resp = requests.patch(
            f"{base_url}/api/v1/ilanlar/{listing_id}/publish",
            headers=headers,
            json=patch_payload,
            timeout=timeout
        )
        assert patch_resp.status_code == 200, f"Expected 200 OK for publish, got {patch_resp.status_code}"

    finally:
        # Clean up: delete the created listing
        try:
            requests.delete(
                f"{base_url}/api/v1/ilanlar/{listing_id}",
                headers=headers,
                timeout=timeout
            )
        except:
            pass


def test_auth_login_with_invalid_credentials():
    base_url = "http://localhost:8002"
    headers = {"Content-Type": "application/json"}
    timeout = 30

    login_data = {
        "email": "invalid@example.com",
        "password": "wrongpassword"
    }

    resp = requests.post(
        f"{base_url}/api/v1/auth/login",
        headers=headers,
        json=login_data,
        timeout=timeout
    )
    assert resp.status_code == 401, f"Expected 401 Unauthorized, got {resp.status_code}"
    resp_json = resp.json()
    assert "error" in resp_json or "message" in resp_json, "Expected error message in response"


# Execute the test function as requested
test_auth_login_with_invalid_credentials()