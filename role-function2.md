# Role Functions - Detailed Workflow Description

## Overview
This document describes the specific day-to-day functions, workflows, and actions each role performs in the Apex Rentals car rental system.

---

## 1. ADMIN (Company Administrator)

**Primary Responsibility:** Full company oversight and cross-branch management

### Daily Workflows

#### Morning Dashboard Review
- Log into `/admin` portal
- Review company-wide KPIs: total revenue, fleet utilization, active rentals, pending bookings
- Check alerts: vehicles in maintenance, overdue inspections, expiring documents
- Review branch performance comparison

#### Branch Management
- **Create new branch:** Fill branch details (name, code, address, city, phone, email, hours, GPS coordinates)
- **Assign branch manager:** Either select existing user or auto-create manager account with email/password
- **Activate/deactivate branches:** Toggle branch status for seasonal operations
- **View branch details:** Staff count, vehicle count, booking volume, revenue, transfer activity

#### Vehicle Fleet Oversight
- **Add vehicles to any branch:** Specify branch, category, specs (brand, model, year, reg number, VIN, fuel, transmission, seats, price/day)
- **Bulk vehicle operations:** Status changes, transfers, maintenance scheduling
- **Cross-branch transfers:** Initiate, approve, track vehicle movements between branches
- **Vehicle lifecycle:** Monitor condition, schedule inspections, track documents (registration, insurance), record damages

#### Booking Administration
- **View all bookings** across company with filters (branch, status, date range, customer)
- **Confirm bookings:** Review and approve pending bookings
- **Reject bookings:** With reason (vehicle unavailable, customer issue, etc.)
- **Manage pickup/return:** Track vehicle handovers, mileage, fuel levels, condition notes
- **Handle exceptions:** Override workflows, force status changes with audit trail

#### Payment & Financial Management
- **Reconciliation dashboard:** Match Chapa payments with bookings, identify discrepancies
- **Confirm cash payments:** Verify branch-reported cash collections
- **Process refunds:** Full/partial refunds with reason tracking
- **Mark failed payments:** Handle gateway failures, retry logic
- **Revenue reports:** Daily, monthly, per-branch, per-vehicle revenue analysis

#### User & Staff Management
- **Create staff accounts:** Assign to branches, set roles (staff, branch_manager)
- **Manage branch managers:** Reassign, remove, reset credentials
- **View all users:** Filter by role, branch, status

#### Maintenance & Compliance
- **Schedule maintenance:** Recurring or one-time across all branches
- **Track compliance:** Vehicle document expiry, inspection due dates
- **Audit trail:** Full history of all changes with user attribution

#### Reports & Analytics
- **Company revenue:** Daily, weekly, monthly, custom ranges
- **Fleet utilization:** Per vehicle, per category, per branch
- **Branch performance:** Bookings, revenue, efficiency metrics
- **Export capabilities:** CSV/PDF for external reporting

---

## 2. BRANCH MANAGER

**Primary Responsibility:** Single-branch operations and team leadership

**Portal:** `/branch` (or `/manager` legacy)

### Daily Workflows

#### Morning Branch Dashboard
- Log into `/branch` portal (auto-scoped to assigned branch)
- Review branch KPIs: available vehicles, active rentals, today's bookings, pending confirmations
- Check maintenance alerts for branch vehicles
- Review overnight notifications (new bookings, payment confirmations, license submissions)

#### Vehicle Management (Branch-Scoped)
- **View branch vehicles only:** Filter by status (available, rented, maintenance, unavailable)
- **Add new vehicles to branch:** Branch auto-assigned, cannot change branch_id
- **Update vehicle details:** Status, price, condition, images
- **Move vehicles to maintenance:** Change status, create maintenance record
- **Handle returns:** Inspect condition, record mileage/fuel, note damages

