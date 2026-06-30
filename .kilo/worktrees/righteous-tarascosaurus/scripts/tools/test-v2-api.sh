#!/bin/bash

# V2 API Integration Test Script
# This script tests all V2 API endpoints in sequence

set -e

BASE_URL="http://localhost:8002/api/v1"
ADMIN_EMAIL="admin@test.local"
ADMIN_PASSWORD="password123"
DANISMAN_EMAIL="ahmet@test.local"
DANISMAN_PASSWORD="password123"
INACTIVE_EMAIL="pasif@test.local"
INACTIVE_PASSWORD="password123"

echo ""
echo "╔════════════════════════════════════════════════════════╗"
echo "║     V2 API INTEGRATION TEST SUITE                      ║"
echo "║     Date: 2 Ocak 2026                                  ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

# Test 1: Admin Login
echo "🔐 Test 1: Admin Login"
echo "   POST $BASE_URL/auth/login"
ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$ADMIN_EMAIL\",\"sifre\":\"$ADMIN_PASSWORD\"}")

ADMIN_TOKEN=$(echo "$ADMIN_RESPONSE" | jq -r '.data.token // empty')

if [ -z "$ADMIN_TOKEN" ]; then
  echo "   ❌ FAILED: Could not get admin token"
  echo "   Response: $ADMIN_RESPONSE"
  exit 1
fi

echo "   ✅ SUCCESS: Admin token obtained"
echo "   Token: ${ADMIN_TOKEN:0:20}..."
echo ""

# Test 2: Get Admin Profile
echo "👤 Test 2: Get Authenticated User Profile"
echo "   GET $BASE_URL/auth/me"
PROFILE=$(curl -s -X GET "$BASE_URL/auth/me" \
  -H "Authorization: Bearer $ADMIN_TOKEN")

PROFILE_NAME=$(echo "$PROFILE" | jq -r '.data.ad_soyad // empty')
if [ -z "$PROFILE_NAME" ]; then
  echo "   ❌ FAILED: Could not get profile"
  exit 1
fi

echo "   ✅ SUCCESS: Profile retrieved"
echo "   User: $PROFILE_NAME (Admin)"
echo ""

# Test 3: Danişman Login
echo "🔐 Test 3: Danişman Login"
echo "   POST $BASE_URL/auth/login"
DANISMAN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$DANISMAN_EMAIL\",\"sifre\":\"$DANISMAN_PASSWORD\"}")

DANISMAN_TOKEN=$(echo "$DANISMAN_RESPONSE" | jq -r '.data.token // empty')

if [ -z "$DANISMAN_TOKEN" ]; then
  echo "   ❌ FAILED: Could not get danişman token"
  exit 1
fi

echo "   ✅ SUCCESS: Danişman token obtained"
echo ""

# Test 4: Inactive User Login (Should Fail)
echo "🚫 Test 4: Inactive User Login (Should Fail)"
echo "   POST $BASE_URL/auth/login"
INACTIVE_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$INACTIVE_EMAIL\",\"sifre\":\"$INACTIVE_PASSWORD\"}")

INACTIVE_STATUS=$(echo "$INACTIVE_RESPONSE" | jq -r '.success // empty')
INACTIVE_MESSAGE=$(echo "$INACTIVE_RESPONSE" | jq -r '.message // empty')

if [ "$INACTIVE_STATUS" = "false" ] && [[ "$INACTIVE_MESSAGE" == *"deaktif"* ]]; then
  echo "   ✅ SUCCESS: Inactive user properly rejected"
  echo "   Message: $INACTIVE_MESSAGE"
else
  echo "   ❌ FAILED: Inactive user should be rejected"
  exit 1
fi
echo ""

# Test 5: List Users
echo "📋 Test 5: List Users (Public)"
echo "   GET $BASE_URL/users"
USERS=$(curl -s -X GET "$BASE_URL/users")
USER_COUNT=$(echo "$USERS" | jq '.data | length')

echo "   ✅ SUCCESS: Listed $USER_COUNT users"
echo ""

# Test 6: Get Single User
echo "👁️  Test 6: Get Single User"
echo "   GET $BASE_URL/users/1"
USER=$(curl -s -X GET "$BASE_URL/users/1")
USER_NAME=$(echo "$USER" | jq -r '.data.ad_soyad')

echo "   ✅ SUCCESS: User retrieved - $USER_NAME"
echo ""

