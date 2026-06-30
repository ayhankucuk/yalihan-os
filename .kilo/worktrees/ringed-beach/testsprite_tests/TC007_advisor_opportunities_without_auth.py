import requests

BASE_URL = "http://localhost:8002"


def test_advisor_opportunities_without_auth():
    timeout = 30

    # Phase 7: Admin Listing Wizard Tests

    # Sample valid listing data
    listing_data = {
        "baslik": "Test Listing Title",
        "aciklama": "Test Listing Description",
        "alan_m2": 120,
        "birim_fiyat": 2500,
        "il": "İstanbul",
        "ilce": "Kadıköy",
        "mahalle": "Moda",
        "lat": 40.98765,
        "lng": 29.0467
    }

    headers = {"Content-Type": "application/json"}

    listing_id = None
    try:
        # 1. Create a listing (POST /api/v1/ilanlar)
        create_resp = requests.post(
            f"{BASE_URL}/api/v1/ilanlar", json=listing_data, headers=headers, timeout=timeout
        )
        assert create_resp.status_code == 201, f"Expected 201 Created, got {create_resp.status_code}"
        listing_resp_json = create_resp.json()
        listing_id = listing_resp_json.get("id")
        assert listing_id is not None, "Listing ID not returned in response"

        # 2. Verify blank title returns 422
        invalid_data_title = listing_data.copy()
        invalid_data_title["baslik"] = ""
        resp_invalid_title = requests.post(
            f"{BASE_URL}/api/v1/ilanlar", json=invalid_data_title, headers=headers, timeout=timeout
        )
        assert resp_invalid_title.status_code == 422, f"Expected 422 for blank title, got {resp_invalid_title.status_code}"

        # 3. Verify blank description returns 422
        invalid_data_desc = listing_data.copy()
        invalid_data_desc["aciklama"] = ""
        resp_invalid_desc = requests.post(
            f"{BASE_URL}/api/v1/ilanlar", json=invalid_data_desc, headers=headers, timeout=timeout
        )
        assert resp_invalid_desc.status_code == 422, f"Expected 422 for blank description, got {resp_invalid_desc.status_code}"

        # 4. Publish the listing (PATCH /api/v1/ilanlar/{id}/publish) with status 'Aktif'
        publish_data = {"status": "Aktif"}
        patch_resp = requests.patch(
            f"{BASE_URL}/api/v1/ilanlar/{listing_id}/publish",
            json=publish_data,
            headers=headers,
            timeout=timeout,
        )
        assert patch_resp.status_code == 200, f"Expected 200 OK on publish, got {patch_resp.status_code}"

        # 5. Test GET /api/advisor/opportunities without Authorization header
        resp_no_auth = requests.get(f"{BASE_URL}/api/advisor/opportunities", timeout=timeout)
        assert resp_no_auth.status_code == 401, f"Expected 401 Unauthorized without auth, got {resp_no_auth.status_code}"

        # 6. Test GET /api/advisor/opportunities with invalid Authorization header
        invalid_headers = {"Authorization": "Bearer invalidtoken"}
        resp_invalid_auth = requests.get(
            f"{BASE_URL}/api/advisor/opportunities", headers=invalid_headers, timeout=timeout
        )
        assert resp_invalid_auth.status_code == 401, f"Expected 401 Unauthorized with invalid auth, got {resp_invalid_auth.status_code}"

        print("TC007 advisor-opportunities-without-auth PASSED")

    except AssertionError as ae:
        print(f"TC007 advisor-opportunities-without-auth FAILED: {ae}")

    except requests.RequestException as e:
        print(f"TC007 advisor-opportunities-without-auth FAILED: RequestException: {e}")

    finally:
        if listing_id:
            try:
                requests.delete(f"{BASE_URL}/api/v1/ilanlar/{listing_id}", timeout=timeout)
            except Exception:
                pass


test_advisor_opportunities_without_auth()