#### Booking Operations
- **View branch bookings:** Today's pickups, returns, active rentals, pending
- **Confirm/reject new bookings:** Review customer license status, vehicle availability
- **Prepare for pickup:** Print rental agreement, verify documents, assign staff
- **Execute pickup:** Verify customer ID, license, record mileage/fuel, handover keys
- **Execute return:** Inspect vehicle, record mileage/fuel, assess damages, close booking

#### Customer Management
- **View branch customers:** Booking history, license status, payment history
- **Assist walk-ins:** Create bookings on behalf of customers
- **Handle complaints:** Document issues, escalate to admin if needed

#### Payment Handling
- **Confirm cash payments:** Record cash received, update payment status
- **View payment history:** All branch payments with status
- **Handle payment issues:** Failed Chapa payments, refund requests (escalate to admin)

#### Staff Supervision
- **View branch staff:** Roles, contact info, activity
- **Create staff accounts:** Auto-assigned to branch
- **Manage schedules:** Assign staff to shifts/pickups/returns

#### Vehicle Transfers (Outgoing/Incoming)
- **Request transfer out:** Select vehicle, destination branch, date, reason
- **Prepare release:** Final inspection, documents, handover to driver
- **Receive incoming:** Inspect vehicle, verify documents, update branch assignment
- **Track transfer status:** Pending, approved, in-transit, received, completed

#### Maintenance Requests
- **Create requests:** For branch vehicles needing service
- **Track status:** Scheduled, in-progress, completed
- **Coordinate with fleet:** Communicate urgency, downtime estimates

#### License Verification
- **Review submitted licenses:** View documents, customer info
- **Approve/Reject:** With reason (blurry image, expired, mismatch)
- **Track expirations:** Notify customers for renewals

#### Reports
- **Branch revenue:** Daily, monthly, by vehicle/category
- **Fleet utilization:** Which vehicles earn most, idle time
- **Booking trends:** Peak days, average duration, cancellation rate

---

## 3. STAFF (Rental Agent / Counter Staff)

**Primary Responsibility:** Front-line customer operations at assigned branch

**Portal:** `/staff`

### Daily Workflows

#### Shift Start
- Log into `/staff` portal (auto-scoped to assigned branch)
- Review today's schedule: pickups, returns, walk-in availability
- Check vehicle prep status (cleaned, fueled, inspected)

#### Customer Check-In (Pickup)
- **Verify customer:** ID, driver license (check status in system), booking confirmation
- **Vehicle walkaround:** Joint inspection with customer, photo documentation
- **Record:** Mileage, fuel level, existing damages, condition notes
- **Handover:** Keys, rental agreement copy, emergency contacts, return instructions

#### Customer Check-Out (Return)
- **Receive vehicle:** Keys, documents, personal items
- **Inspect:** Mileage, fuel level, new damages, cleanliness
- **Compare:** Against pickup record, calculate charges (extra km, fuel, damages)
- **Close booking:** Finalize charges, process any additional payments

#### Walk-In Customers
- **Check availability:** Real-time vehicle search by category, dates
- **Create booking:** Customer details, dates, vehicle selection, payment method
- **License check:** Verify customer has verified license or guide through upload

#### Payment Processing
- **Cash payments:** Record amount, issue receipt, update system
- **Chapa follow-up:** Check payment status, guide customer through retry
- **Refund requests:** Document reason, escalate to branch manager

#### Vehicle Prep Coordination
- **Flag vehicles** for cleaning, fueling, maintenance
- **Coordinate with fleet/maintenance** for quick turnaround
- **Track vehicle status** changes in real-time

#### Transfer Operations
- **Prepare release:** Final check, documents, photos for outgoing transfers
- **Receive incoming:** Inspect, verify, update system for incoming transfers

#### End of Shift
- **Handover notes:** Pending pickups/returns, issues, vehicle status
- **Ensure all bookings** processed, payments recorded
- **Secure keys** and documentation

---

## 4. FLEET MANAGER

**Primary Responsibility:** Company-wide vehicle lifecycle and maintenance strategy

