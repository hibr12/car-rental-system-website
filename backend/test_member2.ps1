$BASE_URL = "http://127.0.0.1:8000/api"
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Member 2 API Testing Script (PowerShell)" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

$passed = 0
$failed = 0

function Test-Endpoint {
    param(
        [string]$Method,
        [string]$Endpoint,
        [string]$Body = $null,
        [string]$Token = $null,
        [int]$ExpectedCode,
        [string]$Description
    )

    Write-Host "Testing: $Description" -ForegroundColor Yellow

    $url = "$BASE_URL$Endpoint"
    $requestHeaders = $headers.Clone()

    if ($Token) {
        $requestHeaders["Authorization"] = "Bearer $Token"
    }

    try {
        $params = @{
            Uri = $url
            Method = $Method
            Headers = $requestHeaders
            UseBasicParsing = $true
        }

        if ($Body) {
            $params.Body = $Body
        }

        $response = Invoke-WebRequest @params
        $statusCode = $response.StatusCode
        $content = $response.Content

        if ($statusCode -eq $ExpectedCode) {
            Write-Host "  PASS (HTTP $statusCode)" -ForegroundColor Green
            $script:passed++
        } else {
            Write-Host "  FAIL (Expected $ExpectedCode, got $statusCode)" -ForegroundColor Red
            Write-Host "  Response: $content" -ForegroundColor Red
            $script:failed++
        }
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "  FAIL (Expected $ExpectedCode, got $statusCode)" -ForegroundColor Red
        Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
        $script:failed++
    }
    Write-Host ""
}

# ==========================================
# 1. AUTHENTICATION
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "1. AUTHENTICATION" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

# Register Admin
Test-Endpoint -Method "POST" -Endpoint "/auth/register" `
    -Body '{"name":"Test Admin","email":"testadmin2@test.com","password":"password","phone":"1234567890"}' `
    -ExpectedCode 201 -Description "Register Admin User"

# Register Customer
Test-Endpoint -Method "POST" -Endpoint "/auth/register" `
    -Body '{"name":"Test Customer","email":"testcustomer2@test.com","password":"password","phone":"0987654321"}' `
    -ExpectedCode 201 -Description "Register Customer User"

# Login Admin
Write-Host "Logging in as Admin..." -ForegroundColor Yellow
try {
    $adminResponse = Invoke-RestMethod -Uri "$BASE_URL/auth/login" -Method POST -Headers $headers -Body '{"email":"testadmin2@test.com","password":"password"}' -UseBasicParsing
    $adminToken = $adminResponse.token
    Write-Host "Admin Token: $($adminToken.Substring(0, [Math]::Min(20, $adminToken.Length)))..." -ForegroundColor Green
} catch {
    # Try with existing admin
    try {
        $adminResponse = Invoke-RestMethod -Uri "$BASE_URL/auth/login" -Method POST -Headers $headers -Body '{"email":"admin@carrental.com","password":"password"}' -UseBasicParsing
        $adminToken = $adminResponse.token
        Write-Host "Admin Token: $($adminToken.Substring(0, [Math]::Min(20, $adminToken.Length)))..." -ForegroundColor Green
    } catch {
        Write-Host "Failed to login admin" -ForegroundColor Red
        $adminToken = $null
    }
}
Write-Host ""

# Login Customer
Write-Host "Logging in as Customer..." -ForegroundColor Yellow
try {
    $customerResponse = Invoke-RestMethod -Uri "$BASE_URL/auth/login" -Method POST -Headers $headers -Body '{"email":"testcustomer2@test.com","password":"password"}' -UseBasicParsing
    $customerToken = $customerResponse.token
    Write-Host "Customer Token: $($customerToken.Substring(0, [Math]::Min(20, $customerToken.Length)))..." -ForegroundColor Green
} catch {
    try {
        $customerResponse = Invoke-RestMethod -Uri "$BASE_URL/auth/login" -Method POST -Headers $headers -Body '{"email":"customer@carrental.com","password":"password"}' -UseBasicParsing
        $customerToken = $customerResponse.token
        Write-Host "Customer Token: $($customerToken.Substring(0, [Math]::Min(20, $customerToken.Length)))..." -ForegroundColor Green
    } catch {
        Write-Host "Failed to login customer" -ForegroundColor Red
        $customerToken = $null
    }
}
Write-Host ""

# ==========================================
# 2. VEHICLE CRUD
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "2. VEHICLE CRUD (Setup)" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

# Create Category
Write-Host "Creating category..." -ForegroundColor Yellow
try {
    $categoryResponse = Invoke-RestMethod -Uri "$BASE_URL/categories" -Method POST -Headers (@{"Content-Type"="application/json";"Accept"="application/json";"Authorization"="Bearer $adminToken"}) -Body '{"name":"Sedan","description":"Sedan vehicles"}' -UseBasicParsing
    $categoryId = $categoryResponse.data.id
    Write-Host "Category ID: $categoryId" -ForegroundColor Green
} catch {
    Write-Host "Failed to create category" -ForegroundColor Red
    $categoryId = 1
}
Write-Host ""

# Create Vehicle
Test-Endpoint -Method "POST" -Endpoint "/vehicles" `
    -Body "{`"category_id`":$categoryId,`"brand`":`"Toyota`",`"model`":`"Camry`",`"year`":2024,`"registration_number`":`"TEST-M2-001`",`"fuel_type`":`"petrol`",`"transmission`":`"automatic`",`"seats`":5,`"rental_price_per_day`":50,`"status`":`"available`"}" `
    -Token $adminToken -ExpectedCode 201 -Description "Create Vehicle"

Test-Endpoint -Method "GET" -Endpoint "/vehicles" -ExpectedCode 200 -Description "View All Vehicles"

