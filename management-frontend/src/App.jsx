import React, { useEffect } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";

import ProtectedRoute from "./routes/ProtectedRoute";
import RoleRoute from "./routes/RoleRoute";
import AdminLayout from "./layouts/AdminLayout";
import FleetLayout from "./layouts/FleetLayout";
import StaffLayout from "./layouts/StaffLayout";

import ManagementLoginPage from "./pages/auth/ManagementLoginPage";

import AdminDashboard from "./pages/admin/AdminDashboard";
import VehicleManagement from "./pages/admin/VehicleManagement";
import AdminBookings from "./pages/admin/AdminBookings";
import UserManagement from "./pages/admin/UserManagement";
import PaymentsPage from "./pages/admin/PaymentsPage";
import MaintenancePage from "./pages/admin/MaintenancePage";
import MessagesPage from "./pages/admin/MessagesPage";
import CategoryManagement from "./pages/admin/CategoryManagement";
import ReviewsManagement from "./pages/admin/ReviewsManagement";
import AnalyticsPage from "./pages/admin/AnalyticsPage";

import FleetDashboard from "./pages/fleet/FleetDashboard";
import FleetVehicles from "./pages/fleet/FleetVehicles";
import FleetMaintenance from "./pages/fleet/FleetMaintenance";

import StaffDashboard from "./pages/staff/StaffDashboard";
import StaffBookings from "./pages/staff/StaffBookings";

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
        {/* ================= MANAGEMENT LOGIN ================= */}
        <Route path="/login" element={<ManagementLoginPage />} />

        {/* ================= ADMIN ROUTES ================= */}
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
          <Route path="reviews" element={<ReviewsManagement />} />
          <Route path="maintenance" element={<MaintenancePage />} />
          <Route path="messages" element={<MessagesPage />} />
          <Route path="categories" element={<CategoryManagement />} />
          <Route path="analytics" element={<AnalyticsPage />} />
        </Route>

        {/* ================= FLEET MANAGER ROUTES ================= */}
        <Route
          path="/fleet"
          element={
            <ProtectedRoute>
              <RoleRoute allowedRoles={["fleet_manager"]}>
                <FleetLayout />
              </RoleRoute>
            </ProtectedRoute>
          }
        >
          <Route index element={<FleetDashboard />} />
          <Route path="vehicles" element={<FleetVehicles />} />
          <Route path="maintenance" element={<FleetMaintenance />} />
        </Route>

        {/* ================= STAFF ROUTES ================= */}
        <Route
          path="/staff"
          element={
            <ProtectedRoute>
              <RoleRoute allowedRoles={["staff"]}>
                <StaffLayout />
              </RoleRoute>
            </ProtectedRoute>
          }
        >
          <Route index element={<StaffDashboard />} />
          <Route path="bookings" element={<StaffBookings />} />
        </Route>

        {/* Fallback redirect */}
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
