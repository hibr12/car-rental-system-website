import React, { useEffect } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";

import ProtectedRoute from "./routes/ProtectedRoute";
import CustomerLayout from "./layouts/CustomerLayout";

import HomePage from "./pages/public/HomePage";
import VehiclesPage from "./pages/public/VehiclesPage";
import VehicleDetailPage from "./pages/public/VehicleDetailPage";
import ContactPage from "./pages/public/ContactPage";

import LoginPage from "./pages/auth/LoginPage";
import RegisterPage from "./pages/auth/RegisterPage";
import ForgotPasswordPage from "./pages/auth/ForgotPasswordPage";
import ResetPasswordPage from "./pages/auth/ResetPasswordPage";

import CheckoutPage from "./pages/payment/CheckoutPage";
import PaymentStatusPage from "./pages/payment/PaymentStatusPage";
import BookingConfirmationPage from "./pages/payment/BookingConfirmationPage";

import CustomerDashboard from "./pages/customer/CustomerDashboard";
import CustomerBookings from "./pages/customer/CustomerBookings";
import BookingDetailPage from "./pages/customer/BookingDetailPage";
import CustomerProfile from "./pages/customer/CustomerProfile";
import CustomerReviews from "./pages/customer/CustomerReviews";
import CustomerPayments from "./pages/customer/CustomerPayments";
import NotificationsPage from "./pages/customer/NotificationsPage";

import useAuthStore from "./store/authStore";

function App() {
  const { initAuth } = useAuthStore();

  useEffect(() => {
    initAuth();
    const handleUnauthorized = () => useAuthStore.getState().resetAuth();
    window.addEventListener("unauthorized", handleUnauthorized);
    return () => window.removeEventListener("unauthorized", handleUnauthorized);
  }, [initAuth]);

  return (
    <BrowserRouter>
      <Routes>
        {/* ================= PUBLIC ROUTES ================= */}
        <Route element={<CustomerLayout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/vehicles" element={<VehiclesPage />} />
          <Route path="/vehicles/:id" element={<VehicleDetailPage />} />
          <Route path="/contact" element={<ContactPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/reset-password" element={<ResetPasswordPage />} />
        </Route>

        {/* ================= PAYMENT ROUTES (Protected) ================= */}
        <Route
          path="/checkout"
          element={
            <ProtectedRoute>
              <CustomerLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<CheckoutPage />} />
        </Route>

        <Route
          path="/payments/status"
          element={
            <ProtectedRoute>
              <CustomerLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<PaymentStatusPage />} />
        </Route>

        <Route
          path="/booking/confirmation/:id"
          element={
            <ProtectedRoute>
              <CustomerLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<BookingConfirmationPage />} />
        </Route>

        {/* ================= PROTECTED CUSTOMER ROUTES ================= */}
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <CustomerLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<CustomerDashboard />} />
          <Route path="bookings" element={<CustomerBookings />} />
          <Route path="bookings/:id" element={<BookingDetailPage />} />
          <Route path="reviews" element={<CustomerReviews />} />
          <Route path="notifications" element={<NotificationsPage />} />
          <Route path="profile" element={<CustomerProfile />} />
          <Route path="payments" element={<CustomerPayments />} />
        </Route>

        {/* Fallback redirect */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
