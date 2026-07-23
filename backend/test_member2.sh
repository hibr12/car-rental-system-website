#!/bin/bash

# Member 2 API Test Script
# Run this after starting the server: php artisan serve

BASE_URL="http://localhost:8000/api"

echo "========================================="
echo "Member 2 API Testing Script"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
PASSED=0
FAILED=0

# Function to test API endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local token=$4
    local expected_code=$5
    local description=$6

    echo -e "${YELLOW}Testing: ${description}${NC}"

    if [ -n "$token" ]; then
        if [ -n "$data" ]; then
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
                -H "Authorization: Bearer $token" \
                -H "Content-Type: application/json" \
                -d "$data")
        else
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
                -H "Authorization: Bearer $token")
        fi
    else
        if [ -n "$data" ]; then
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
                -H "Content-Type: application/json" \
                -d "$data")
        else
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint")
        fi
    fi

    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)

    if [ "$http_code" -eq "$expected_code" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $http_code)"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_code, got $http_code)"
        echo "Response: $body"
        FAILED=$((FAILED + 1))
    fi
    echo ""
}

# ==========================================
# 1. Authentication Tests
# ==========================================
echo "========================================="
echo "1. AUTHENTICATION"
echo "========================================="

# Register Admin
test_endpoint "POST" "/auth/register" \
    '{"name":"Test Admin","email":"testadmin@test.com","password":"password","phone":"1234567890","role":"admin"}' \
    "" 201 "Register Admin User"

# Register Customer
test_endpoint "POST" "/auth/register" \
    '{"name":"Test Customer","email":"testcustomer@test.com","password":"password","phone":"0987654321","role":"customer"}' \
    "" 201 "Register Customer User"

# Login Admin
echo -e "${YELLOW}Logging in as Admin...${NC}"
ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"testadmin@test.com","password":"password"}')
ADMIN_TOKEN=$(echo $ADMIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo -e "${GREEN}Admin Token: ${ADMIN_TOKEN:0:20}...${NC}"
echo ""

# Login Customer
echo -e "${YELLOW}Logging in as Customer...${NC}"
CUSTOMER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"testcustomer@test.com","password":"password"}')
CUSTOMER_TOKEN=$(echo $CUSTOMER_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo -e "${GREEN}Customer Token: ${CUSTOMER_TOKEN:0:20}...${NC}"
echo ""

# ==========================================
# 2. Vehicle Tests
# ==========================================
echo "========================================="
echo "2. VEHICLE CRUD"
echo "========================================="

# Create Category (needed for vehicle)
echo -e "${YELLOW}Creating category...${NC}"
CATEGORY_RESPONSE=$(curl -s -X POST "$BASE_URL/categories" \
    -H "Authorization: Bearer $ADMIN_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"name":"Sedan","description":"Sedan vehicles"}')
CATEGORY_ID=$(echo $CATEGORY_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo -e "${GREEN}Category ID: $CATEGORY_ID${NC}"
echo ""

# Create Vehicle
test_endpoint "POST" "/vehicles" \
    "{\"category_id\":$CATEGORY_ID,\"brand\":\"Toyota\",\"model\":\"Camry\",\"year\":2024,\"registration_number\":\"TEST-001\",\"fuel_type\":\"petrol\",\"transmission\":\"automatic\",\"seats\":5,\"rental_price_per_day\":50,\"status\":\"available\"}" \
    "$ADMIN_TOKEN" 201 "Create Vehicle"

# View All Vehicles
test_endpoint "GET" "/vehicles" "" "" 200 "View All Vehicles"

# View Vehicle Details
test_endpoint "GET" "/vehicles/1" "" "" 200 "View Vehicle Details"

# Update Vehicle
test_endpoint "PUT" "/vehicles/1" \
    '{"rental_price_per_day":55}' \
    "$ADMIN_TOKEN" 200 "Update Vehicle"

# ==========================================
# 3. Booking Tests
# ==========================================
echo "========================================="
echo "3. BOOKING CRUD"
echo "========================================="

# Create Booking
test_endpoint "POST" "/bookings" \
    '{"vehicle_id":1,"pickup_location":"Airport","return_location":"Airport","pickup_date":"2026-08-01 10:00:00","return_date":"2026-08-05 10:00:00"}' \
    "$CUSTOMER_TOKEN" 201 "Create Booking"

# View Customer Bookings
test_endpoint "GET" "/bookings" "" "$CUSTOMER_TOKEN" 200 "View Customer Bookings"

# View Booking Details
test_endpoint "GET" "/bookings/1" "" "$CUSTOMER_TOKEN" 200 "View Booking Details"

# Confirm Booking (Admin)
test_endpoint "PUT" "/admin/bookings/1/confirm" "" "$ADMIN_TOKEN" 200 "Confirm Booking"

# View Admin Bookings
test_endpoint "GET" "/admin/bookings" "" "$ADMIN_TOKEN" 200 "View All Bookings (Admin)"

# ==========================================
# 4. Payment Tests
# ==========================================
echo "========================================="
echo "4. PAYMENT CRUD"
echo "========================================="

# Create Payment
test_endpoint "POST" "/payments" \
    '{"booking_id":1,"payment_method":"card","transaction_reference":"TXN-TEST-001"}' \
    "$CUSTOMER_TOKEN" 201 "Create Payment"

# View Payment History
test_endpoint "GET" "/payments" "" "$CUSTOMER_TOKEN" 200 "View Payment History"

# View Payment Details
test_endpoint "GET" "/payments/1" "" "$CUSTOMER_TOKEN" 200 "View Payment Details"

# ==========================================
# 5. Booking Completion (for Review)
# ==========================================
echo "========================================="
echo "5. BOOKING COMPLETION"
echo "========================================="

# Pickup Booking
test_endpoint "PUT" "/admin/bookings/1/pickup" "" "$ADMIN_TOKEN" 200 "Pickup Vehicle"

# Return Vehicle
test_endpoint "PUT" "/admin/bookings/1/return" "" "$ADMIN_TOKEN" 200 "Return Vehicle"

# ==========================================
# 6. Review Tests
# ==========================================
echo "========================================="
echo "6. REVIEW CRUD"
echo "========================================="

# Create Review
test_endpoint "POST" "/vehicles/1/reviews" \
    '{"booking_id":1,"rating":5,"comment":"Great car!"}' \
    "$CUSTOMER_TOKEN" 201 "Create Review"

# View Reviews
test_endpoint "GET" "/vehicles/1/reviews" "" "" 200 "View Vehicle Reviews"

# Delete Review
test_endpoint "DELETE" "/reviews/1" "" "$CUSTOMER_TOKEN" 200 "Delete Review"

# ==========================================
# Summary
# ==========================================
echo "========================================="
echo "TEST SUMMARY"
echo "========================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
else
    echo -e "${RED}Some tests failed. Please check the output above.${NC}"
fi
echo ""
