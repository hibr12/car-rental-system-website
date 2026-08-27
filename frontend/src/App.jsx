import React, { useEffect, Suspense, lazy } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

import PortalGate from './app/guards/PortalGate';
import ProtectedRoute from './app/guards/ProtectedRoute';

import CustomerLayout from './layouts/CustomerLayout';
import AdminLayout from './layouts/AdminLayout';
import ManagerLayout from './layouts/ManagerLayout';
import BranchLayout from './layouts/BranchLayout';
import FleetLayout from './layouts/FleetLayout';
import StaffLayout from './layouts/StaffLayout';

import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage';
import ResetPasswordPage from './pages/auth/ResetPasswordPage';
import EmailVerificationNoticePage from './pages/auth/EmailVerificationNoticePage';

import HomePage from './pages/public/HomePage';
import VehiclesPage from './pages/public/VehiclesPage';
import VehicleDetailPage from './pages/public/VehicleDetailPage';
import ContactPage from './pages/public/ContactPage';

import CustomerDashboard from './pages/customer/CustomerDashboard';
import CustomerBookings from './pages/customer/CustomerBookings';
import BookingDetailPage from './pages/customer/BookingDetailPage';
import CustomerProfile from './pages/customer/CustomerProfile';
import CustomerReviews from './pages/customer/CustomerReviews';
import BookingReviewPage from './pages/customer/BookingReviewPage';
import CustomerPayments from './pages/customer/CustomerPayments';
import NotificationsPage from './pages/customer/NotificationsPage';
import { DriverLicensePage } from './pages/customer/DriverLicensePage';
import { LicenseReviewPage } from './pages/admin/LicenseReviewPage';

import CheckoutPage from './pages/payment/CheckoutPage';
import PaymentStatusPage from './pages/payment/PaymentStatusPage';
import BookingConfirmationPage from './pages/payment/BookingConfirmationPage';

const AdminDashboard = lazy(() => import('./pages/admin/AdminDashboard'));
const VehicleManagement = lazy(() => import('./pages/admin/VehicleManagement'));
const AdminBookings = lazy(() => import('./pages/admin/AdminBookings'));
const UserManagement = lazy(() => import('./pages/admin/UserManagement'));
const PaymentsPage = lazy(() => import('./pages/admin/PaymentsPage'));
const PaymentHistoryPage = lazy(() => import('./pages/admin/PaymentHistoryPage'));
const PaymentReconciliationPage = lazy(() => import('./pages/admin/PaymentReconciliationPage'));
const ArchiveIndexPage = lazy(() => import('./pages/admin/ArchiveIndexPage'));
const ArchiveBookingsPage = lazy(() => import('./pages/admin/ArchiveBookingsPage'));
const ArchivePaymentsPage = lazy(() => import('./pages/admin/ArchivePaymentsPage'));
const MaintenancePage = lazy(() => import('./pages/admin/MaintenancePage'));
const MessagesPage = lazy(() => import('./pages/admin/MessagesPage'));
const CategoryManagement = lazy(() => import('./pages/admin/CategoryManagement'));
const ReviewsManagement = lazy(() => import('./pages/admin/ReviewsManagement'));
const AnalyticsPage = lazy(() => import('./pages/admin/AnalyticsPage'));
const BranchesPage = lazy(() => import('./pages/admin/BranchesPage'));
const VehicleTransfersPage = lazy(() => import('./pages/admin/VehicleTransfersPage'));
const StaffManagementPage = lazy(() => import('./pages/admin/StaffManagementPage'));
const ReportsPage = lazy(() => import('./pages/admin/ReportsPage'));

import BranchDashboard from './pages/branch/BranchDashboard';
import BranchRentalsPage from './pages/branch/BranchRentalsPage';
import BranchMaintenanceRequests from './pages/branch/BranchMaintenanceRequests';
import BranchCustomers from './pages/branch/BranchCustomers';