**Portal:** `/fleet`

### Daily Workflows

#### Fleet Overview Dashboard
- Log into `/fleet` portal (company-wide access)
- Review fleet health: total vehicles, by status, by branch, by category
- Identify: Vehicles due for inspection, expiring documents, high-maintenance units
- Track: Utilization rates, revenue per vehicle, lifecycle stage

#### Vehicle Lifecycle Management
- **Acquisition:** Add new vehicles to any branch with full specs
- **Assignment:** Move vehicles between branches (coordinate with branch managers)
- **Retirement:** Decommission vehicles, manage disposal/sale
- **Replacement planning:** Identify vehicles nearing end-of-life

#### Maintenance Strategy
- **Schedule preventive maintenance:** By mileage, time, or manufacturer schedule
- **Manage maintenance types:** Oil changes, brake service, tire rotation, inspections, AC, major repairs
- **Vendor management:** Track preferred vendors, costs, quality
- **Budget tracking:** Maintenance costs per vehicle, per branch, per type

#### Inspection Program
- **Schedule inspections:** Pre-rental, post-rental, periodic safety, regulatory
- **Create inspection templates:** Checklists for different inspection types
- **Track compliance:** Due dates, completed, overdue, failed
- **Generate certificates:** Safety inspection certificates, roadworthiness

#### Document Management
- **Track all vehicle documents:** Registration, insurance, permits, inspection certs
- **Expiry monitoring:** Automated alerts 30/60/90 days before expiry
- **Renewal coordination:** Initiate renewals, track progress, store new docs

#### Damage Management
- **Record damages:** From returns, transfers, incidents, accidents
- **Assessment workflow:** Photos, estimates, approval, repair tracking
- **Insurance claims:** Coordinate with insurance, documentation
- **Cost recovery:** Track customer liability, insurance payouts

#### Fleet Analytics & Reporting
- **Utilization reports:** By vehicle, category, branch, time period
- **Cost analysis:** Total cost of ownership, maintenance per km, depreciation
- **Revenue per vehicle:** Identify high/low performers
- **Lifecycle reports:** Age distribution, replacement forecast
- **Compliance reports:** Document status, inspection compliance, regulatory

#### Cross-Branch Coordination
- **Vehicle transfers:** Approve, optimize routing, minimize downtime
- **Balance fleet:** Move vehicles from low-utilization to high-demand branches
- **Standardize specs:** Ensure consistent categories, pricing, features

---

## 5. CUSTOMER

**Primary Responsibility:** Self-service rental experience

**Portal:** `/` (public) → `/login` → `/dashboard`

### Workflows

#### Browse & Search
- **Homepage:** Featured vehicles, categories, promotions
- **Vehicle catalog:** Filter by category, price range, transmission, fuel type, seats, dates
- **Vehicle details:** Photos, specs, features, pricing, availability calendar
- **Real-time availability:** Enter dates → see available vehicles instantly

#### Authentication
- **Register:** Name, email, phone, password
- **Login:** Email/password, remember me
- **Password reset:** Email-based reset flow
- **Profile:** Update name, phone, password, notification preferences

#### Driver License (Required for Booking)
- **Upload license:** Front + back photos, license details (number, name, DOB, category, issue/expiry dates, authority, country)
- **View status:** Pending review, verified, rejected, expired
- **Update documents:** Replace blurry/rejected images
- **Eligibility check:** Enter vehicle ID → system checks license category match

#### Booking Process
1. **Select vehicle & dates** → Check availability
2. **Price estimate** → See breakdown (daily rate × days + fees - discounts)
3. **Create booking** → System validates license, vehicle availability
4. **Payment** → Choose Chapa (online) or Cash (pay at branch)
5. **Confirmation** → Booking reference, pickup details, instructions

#### Payment Options
- **Chapa (Online):** Redirect to Chapa gateway, auto-verify on return
- **Cash:** Select cash, booking held, pay at branch on pickup
- **Payment status:** Real-time tracking (pending, paid, failed, refunded)

