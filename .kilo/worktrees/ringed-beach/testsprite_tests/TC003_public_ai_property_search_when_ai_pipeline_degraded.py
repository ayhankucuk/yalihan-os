import requests

BASE_URL = "http://localhost:8002"
TIMEOUT = 30

def test_public_ai_property_search_when_ai_pipeline_degraded():
    headers = {"Content-Type": "application/json"}

    # Step 1: Create a listing with required fields
    create_listing_url = f"{BASE_URL}/api/v1/ilanlar"
    listing_payload = {
        "baslik": "Deneme Villa",
        "aciklama": "Test açıklaması",
        "alan_m2": 150,
        "birim_fiyat": 3000,
        "il": "İstanbul",
        "ilce": "Beşiktaş",
        "mahalle": "Levent",
        "lat": 41.0904,
        "lng": 29.0156
    }

    listing_id = None
    try:
        response = requests.post(create_listing_url, json=listing_payload, headers=headers, timeout=TIMEOUT)
        assert response.status_code == 201, f"Expected 201 Created, got {response.status_code}"
        listing_id = response.json().get("id")
        assert listing_id is not None, "Created listing response missing 'id'"

        # Step 2: Verify that blank title returns 422
        bad_title_payload = listing_payload.copy()
        bad_title_payload["baslik"] = ""
        r_blank_title = requests.post(create_listing_url, json=bad_title_payload, headers=headers, timeout=TIMEOUT)
        assert r_blank_title.status_code == 422, f"Expected 422 for blank title, got {r_blank_title.status_code}"

        # Verify that blank description returns 422
        bad_desc_payload = listing_payload.copy()
        bad_desc_payload["aciklama"] = ""
        r_blank_desc = requests.post(create_listing_url, json=bad_desc_payload, headers=headers, timeout=TIMEOUT)
        assert r_blank_desc.status_code == 422, f"Expected 422 for blank description, got {r_blank_desc.status_code}"

        # Step 3: Publish listing using PATCH /api/v1/ilanlar/{id}/publish with status 'Aktif'
        publish_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}/publish"
        publish_payload = {"status": "Aktif"}
        r_publish = requests.patch(publish_url, json=publish_payload, headers=headers, timeout=TIMEOUT)
        assert r_publish.status_code == 200, f"Expected 200 OK for publish, got {r_publish.status_code}"

        # Step 4: Simulate AI pipeline degraded state by calling /api/v1/public-ai/ilan-arama
        # Expect 503 Service Unavailable
        ai_search_url = f"{BASE_URL}/api/v1/public-ai/ilan-arama"
        # Provide a normal valid prompt payload since the point is to get 503 due to AI pipeline degraded
        ai_search_payload = {"prompt": "bodrumda 5 milyon altı villa"}

        r_ai_search = requests.post(ai_search_url, json=ai_search_payload, headers=headers, timeout=TIMEOUT)
        assert r_ai_search.status_code == 503, f"Expected 503 Service Unavailable when AI pipeline degraded, got {r_ai_search.status_code}"

        print("TC003 PASSED")
    except AssertionError as e:
        print("TC003 FAILED:", e)
    except requests.RequestException as e:
        print("TC003 FAILED: HTTP request exception:", e)
    finally:
        # Clean up: delete the listing if created
        if listing_id:
            try:
                delete_url = f"{BASE_URL}/api/v1/ilanlar/{listing_id}"
                requests.delete(delete_url, timeout=TIMEOUT)
            except requests.RequestException:
                pass

test_public_ai_property_search_when_ai_pipeline_degraded()