# Get first vehicle ID
try {
    $vehiclesResponse = Invoke-RestMethod -Uri "$BASE_URL/vehicles" -Method GET -Headers (@{"Accept"="application/json"}) -UseBasicParsing
    $vehicleId = $vehiclesResponse.data[0].id
    Write-Host "Using Vehicle ID: $vehicleId" -ForegroundColor Green
} catch {
    $vehicleId = 1
}

Test-Endpoint -Method "GET" -Endpoint "/vehicles/$vehicleId" -ExpectedCode 200 -Description "View Vehicle Details"

# ==========================================
# 3. BOOKING CRUD
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "3. BOOKING CRUD" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

Test-Endpoint -Method "POST" -Endpoint "/bookings" `
    -Body "{`"vehicle_id`":$vehicleId,`"pickup_location`":`"Airport`",`"return_location`":`"Airport`",`"pickup_date`":`"2026-08-15 10:00:00`",`"return_date`":`"2026-08-20 10:00:00`"}" `
    -Token $customerToken -ExpectedCode 201 -Description "Create Booking"

Test-Endpoint -Method "GET" -Endpoint "/bookings" -Token $customerToken -ExpectedCode 200 -Description "View Customer Bookings"

# Get booking ID
try {
    $bookingsResponse = Invoke-RestMethod -Uri "$BASE_URL/bookings" -Method GET -Headers (@{"Content-Type"="application/json";"Accept"="application/json";"Authorization"="Bearer $customerToken"}) -UseBasicParsing
    $bookingId = $bookingsResponse.data[0].id
    Write-Host "Using Booking ID: $bookingId" -ForegroundColor Green
} catch {
    $bookingId = 1
}

Test-Endpoint -Method "GET" -Endpoint "/bookings/$bookingId" -Token $customerToken -ExpectedCode 200 -Description "View Booking Details"

# Check Availability
Test-Endpoint -Method "GET" -Endpoint "/bookings/check-availability?vehicle_id=$vehicleId&pickup_date=2026-08-25&return_date=2026-08-28" -Token $customerToken -ExpectedCode 200 -Description "Check Availability"

# Price Estimate
Test-Endpoint -Method "GET" -Endpoint "/bookings/price-estimate?vehicle_id=$vehicleId&pickup_date=2026-08-15&return_date=2026-08-20" -Token $customerToken -ExpectedCode 200 -Description "Price Estimate"

Test-Endpoint -Method "PUT" -Endpoint "/admin/bookings/$bookingId/confirm" -Token $adminToken -ExpectedCode 200 -Description "Confirm Booking (Admin)"

Test-Endpoint -Method "GET" -Endpoint "/admin/bookings" -Token $adminToken -ExpectedCode 200 -Description "View All Bookings (Admin)"

# ==========================================
# 4. PAYMENT CRUD
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "4. PAYMENT CRUD" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

Test-Endpoint -Method "POST" -Endpoint "/payments" `
    -Body "{`"booking_id`":$bookingId,`"payment_method`":`"card`",`"transaction_reference`":`"TXN-M2-001`"}" `
    -Token $customerToken -ExpectedCode 201 -Description "Create Payment"

Test-Endpoint -Method "GET" -Endpoint "/payments" -Token $customerToken -ExpectedCode 200 -Description "View Payment History"

# Get payment ID
try {
    $paymentsResponse = Invoke-RestMethod -Uri "$BASE_URL/payments" -Method GET -Headers (@{"Content-Type"="application/json";"Accept"="application/json";"Authorization"="Bearer $customerToken"}) -UseBasicParsing
    $paymentId = $paymentsResponse.data[0].id
    Write-Host "Using Payment ID: $paymentId" -ForegroundColor Green
} catch {
    $paymentId = 1
}

Test-Endpoint -Method "GET" -Endpoint "/payments/$paymentId" -Token $customerToken -ExpectedCode 200 -Description "View Payment Details"

# ==========================================
# 5. BOOKING COMPLETION
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "5. BOOKING COMPLETION" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

Test-Endpoint -Method "PUT" -Endpoint "/admin/bookings/$bookingId/pickup" -Token $adminToken -ExpectedCode 200 -Description "Pickup Vehicle"

Test-Endpoint -Method "PUT" -Endpoint "/admin/bookings/$bookingId/return" -Token $adminToken -ExpectedCode 200 -Description "Return Vehicle"

# ==========================================
# 6. REVIEW CRUD
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "6. REVIEW CRUD" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

Test-Endpoint -Method "POST" -Endpoint "/vehicles/$vehicleId/reviews" `
    -Body "{`"booking_id`":$bookingId,`"rating`":5,`"comment`":`"Great car!`"}" `
    -Token $customerToken -ExpectedCode 201 -Description "Create Review"

Test-Endpoint -Method "GET" -Endpoint "/vehicles/$vehicleId/reviews" -ExpectedCode 200 -Description "View Vehicle Reviews"

# ==========================================
# 7. NOTIFICATIONS
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "7. NOTIFICATIONS" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

Test-Endpoint -Method "GET" -Endpoint "/notifications" -Token $customerToken -ExpectedCode 200 -Description "View Notifications"

Test-Endpoint -Method "PUT" -Endpoint "/notifications/read-all" -Token $customerToken -ExpectedCode 200 -Description "Mark All as Read"

# ==========================================
# SUMMARY
# ==========================================
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "TEST SUMMARY" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Passed: $passed" -ForegroundColor Green
Write-Host "Failed: $failed" -ForegroundColor Red
Write-Host ""

if ($failed -eq 0) {
    Write-Host "All tests passed!" -ForegroundColor Green
} else {
    Write-Host "Some tests failed. Please check the output above." -ForegroundColor Red
}
