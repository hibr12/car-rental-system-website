import React, { useEffect } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";

// Import Route Guards
import ProtectedRoute from "./routes/ProtectedRoute";
import RoleRoute from "./routes/RoleRoute";

// Import Layouts
import PublicLayout from "./layouts/PublicLayout";
import AdminLayout from "./layouts/AdminLayout";
import DashboardLayout from "./layouts/DashboardLayout";

// Import Public Pages
import HomePage from "./pages/public/HomePage";
import VehiclesPage from "./pages/public/VehiclesPage";
import VehicleDetailPage from "./pages/public/VehicleDetailPage";
import ContactPage from "./pages/public/ContactPage";

// Import Auth Pages
import LoginPage from "./pages/auth/LoginPage";
import RegisterPage from "./pages/auth/RegisterPage";
import ForgotPasswordPage from "./pages/auth/ForgotPasswordPage";
import ResetPasswordPage from "./pages/auth/ResetPasswordPage";

// Import Admin Pages
import AdminDashboard from "./pages/admin/AdminDashboard";
import VehicleManagement from "./pages/admin/VehicleManagement";
import AdminBookings from "./pages/admin/AdminBookings";
import UserManagement from "./pages/admin/UserManagement";
import PaymentsPage from "./pages/admin/PaymentsPage";
import MaintenancePage from "./pages/admin/MaintenancePage";
import MessagesPage from "./pages/admin/MessagesPage";
import CategoryManagement from "./pages/admin/CategoryManagement";

// Import Customer Pages
import CustomerDashboard from "./pages/customer/CustomerDashboard";
import CustomerBookings from "./pages/customer/CustomerBookings";
import CustomerProfile from "./pages/customer/CustomerProfile";
import CustomerReviews from "./pages/customer/CustomerReviews";

// Import Fleet Page
import FleetDashboard from "./pages/fleet/FleetDashboard";

// Import Staff Pages
import StaffDashboard from "./pages/staff/StaffDashboard";
import StaffBookings from "./pages/staff/StaffBookings";

// Import Auth Store
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
        <Route element={<PublicLayout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/vehicles" element={<VehiclesPage />} />
          <Route path="/vehicles/:id" element={<VehicleDetailPage />} />
          <Route path="/contact" element={<ContactPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/reset-password" element={<ResetPasswordPage />} />
        </Route>

        {/* ================= PROTECTED ADMIN ROUTES ================= */}
        <Route
          path="/admin"
          element={
            <ProtectedRoute>
              <RoleRoute allowedRoles={["admin"]}>
                <AdminLayout />
              </RoleRoute>
            </ProtectedRoute>
          }
        >
          <Route index element={<AdminDashboard />} />
          <Route path="vehicles" element={<VehicleManagement />} />
          <Route path="bookings" element={<AdminBookings />} />
          <Route path="users" element={<UserManagement />} />
          <Route path="payments" element={<PaymentsPage />} />
          <Route path="maintenance" element={<MaintenancePage />} />
          <Route path="messages" element={<MessagesPage />} />
          <Route path="categories" element={<CategoryManagement />} />
        </Route>

        {/* ================= PROTECTED FLEET MANAGER ROUTES ================= */}
        <Route
          path="/fleet"
          element={
            <ProtectedRoute>
              <RoleRoute allowedRoles={["fleet_manager"]}>
                <DashboardLayout />
              </RoleRoute>
            </ProtectedRoute>
          }
        >
          <Route index element={<FleetDashboard />} />
          <Route path="vehicles" element={<VehicleManagement />} />
          <Route path="maintenance" element={<MaintenancePage />} />
          <Route path="categories" element={<CategoryManagement />} />
        </Route>

        {/* ================= PROTECTED CUSTOMER ROUTES ================= */}
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <RoleRoute allowedRoles={["customer"]}>
                <DashboardLayout />
              </RoleRoute>
            </ProtectedRoute>
          }
        >
          <Route index element={<CustomerDashboard />} />
          <Route path="bookings" element={<CustomerBookings />} />
          <Route path="reviews" element={<CustomerReviews />} />
          <Route path="profile" element={<CustomerProfile />} />
        </Route>

        {/* ================= PROTECTED STAFF ROUTES ================= */}
        <Route
          path="/staff"
          element={
            <ProtectedRoute>
              <RoleRoute allowedRoles={["staff"]}>
                <DashboardLayout />
              </RoleRoute>
            </ProtectedRoute>
          }
        >
          <Route index element={<StaffDashboard />} />
          <Route path="bookings" element={<StaffBookings />} />
        </Route>

        {/* Fallback redirect */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
