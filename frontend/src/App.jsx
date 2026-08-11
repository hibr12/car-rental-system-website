import React, { useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

import PortalGate from './app/guards/PortalGate';
import ProtectedRoute from './app/guards/ProtectedRoute';

import CustomerLayout from './layouts/CustomerLayout';
import AdminLayout from './layouts/AdminLayout';
import ManagerLayout from './layouts/ManagerLayout';
import FleetLayout from './layouts/FleetLayout';
import StaffLayout from './layouts/StaffLayout';

import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage';
import ResetPasswordPage from './pages/auth/ResetPasswordPage';

import HomePage from './pages/public/HomePage';
import VehiclesPage from './pages/public/VehiclesPage';
import VehicleDetailPage from './pages/public/VehicleDetailPage';
import ContactPage from './pages/public/ContactPage';

import CustomerDashboard from './pages/customer/CustomerDashboard';
import CustomerBookings from './pages/customer/CustomerBookings';
import BookingDetailPage from './pages/customer/BookingDetailPage';
import CustomerProfile from './pages/customer/CustomerProfile';
import CustomerReviews from './pages/customer/CustomerReviews';
import CustomerPayments from './pages/customer/CustomerPayments';
import NotificationsPage from './pages/customer/NotificationsPage';

import CheckoutPage from './pages/payment/CheckoutPage';
import PaymentStatusPage from './pages/payment/PaymentStatusPage';
import BookingConfirmationPage from './pages/payment/BookingConfirmationPage';

import AdminDashboard from './pages/admin/AdminDashboard';
import VehicleManagement from './pages/admin/VehicleManagement';
import AdminBookings from './pages/admin/AdminBookings';
import UserManagement from './pages/admin/UserManagement';
import PaymentsPage from './pages/admin/PaymentsPage';
import PaymentHistoryPage from './pages/admin/PaymentHistoryPage';
import ArchiveIndexPage from './pages/admin/ArchiveIndexPage';
import ArchiveBookingsPage from './pages/admin/ArchiveBookingsPage';
import ArchivePaymentsPage from './pages/admin/ArchivePaymentsPage';
import MaintenancePage from './pages/admin/MaintenancePage';
import MessagesPage from './pages/admin/MessagesPage';
import CategoryManagement from './pages/admin/CategoryManagement';
import ReviewsManagement from './pages/admin/ReviewsManagement';
import AnalyticsPage from './pages/admin/AnalyticsPage';
import BranchesPage from './pages/admin/BranchesPage';
import VehicleTransfersPage from './pages/admin/VehicleTransfersPage';
import StaffManagementPage from './pages/admin/StaffManagementPage';
import ReportsPage from './pages/admin/ReportsPage';

import BranchDashboard from './pages/branch/BranchDashboard';
import BranchRentalsPage from './pages/branch/BranchRentalsPage';

import FleetDashboard from './pages/fleet/FleetDashboard';
import FleetVehicles from './pages/fleet/FleetVehicles';
import FleetMaintenance from './pages/fleet/FleetMaintenance';

import StaffDashboard from './pages/staff/StaffDashboard';
import StaffBookings from './pages/staff/StaffBookings';

import PortalNotFound from './pages/shared/PortalNotFound';
import ManagementLoginPage from './pages/auth/ManagementLoginPage';
import LegacyBranchRedirect from './app/redirects/LegacyBranchRedirect';

import useAuthStore from './store/authStore';

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

        {/* ═══ CUSTOMER / PUBLIC ═══ */}
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

        <Route path="/checkout" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index element={<CheckoutPage />} />
        </Route>
        <Route path="/payments/status" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index element={<PaymentStatusPage />} />
        </Route>
        <Route path="/booking/confirmation/:id" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index element={<BookingConfirmationPage />} />
        </Route>
        <Route path="/dashboard" element={<ProtectedRoute><CustomerLayout /></ProtectedRoute>}>
          <Route index                  element={<CustomerDashboard />} />
          <Route path="bookings"        element={<CustomerBookings />} />
          <Route path="bookings/:id"    element={<BookingDetailPage />} />
          <Route path="reviews"         element={<CustomerReviews />} />
          <Route path="notifications"   element={<NotificationsPage />} />
          <Route path="profile"         element={<CustomerProfile />} />
          <Route path="payments"        element={<CustomerPayments />} />
        </Route>

        {/* ═══ MANAGEMENT SIGN-IN (must be outside PortalGate) ═══ */}
        <Route path="/admin/login"   element={<ManagementLoginPage portal="admin" />} />
        <Route path="/manager/login" element={<ManagementLoginPage portal="manager" />} />
        <Route path="/branch/login"  element={<Navigate to="/manager/login" replace />} />
        <Route path="/fleet/login"   element={<ManagementLoginPage portal="fleet" />} />
        <Route path="/staff/login"   element={<ManagementLoginPage portal="staff" />} />

        {/* ═══ ADMIN PORTAL ═══ */}
        <Route path="/admin" element={<PortalGate portal="admin" layout={AdminLayout} />}>
          <Route index                  element={<AdminDashboard />} />
          <Route path="dashboard"       element={<AdminDashboard />} />
          <Route path="branches"        element={<BranchesPage />} />
          <Route path="staff"           element={<StaffManagementPage />} />
          <Route path="vehicles"        element={<VehicleManagement />} />
          <Route path="transfers"       element={<VehicleTransfersPage />} />
          <Route path="bookings"        element={<AdminBookings />} />
          <Route path="users"           element={<UserManagement />} />
          <Route path="customers"       element={<UserManagement />} />
          <Route path="payments"        element={<PaymentsPage />} />
          <Route path="payment-history" element={<PaymentHistoryPage />} />
          <Route path="reviews"         element={<ReviewsManagement />} />
          <Route path="maintenance"     element={<MaintenancePage />} />
          <Route path="messages"        element={<MessagesPage />} />
          <Route path="categories"      element={<CategoryManagement />} />
          <Route path="reports"         element={<ReportsPage />} />
          <Route path="analytics"       element={<AnalyticsPage />} />
          <Route path="archive"         element={<ArchiveIndexPage />} />
          <Route path="archive/bookings" element={<ArchiveBookingsPage />} />
          <Route path="archive/payments" element={<ArchivePaymentsPage />} />
          <Route path="*"               element={<PortalNotFound />} />
        </Route>

        {/* ═══ BRANCH MANAGER PORTAL (/manager) ═══ */}
        <Route path="/manager" element={<PortalGate portal="manager" layout={ManagerLayout} />}>
          <Route index                  element={<BranchDashboard />} />
          <Route path="dashboard"       element={<BranchDashboard />} />
          <Route path="vehicles"        element={<VehicleManagement />} />
          <Route path="bookings"        element={<AdminBookings />} />
          <Route path="customers"       element={<UserManagement />} />
          <Route path="rentals"         element={<BranchRentalsPage />} />
          <Route path="check-in"        element={<BranchRentalsPage />} />
          <Route path="check-out"       element={<BranchRentalsPage />} />
          <Route path="inspections"     element={<BranchRentalsPage />} />
          <Route path="payments"        element={<PaymentsPage />} />
          <Route path="payment-history" element={<PaymentHistoryPage />} />
          <Route path="maintenance"     element={<MaintenancePage />} />
          <Route path="staff"           element={<StaffManagementPage />} />
          <Route path="transfers"       element={<VehicleTransfersPage />} />
          <Route path="reports"         element={<ReportsPage />} />
          <Route path="*"               element={<PortalNotFound />} />
        </Route>

        {/* Legacy /branch → /manager */}
        <Route path="/branch/*" element={<LegacyBranchRedirect />} />

        {/* ═══ FLEET MANAGER PORTAL ═══ */}
        <Route path="/fleet" element={<PortalGate portal="fleet" layout={FleetLayout} />}>
          <Route index                  element={<FleetDashboard />} />
          <Route path="dashboard"       element={<FleetDashboard />} />
          <Route path="vehicles"        element={<FleetVehicles />} />
          <Route path="maintenance"     element={<FleetMaintenance />} />
          <Route path="*"               element={<PortalNotFound />} />
        </Route>

        {/* ═══ STAFF PORTAL ═══ */}
        <Route path="/staff" element={<PortalGate portal="staff" layout={StaffLayout} />}>
          <Route index                  element={<StaffDashboard />} />
          <Route path="dashboard"       element={<StaffDashboard />} />
          <Route path="bookings"        element={<StaffBookings />} />
          <Route path="payments"        element={<PaymentsPage />} />
          <Route path="payment-history" element={<PaymentHistoryPage />} />
          <Route path="customers"       element={<UserManagement />} />
          <Route path="vehicles"        element={<VehicleManagement />} />
          <Route path="maintenance"     element={<MaintenancePage />} />
          <Route path="check-in"        element={<StaffBookings />} />
          <Route path="check-out"       element={<StaffBookings />} />
          <Route path="inspections"     element={<StaffBookings />} />
          <Route path="*"               element={<PortalNotFound />} />
        </Route>

        {/* Customer 404 only — management unknown routes handled inside each portal */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