#### Booking Management
- **My bookings:** Upcoming, active, past, cancelled
- **Booking details:** Vehicle, dates, locations, pricing, status, payment status
- **Cancel booking:** With reason, cancellation policy applied
- **Modify booking:** Contact branch for changes (dates, vehicle)

#### Reviews
- **Eligible bookings:** Completed rentals available for review
- **Write review:** 1-5 star rating + comment
- **Edit review:** Within allowed timeframe
- **View responses:** Admin/branch manager responses

#### Notifications
- **Booking confirmations:** Email + in-app
- **Payment receipts:** Success/failure alerts
- **License updates:** Verified/rejected with reason
- **Reminders:** Pickup tomorrow, return due, license expiring
- **Promotions:** Special offers, discounts

---

## Cross-Role Interaction Flows

### Booking Creation → Approval
```
Customer creates booking
    ↓
Branch Manager/Staff receives notification
    ↓
Review: License verified? Vehicle available? Dates valid?
    ↓
Confirm → Customer notified, payment initiated (if not cash)
    ↓
Reject → Customer notified with reason, booking cancelled
```

### Payment Verification
```
Customer pays via Chapa
    ↓
Chapa callback/webhook received
    ↓
System verifies with Chapa API
    ↓
Payment marked PAID → Booking confirmed
    ↓
Branch notified for pickup prep
```

### Vehicle Transfer
```
Branch A Manager requests transfer
    ↓
Branch B Manager/Fleet Manager approves
    ↓
Branch A Staff prepares release (inspection, docs, photos)
    ↓
Driver transports → Branch B Staff receives (inspection, verify)
    ↓
Vehicle branch_id updated → Available at Branch B
```

### Maintenance Cycle
```
Branch Manager/Staff creates request
    ↓
Fleet Manager schedules (assigns vendor, date, type)
    ↓
Vehicle status → MAINTENANCE (removed from available pool)
    ↓
Work completed → Fleet Manager closes request
    ↓
Vehicle status → AVAILABLE (or RENTED if booked)
```

### License Verification
```
Customer uploads license
    ↓
Branch Manager/Staff/Admin reviews
    ↓
Approve → Customer can book vehicles matching category
    ↓
Reject → Customer notified with reason, can re-upload
```

---

## Permission Boundaries Summary

| Action | Admin | Fleet Mgr | Branch Mgr | Staff | Customer |
|--------|-------|-----------|------------|-------|----------|
| Create branch | ✅ | ❌ | ❌ | ❌ | ❌ |
| Assign branch manager | ✅ | ❌ | ❌ | ❌ | ❌ |
| Add vehicle to ANY branch | ✅ | ✅ | Own only | ❌ | ❌ |
| Move vehicle between branches | ✅ | ✅ | Request only | ❌ | ❌ |
| Approve transfers | ✅ | ✅ | Own branch | ❌ | ❌ |
| Confirm bookings | ✅ | ❌ | Own branch | Own branch | ❌ |
| Reject bookings | ✅ | ❌ | Own branch | Own branch | ❌ |
| Process pickup/return | ✅ | ❌ | Own branch | Own branch | ❌ |
| Confirm cash payments | ✅ | ❌ | Own branch | Own branch | ❌ |
| Reconcile payments | ✅ | ❌ | ❌ | ❌ | ❌ |
| Process refunds | ✅ | ❌ | ❌ | ❌ | ❌ |
| Schedule maintenance | ✅ | ✅ | Request | Request | ❌ |
| Approve licenses | ✅ | ❌ | Own branch | Own branch | ❌ |
| View all company data | ✅ | Fleet only | Own branch | Own branch | Own only |
| Export reports | ✅ | ✅ | Own branch | ❌ | ❌ |
| Manage users | ✅ | ❌ | Own branch staff | ❌ | ❌ |