import requests
import sys

BASE_URL = "http://localhost:8002"
LOGIN_URL = f"{BASE_URL}/api/v1/auth/login"
ADVISOR_OPP_URL = f"{BASE_URL}/api/advisor/opportunities"

def test_advisor():
    print("--- SAB ADVISOR AUDIT ---")
    # 1. Login
    login_payload = {"email":"ayhankucuk@gmail.com", "sifre":"123456"}
    headers = {"Content-Type": "application/json", "Accept": "application/json"}
    login_resp = requests.post(LOGIN_URL, json=login_payload, headers=headers)
    
    if login_resp.status_code != 200:
        print(f"[FAIL] Login failed: {login_resp.status_code}")
        sys.exit(1)
        
    token = login_resp.json().get("data", {}).get("token")
    if not token:
        print("[FAIL] Token not found in login response")
        sys.exit(1)
        
    # 2. Access Advisor Opportunities
    auth_headers = {"Authorization": f"Bearer {token}", "Accept": "application/json"}
    opp_resp = requests.get(ADVISOR_OPP_URL, headers=auth_headers)
    
    status = "PASS" if opp_resp.status_code == 200 else "FAIL"
    print(f"[{status}] GET /api/advisor/opportunities -> {opp_resp.status_code}")
    
    if opp_resp.status_code != 200:
        print(f"Response: {opp_resp.text}")
        sys.exit(1)
    
    print("[PASS] Advisor Opportunities resolved.")

if __name__ == "__main__":
    test_advisor()