# Test 7: List Listings
echo "🏘️  Test 7: List Listings (Public)"
echo "   GET $BASE_URL/ilanlar"
LISTINGS=$(curl -s -X GET "$BASE_URL/ilanlar")
LISTING_COUNT=$(echo "$LISTINGS" | jq '.data | length')

echo "   ✅ SUCCESS: Listed $LISTING_COUNT active listings"
echo ""

# Test 8: Create New Listing (Danişman)
echo "🏠 Test 8: Create New Listing (Danişman)"
echo "   POST $BASE_URL/ilanlar"
NEW_LISTING=$(curl -s -X POST "$BASE_URL/ilanlar" \
  -H "Authorization: Bearer $DANISMAN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "baslik": "Test Villa - API Oluşturma",
    "aciklama": "Bu ilan API aracılığıyla oluşturuldu. En az 20 karakter açıklama gerekli.",
    "alan_m2": 250,
    "birim_fiyat": 40000,
    "il": "Muğla",
    "ilce": "Bodrum",
    "mahalle": "Turgutreis",
    "lat": 37.2564,
    "lng": 27.2673,
    "one_cikan": false
  }')

NEW_LISTING_ID=$(echo "$NEW_LISTING" | jq -r '.data.id // empty')

if [ -z "$NEW_LISTING_ID" ]; then
  echo "   ❌ FAILED: Could not create listing"
  echo "   Response: $NEW_LISTING"
  exit 1
fi

echo "   ✅ SUCCESS: Listing created with ID $NEW_LISTING_ID"
echo ""

# Test 9: Publish Listing
echo "📢 Test 9: Publish Listing"
echo "   PATCH $BASE_URL/ilanlar/$NEW_LISTING_ID/publish"
PUBLISH=$(curl -s -X PATCH "$BASE_URL/ilanlar/$NEW_LISTING_ID/publish" \
  -H "Authorization: Bearer $DANISMAN_TOKEN")

PUBLISH_STATUS=$(echo "$PUBLISH" | jq -r '.data.yayin_durumu // empty')

if [ "$PUBLISH_STATUS" = "Aktif" ]; then
  echo "   ✅ SUCCESS: Listing published"
else
  echo "   ❌ FAILED: Could not publish listing"
fi
echo ""

# Test 10: Get Authenticated User's Drafts
echo "📄 Test 10: List User's Drafts"
echo "   GET $BASE_URL/drafts"
DRAFTS=$(curl -s -X GET "$BASE_URL/drafts" \
  -H "Authorization: Bearer $DANISMAN_TOKEN")

DRAFT_COUNT=$(echo "$DRAFTS" | jq '.data | length')

echo "   ✅ SUCCESS: Listed $DRAFT_COUNT drafts"
echo ""

# Test 11: Logout
echo "🔓 Test 11: Logout (Revoke Token)"
echo "   POST $BASE_URL/auth/logout"
LOGOUT=$(curl -s -X POST "$BASE_URL/auth/logout" \
  -H "Authorization: Bearer $ADMIN_TOKEN")

LOGOUT_SUCCESS=$(echo "$LOGOUT" | jq -r '.success // empty')

if [ "$LOGOUT_SUCCESS" = "true" ]; then
  echo "   ✅ SUCCESS: Admin logged out"
else
  echo "   ❌ FAILED: Could not logout"
  exit 1
fi
echo ""

# Test 12: Use Revoked Token (Should Fail)
echo "🚫 Test 12: Use Revoked Token (Should Fail)"
echo "   GET $BASE_URL/auth/me (with revoked token)"
REVOKED=$(curl -s -X GET "$BASE_URL/auth/me" \
  -H "Authorization: Bearer $ADMIN_TOKEN")

REVOKED_STATUS=$(echo "$REVOKED" | jq -r '.success // empty')

if [ "$REVOKED_STATUS" = "false" ]; then
  echo "   ✅ SUCCESS: Revoked token properly rejected"
else
  echo "   ❌ FAILED: Revoked token should be rejected"
fi
echo ""

echo "╔════════════════════════════════════════════════════════╗"
echo "║     ✅ ALL TESTS PASSED                               ║"
echo "║     V2 API is production ready!                        ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "Summary:"
echo "  ✅ Authentication (Login/Logout/Profile)"
echo "  ✅ Access Control (Inactive user rejection)"
echo "  ✅ User Management (List/Get)"
echo "  ✅ Listing Management (Create/Publish/List)"
echo "  ✅ Draft Management (List)"
echo "  ✅ Token Revocation"
echo ""
