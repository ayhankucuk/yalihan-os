import requests
import sys

BASE_URL = "http://localhost:8002"

endpoints = [
    "/api/v1/health",
    "/api/v1/health/simple",
    "/api/v1/health/detailed",
    "/api/v1/ai/health"
]

def test_health():
    print("--- SAB HEALTH AUDIT ---")
    all_passed = True
    for ep in endpoints:
        url = BASE_URL + ep
        try:
            resp = requests.get(url, timeout=5)
            status = "PASS" if resp.status_code == 200 else "FAIL"
            print(f"[{status}] {ep} -> {resp.status_code}")
            if resp.status_code != 200:
                all_passed = False
            if ep == "/api/v1/ai/health":
                data = resp.json()
                print(f"   AI Status: {data.get('status')} | Queue: {data.get('queue')}")
        except Exception as e:
            print(f"[ERROR] {ep} -> {str(e)}")
            all_passed = False
    
    if not all_passed:
        sys.exit(1)

if __name__ == "__main__":
    test_health()
