import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  CalendarCheck,
  Car,
  Clock,
  CheckCircle2,
  AlertCircle,
  Loader2,
  ArrowRight,
  UserCheck,
  RotateCcw
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import useAuthStore from '../../store/authStore';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { StatCardSkeleton } from '../../components/common/Skeleton';

export const StaffDashboard = () => {
  const { user } = useAuthStore();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    bookingApi
      .getAdminBookings({ per_page: 10 })
      .then((res) => {
        setBookings(res.data || []);
      })
      .catch((err) => console.error('Failed to load bookings:', err))
      .finally(() => setLoading(false));
  }, []);

  const totalBookings = bookings.length;
  const pendingBookings = bookings.filter((b) => b.status === 'pending').length;
  const activeBookings = bookings.filter((b) => b.status === 'active' || b.status === 'confirmed').length;
  const completedBookings = bookings.filter((b) => b.status === 'completed').length;
  const needsAction = bookings.filter((b) => b.status === 'pending' || b.status === 'confirmed').length;

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="bg-cyan-900/60 border border-theme p-8 rounded-3xl flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl transition-colors duration-200">
        <div className="space-y-2">
          <span className="text-xs uppercase font-extrabold tracking-wider text-cyan-400">
            Staff Workstation
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">
            Welcome, {user?.name || 'Staff Member'}!
          </h1>
          <p className="text-sm text-theme-muted max-w-xl">
            Manage bookings, process vehicle pickups and returns, and assist customers with their reservations.
          </p>
        </div>
        <Link
          to="/staff/bookings"
          className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-sm shadow-lg shadow-cyan-600/25 transition-all self-start md:self-auto"
        >
          <CalendarCheck className="w-4 h-4" />
          <span>View All Bookings</span>
        </Link>
      </div>

      {/* Metrics Row */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {loading ? (
          <>
            <StatCardSkeleton />
            <StatCardSkeleton />
            <StatCardSkeleton />
            <StatCardSkeleton />
          </>
        ) : (
          <>
            <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-2 transition-colors duration-200">
              <div className="flex items-center justify-between text-theme-muted">
                <span className="text-xs font-semibold uppercase tracking-wider">Total Bookings</span>
                <CalendarCheck className="w-5 h-5 text-blue-400" />
              </div>
              <p className="text-3xl font-extrabold text-theme-primary">{totalBookings}</p>
            </div>

            <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-2 transition-colors duration-200">
              <div className="flex items-center justify-between text-theme-muted">
                <span className="text-xs font-semibold uppercase tracking-wider">Needs Action</span>
                <AlertCircle className="w-5 h-5 text-amber-400" />
              </div>
              <p className="text-3xl font-extrabold text-amber-400">{needsAction}</p>
            </div>

            <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-2 transition-colors duration-200">
              <div className="flex items-center justify-between text-theme-muted">
                <span className="text-xs font-semibold uppercase tracking-wider">Active Rentals</span>
                <Car className="w-5 h-5 text-emerald-400" />
              </div>
              <p className="text-3xl font-extrabold text-emerald-400">{activeBookings}</p>
            </div>

            <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-2 transition-colors duration-200">
              <div className="flex items-center justify-between text-theme-muted">
                <span className="text-xs font-semibold uppercase tracking-wider">Completed</span>
                <CheckCircle2 className="w-5 h-5 text-purple-400" />
              </div>
              <p className="text-3xl font-extrabold text-purple-400">{completedBookings}</p>
            </div>
          </>
        )}
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <Link
          to="/staff/bookings"
          className="bg-theme-card border border-theme p-6 rounded-2xl hover:border-cyan-500/50 transition-all duration-200 group"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center border border-cyan-500/20">
                <UserCheck className="w-6 h-6" />
              </div>
              <div>
                <h3 className="font-bold text-theme-primary">Process Bookings</h3>
                <p className="text-xs text-theme-muted">Confirm pickups and process returns</p>
              </div>
            </div>
            <ArrowRight className="w-5 h-5 text-theme-muted group-hover:text-cyan-400 transition-colors" />
          </div>
        </Link>

        <Link
          to="/vehicles"
          className="bg-theme-card border border-theme p-6 rounded-2xl hover:border-blue-500/50 transition-all duration-200 group"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
                <Car className="w-6 h-6" />
              </div>
              <div>
                <h3 className="font-bold text-theme-primary">Browse Vehicles</h3>
                <p className="text-xs text-theme-muted">View available fleet inventory</p>
              </div>
            </div>
            <ArrowRight className="w-5 h-5 text-theme-muted group-hover:text-blue-400 transition-colors" />
          </div>
        </Link>
      </div>

      {/* Recent Bookings Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        <div className="flex items-center justify-between pb-4 border-b border-theme">
          <div>
            <h3 className="text-lg font-bold text-theme-primary">Recent Bookings</h3>
            <p className="text-xs text-theme-muted">Latest reservation activity requiring attention</p>
          </div>
          <Link
            to="/staff/bookings"
            className="text-xs font-semibold text-cyan-400 hover:text-cyan-300 flex items-center gap-1"
          >
            <span>View All</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {loading ? (
          <div className="space-y-3">
            <div className="h-10 bg-theme-hover rounded-xl animate-pulse" />
            <div className="h-10 bg-theme-hover rounded-xl animate-pulse" />
            <div className="h-10 bg-theme-hover rounded-xl animate-pulse" />
          </div>
        ) : bookings.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <CalendarCheck className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Bookings Found</p>
            <p className="text-xs text-theme-muted max-w-xs mx-auto">
              There are no bookings in the system yet.
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-hover text-theme-muted border-b border-theme">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Reference</th>
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Pickup</th>
                  <th className="py-3.5 px-4 font-semibold">Return</th>
                  <th className="py-3.5 px-4 font-semibold">Total</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {bookings.slice(0, 8).map((booking) => (
                  <tr key={booking.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-mono text-xs text-cyan-400 font-bold">
                      {booking.booking_reference}
                    </td>
                    <td className="py-4 px-4 font-medium text-theme-primary">
                      {booking.user?.name || `User #${booking.user_id}`}
                    </td>
                    <td className="py-4 px-4 text-theme-secondary">
                      {booking.vehicle
                        ? `${booking.vehicle.brand} ${booking.vehicle.model}`
                        : `Vehicle #${booking.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-xs text-theme-muted">{formatDate(booking.pickup_date)}</td>
                    <td className="py-4 px-4 text-xs text-theme-muted">{formatDate(booking.return_date)}</td>
                    <td className="py-4 px-4 font-bold text-emerald-400">
                      {formatCurrency(booking.total_price)}
                    </td>
                    <td className="py-4 px-4">
                      <span
                        className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(
                          booking.status
                        )}`}
                      >
                        {formatStatus(booking.status)}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default StaffDashboard;
