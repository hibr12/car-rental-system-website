import React, { useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

import ProtectedRoute from './app/guards/ProtectedRoute';
import RoleRoute from './app/guards/RoleRoute';

// ── Layouts ──────────────────────────────────────────────────────────────────
import CustomerLayout from './layouts/CustomerLayout';
import AdminLayout from './layouts/AdminLayout';
import FleetLayout from './layouts/FleetLayout';
import StaffLayout from './layouts/StaffLayout';

// ── Auth pages ────────────────────────────────────────────────────────────────
import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage';
import ResetPasswordPage from './pages/auth/ResetPasswordPage';
import ManagementLoginPage from './pages/auth/ManagementLoginPage';

// ── Customer / Public pages ───────────────────────────────────────────────────
import HomePage from './pages/public/HomePage';
import VehiclesPage from './pages/public/VehiclesPage';
import VehicleDetailPage from './pages/public/VehicleDetailPage';
import ContactPage from './pages/public/ContactPage';

// ── Customer authenticated pages ──────────────────────────────────────────────
import CustomerDashboard from './pages/customer/CustomerDashboard';
import CustomerBookings from './pages/customer/CustomerBookings';
import BookingDetailPage from './pages/customer/BookingDetailPage';
import CustomerProfile from './pages/customer/CustomerProfile';
import CustomerReviews from './pages/customer/CustomerReviews';
import CustomerPayments from './pages/customer/CustomerPayments';
import NotificationsPage from './pages/customer/NotificationsPage';

// ── Payment pages ─────────────────────────────────────────────────────────────
import CheckoutPage from './pages/payment/CheckoutPage';
import PaymentStatusPage from './pages/payment/PaymentStatusPage';
import BookingConfirmationPage from './pages/payment/BookingConfirmationPage';

// ── Admin pages ───────────────────────────────────────────────────────────────
import AdminDashboard from './pages/admin/AdminDashboard';
import VehicleManagement from './pages/admin/VehicleManagement';
import AdminBookings from './pages/admin/AdminBookings';
import UserManagement from './pages/admin/UserManagement';
import PaymentsPage from './pages/admin/PaymentsPage';
import MaintenancePage from './pages/admin/MaintenancePage';
import MessagesPage from './pages/admin/MessagesPage';
import CategoryManagement from './pages/admin/CategoryManagement';
import ReviewsManagement from './pages/admin/ReviewsManagement';
import AnalyticsPage from './pages/admin/AnalyticsPage';
import BranchesPage from './pages/admin/BranchesPage';
import VehicleTransfersPage from './pages/admin/VehicleTransfersPage';
import StaffManagementPage from './pages/admin/StaffManagementPage';
import ReportsPage from './pages/admin/ReportsPage';

// ── Branch Manager pages ──────────────────────────────────────────────────────
import BranchDashboard from './pages/branch/BranchDashboard';
import BranchRentalsPage from './pages/branch/BranchRentalsPage';

// ── Fleet Manager pages ───────────────────────────────────────────────────────
import FleetDashboard from './pages/fleet/FleetDashboard';
import FleetVehicles from './pages/fleet/FleetVehicles';
import FleetMaintenance from './pages/fleet/FleetMaintenance';

// ── Staff pages ───────────────────────────────────────────────────────────────
import StaffDashboard from './pages/staff/StaffDashboard';
import StaffBookings from './pages/staff/StaffBookings';

import useAuthStore from './store/authStore';

// Admin roles
const ADMIN_ROLES = ['admin', 'super_admin'];
// Branch manager roles
const BRANCH_ROLES = ['branch_manager'];
// Fleet manager roles
const FLEET_ROLES = ['fleet_manager'];
// Staff roles
const STAFF_ROLES = ['staff', 'rental_agent', 'inspection_staff', 'maintenance_staff', 'finance_staff'];

function App() {
  const { initAuth } = useAuthStore();

  useEffect(() => {
    initAuth();
    const handleUnauthorized = () => useAuthStore.getState().resetAuth();
    window.addEventListener('unauthorized', handleUnauthorized);
    return () => window.removeEventListener('unauthorized', handleUnauthorized);
  }, [initAuth]);

  return (
    <BrowserRouter>
      <Routes>

        {/* ══════════════════════════════════════════════════════
            CUSTOMER / PUBLIC  (root)
        ══════════════════════════════════════════════════════ */}
        <Route element={<CustomerLayout />}>
          <Route path="/"                 element={<HomePage />} />
          <Route path="/vehicles"         element={<VehiclesPage />} />
          <Route path="/vehicles/:id"     element={<VehicleDetailPage />} />
          <Route path="/contact"          element={<ContactPage />} />
          <Route path="/login"            element={<LoginPage />} />
          <Route path="/register"         element={<RegisterPage />} />
          <Route path="/forgot-password"  element={<ForgotPasswordPage />} />
          <Route path="/reset-password"   element={<ResetPasswordPage />} />
        </Route>

        {/* Protected customer checkout / payment routes */}
        <Route path="/checkout" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index element={<CheckoutPage />} />
        </Route>
        <Route path="/payments/status" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index element={<PaymentStatusPage />} />
        </Route>
        <Route path="/booking/confirmation/:id" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index element={<BookingConfirmationPage />} />
        </Route>

        {/* Protected customer portal */}
        <Route path="/dashboard" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index                  element={<CustomerDashboard />} />
          <Route path="bookings"        element={<CustomerBookings />} />
          <Route path="bookings/:id"    element={<BookingDetailPage />} />
          <Route path="reviews"         element={<CustomerReviews />} />
          <Route path="notifications"   element={<NotificationsPage />} />
          <Route path="profile"         element={<CustomerProfile />} />
          <Route path="payments"        element={<CustomerPayments />} />
        </Route>

        {/* ══════════════════════════════════════════════════════
            MANAGEMENT LOGIN  (/admin/login)
        ══════════════════════════════════════════════════════ */}
        <Route path="/admin/login" element={<ManagementLoginPage />} />

        {/* ══════════════════════════════════════════════════════
            ADMIN PORTAL  (/admin)
        ══════════════════════════════════════════════════════ */}
        <Route path="/admin" element={
          <ProtectedRoute redirectTo="/admin/login">
            <RoleRoute allowedRoles={ADMIN_ROLES}>
              <AdminLayout />
            </RoleRoute>
          </ProtectedRoute>
        }>
          <Route index                  element={<AdminDashboard />} />
          <Route path="dashboard"       element={<AdminDashboard />} />
          <Route path="branches"        element={<BranchesPage />} />
          <Route path="staff"           element={<StaffManagementPage />} />
          <Route path="vehicles"        element={<VehicleManagement />} />
          <Route path="transfers"       element={<VehicleTransfersPage />} />
          <Route path="bookings"        element={<AdminBookings />} />
          <Route path="users"           element={<UserManagement />} />
          <Route path="payments"        element={<PaymentsPage />} />
          <Route path="reviews"         element={<ReviewsManagement />} />
          <Route path="maintenance"     element={<MaintenancePage />} />
          <Route path="messages"        element={<MessagesPage />} />
          <Route path="categories"      element={<CategoryManagement />} />
          <Route path="reports"         element={<ReportsPage />} />
          <Route path="analytics"       element={<AnalyticsPage />} />
        </Route>

        {/* ══════════════════════════════════════════════════════
            BRANCH MANAGER PORTAL  (/branch)
        ══════════════════════════════════════════════════════ */}
        <Route path="/branch" element={
          <ProtectedRoute redirectTo="/admin/login">
            <RoleRoute allowedRoles={BRANCH_ROLES}>
              <AdminLayout />
            </RoleRoute>
          </ProtectedRoute>
        }>
          <Route index                  element={<BranchDashboard />} />
          <Route path="dashboard"       element={<BranchDashboard />} />
          <Route path="vehicles"        element={<VehicleManagement />} />
          <Route path="bookings"        element={<AdminBookings />} />
          <Route path="rentals"         element={<BranchRentalsPage />} />
          <Route path="check-in"        element={<BranchRentalsPage />} />
          <Route path="check-out"       element={<BranchRentalsPage />} />
          <Route path="payments"        element={<PaymentsPage />} />
          <Route path="maintenance"     element={<MaintenancePage />} />
          <Route path="staff"           element={<StaffManagementPage />} />
          <Route path="transfers"       element={<VehicleTransfersPage />} />
          <Route path="reports"         element={<ReportsPage />} />
        </Route>

        {/* ══════════════════════════════════════════════════════
            FLEET MANAGER PORTAL  (/fleet)
        ══════════════════════════════════════════════════════ */}
        <Route path="/fleet" element={
          <ProtectedRoute redirectTo="/admin/login">
            <RoleRoute allowedRoles={FLEET_ROLES}>
              <FleetLayout />
            </RoleRoute>
          </ProtectedRoute>
        }>
          <Route index                  element={<FleetDashboard />} />
          <Route path="dashboard"       element={<FleetDashboard />} />
          <Route path="vehicles"        element={<FleetVehicles />} />
          <Route path="maintenance"     element={<FleetMaintenance />} />
        </Route>

        {/* ══════════════════════════════════════════════════════
            STAFF PORTAL  (/staff)
        ══════════════════════════════════════════════════════ */}
        <Route path="/staff" element={
          <ProtectedRoute redirectTo="/admin/login">
            <RoleRoute allowedRoles={STAFF_ROLES}>
              <StaffLayout />
            </RoleRoute>
          </ProtectedRoute>
        }>
          <Route index                  element={<StaffDashboard />} />
          <Route path="dashboard"       element={<StaffDashboard />} />
          <Route path="bookings"        element={<StaffBookings />} />
          <Route path="check-in"        element={<StaffBookings />} />
          <Route path="check-out"       element={<StaffBookings />} />
        </Route>

        {/* Fallback — customer homepage, NOT login */}
        <Route path="*" element={<Navigate to="/" replace />} />

      </Routes>
    </BrowserRouter>
  );
}

export default App;