import FleetDashboard from './pages/fleet/FleetDashboard';
import FleetVehicles from './pages/fleet/FleetVehicles';
import FleetMaintenance from './pages/fleet/FleetMaintenance';
import FleetReports from './pages/fleet/FleetReports';
import FleetInspections from './pages/fleet/FleetInspections';
import FleetDocuments from './pages/fleet/FleetDocuments';
import FleetDamage from './pages/fleet/FleetDamage';

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
      <Suspense fallback={<div className="p-8 text-sm text-theme-muted">Loading...</div>}>
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
          <Route path="/verify-email"     element={<EmailVerificationNoticePage />} />
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
          <Route path="bookings/:bookingId/review" element={<BookingReviewPage />} />
          <Route path="reviews"         element={<CustomerReviews />} />
          <Route path="notifications"   element={<NotificationsPage />} />
          <Route path="profile"         element={<CustomerProfile />} />
          <Route path="license"         element={<DriverLicensePage />} />
          <Route path="payments"        element={<CustomerPayments />} />
        </Route>

        {/* ═══ MANAGEMENT SIGN-IN (must be outside PortalGate) ═══ */}
        <Route path="/admin/login"   element={<ManagementLoginPage portal="admin" />} />
        <Route path="/admin/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/admin/reset-password" element={<ResetPasswordPage />} />
        <Route path="/manager/login" element={<ManagementLoginPage portal="manager" />} />
        <Route path="/manager/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/manager/reset-password" element={<ResetPasswordPage />} />
        <Route path="/branch/login"  element={<ManagementLoginPage portal="branch" />} />
        <Route path="/branch/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/branch/reset-password" element={<ResetPasswordPage />} />
        <Route path="/fleet/login"   element={<ManagementLoginPage portal="fleet" />} />
        <Route path="/fleet/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/fleet/reset-password" element={<ResetPasswordPage />} />
        <Route path="/staff/login"   element={<ManagementLoginPage portal="staff" />} />
        <Route path="/staff/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/staff/reset-password" element={<ResetPasswordPage />} />

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
          <Route path="payments/reconciliation" element={<PaymentReconciliationPage />} />
          <Route path="payment-history" element={<PaymentHistoryPage />} />
          <Route path="reviews"         element={<ReviewsManagement />} />
          <Route path="licenses"        element={<LicenseReviewPage />} />
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

        {/* ═══ BRANCH MANAGER PORTAL (/branch) ═══ */}
        <Route path="/branch" element={<PortalGate portal="branch" layout={BranchLayout} />}>
          <Route index                  element={<BranchDashboard />} />
          <Route path="dashboard"       element={<BranchDashboard />} />
          <Route path="vehicles"        element={<VehicleManagement />} />
          <Route path="bookings"        element={<AdminBookings />} />
          <Route path="customers"       element={<BranchCustomers />} />
          <Route path="rentals"         element={<BranchRentalsPage />} />
          <Route path="check-in"        element={<BranchRentalsPage />} />
          <Route path="check-out"       element={<BranchRentalsPage />} />
          <Route path="payments"        element={<PaymentsPage />} />
          <Route path="maintenance-requests" element={<BranchMaintenanceRequests />} />
          <Route path="transfers"       element={<VehicleTransfersPage />} />
          <Route path="reviews"         element={<ReviewsManagement />} />
          <Route path="staff"           element={<StaffManagementPage />} />
          <Route path="licenses"        element={<LicenseReviewPage />} />
          <Route path="reports"         element={<ReportsPage />} />
          <Route path="notifications"   element={<NotificationsPage />} />
          <Route path="*"               element={<PortalNotFound />} />
        </Route>

        {/* Legacy /manager — backwards compatible */}
        <Route path="/manager" element={<PortalGate portal="manager" layout={ManagerLayout} />}>
          <Route index                  element={<BranchDashboard />} />
          <Route path="dashboard"       element={<BranchDashboard />} />
          <Route path="vehicles"        element={<VehicleManagement />} />
          <Route path="bookings"        element={<AdminBookings />} />
          <Route path="customers"       element={<BranchCustomers />} />
          <Route path="rentals"         element={<BranchRentalsPage />} />
          <Route path="check-in"        element={<BranchRentalsPage />} />
          <Route path="check-out"       element={<BranchRentalsPage />} />
          <Route path="payments"        element={<PaymentsPage />} />
          <Route path="payment-history" element={<PaymentHistoryPage />} />
          <Route path="maintenance"     element={<MaintenancePage />} />
          <Route path="maintenance-requests" element={<BranchMaintenanceRequests />} />
          <Route path="staff"           element={<StaffManagementPage />} />
          <Route path="transfers"       element={<VehicleTransfersPage />} />
          <Route path="licenses"        element={<LicenseReviewPage />} />
          <Route path="reports"         element={<ReportsPage />} />
          <Route path="reviews"         element={<ReviewsManagement />} />
          <Route path="notifications"   element={<NotificationsPage />} />
          <Route path="*"               element={<PortalNotFound />} />
        </Route>

        {/* Legacy /branch/* paths redirect to /branch portal */}
        <Route path="/branch-old/*" element={<LegacyBranchRedirect />} />

        {/* ═══ FLEET MANAGER PORTAL ═══ */}
        <Route path="/fleet" element={<PortalGate portal="fleet" layout={FleetLayout} />}>
          <Route index                  element={<FleetDashboard />} />
          <Route path="dashboard"       element={<FleetDashboard />} />
          <Route path="vehicles"        element={<FleetVehicles />} />
          <Route path="maintenance"     element={<FleetMaintenance />} />
          <Route path="transfers"       element={<VehicleTransfersPage />} />
          <Route path="inspections"    element={<FleetInspections />} />
          <Route path="documents"      element={<FleetDocuments />} />
          <Route path="damage"         element={<FleetDamage />} />
          <Route path="reports"         element={<FleetReports />} />
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
      </Suspense>
    </BrowserRouter>
  );
}

export default App;
