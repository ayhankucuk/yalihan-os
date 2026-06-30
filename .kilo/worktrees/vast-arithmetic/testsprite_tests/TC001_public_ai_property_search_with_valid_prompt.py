import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_public_ai_property_search_with_valid_prompt():
    headers = {"Content-Type": "application/json"}
    created_listing_id = None
    try:
        # 1. Create a listing with mandatory fields
        listing_payload = {
            "baslik": "Deniz Manzaralı Villa",
            "aciklama": "Geniş ve lüks villa, deniz manzaralı, 150 m2",
            "alan_m2": 150,
            "birim_fiyat": 25000,
            "il": "Muğla",
            "ilce": "Bodrum",
            "mahalle": "Yalı",
            "lat": 37.0344,
            "lng": 27.4300
        }
        create_response = requests.post(
            f"{BASE_URL}/api/v1/ilanlar", json=listing_payload, headers=headers, timeout=TIMEOUT
        )
        assert create_response.status_code == 201, f"Expected 201 Created, got {create_response.status_code}"
        created_listing_id = create_response.json().get("id")
        assert created_listing_id is not None, "Created listing ID not returned in response"

        # 2. Verify that blank title returns 422
        blank_title_payload = listing_payload.copy()
        blank_title_payload["baslik"] = ""
        blank_title_resp = requests.post(
            f"{BASE_URL}/api/v1/ilanlar", json=blank_title_payload, headers=headers, timeout=TIMEOUT
        )
        assert blank_title_resp.status_code == 422, f"Expected 422 for blank title, got {blank_title_resp.status_code}"

        # 3. Verify that blank description returns 422
        blank_description_payload = listing_payload.copy()
        blank_description_payload["aciklama"] = ""
        blank_description_resp = requests.post(
            f"{BASE_URL}/api/v1/ilanlar", json=blank_description_payload, headers=headers, timeout=TIMEOUT
        )
        assert blank_description_resp.status_code == 422, f"Expected 422 for blank description, got {blank_description_resp.status_code}"

        # 4. Publish the created listing by PATCH /api/v1/ilanlar/{id}/publish with status 'Aktif'
        publish_payload = {"durum": "Aktif"}
        publish_response = requests.patch(
            f"{BASE_URL}/api/v1/ilanlar/{created_listing_id}/publish",
            json=publish_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        assert publish_response.status_code == 200, f"Expected 200 OK on publish, got {publish_response.status_code}"

        # 5. Call the public AI search endpoint with a valid natural-language prompt
        ai_search_payload = {
            "prompt": "bodrumda 5 milyon altı villa"
        }
        ai_search_resp = requests.post(
            f"{BASE_URL}/api/v1/public-ai/ilan-arama",
            json=ai_search_payload,
            headers=headers,
            timeout=TIMEOUT
        )
        assert ai_search_resp.status_code == 200, f"Expected 200 OK from AI search, got {ai_search_resp.status_code}"

        data = ai_search_resp.json()
        assert isinstance(data, list), "AI search result is not a list"
        for listing in data:
            assert "id" in listing, "Listing missing 'id' field"
            assert "price" in listing, "Listing missing 'price' field"
            assert "location" in listing, "Listing missing 'location' field"

        print("PASS: public-ai-property-search-with-valid-prompt")
    except AssertionError as e:
        print(f"FAIL: public-ai-property-search-with-valid-prompt - {e}")
    except Exception as e:
        print(f"FAIL: public-ai-property-search-with-valid-prompt - Unexpected error: {e}")
    finally:
        if created_listing_id:
            try:
                requests.delete(f"{BASE_URL}/api/v1/ilanlar/{created_listing_id}", timeout=TIMEOUT)
            except Exception:
                pass

test_public_ai_property_search_with_valid_prompt()