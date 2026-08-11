import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { CalendarCheck, Clock, CheckCircle2, Car, ArrowRight } from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import useAuthStore from '../../store/authStore';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { StatCardSkeleton } from '../../components/common/Skeleton';

export const CustomerDashboard = () => {
  const { user } = useAuthStore();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    bookingApi
      .getUserBookings({ per_page: 5 })
      .then((res) => {
        setBookings(res.data || []);
      })
      .catch((err) => console.error('Failed to load user bookings:', err))
      .finally(() => setLoading(false));
  }, []);

  const totalBookings = bookings.length;
  const activeBookings = bookings.filter((b) => b.status === 'active' || b.status === 'confirmed').length;
  const completedBookings = bookings.filter((b) => b.status === 'completed').length;
  const pendingBookings = bookings.filter((b) => b.status === 'pending').length;

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="bg-theme-card border border-theme p-8 rounded-3xl flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl transition-colors duration-200">
        <div className="space-y-2">
          <span className="text-xs uppercase font-extrabold tracking-wider text-blue-400">
            Customer Dashboard
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">
            Hello, {user?.name || 'Valued Client'}!
          </h1>
          <p className="text-sm text-theme-muted max-w-xl">
            Track active vehicle rentals, manage reservation dates, and review completed trips from your personal workstation.
          </p>
        </div>
        <Link
          to="/vehicles"
          className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg transition-all self-start md:self-auto"
        >
          <Car className="w-4 h-4" />
          <span>Book New Vehicle</span>
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
                <span className="text-xs font-semibold uppercase tracking-wider">Active Rentals</span>
                <Clock className="w-5 h-5 text-emerald-400" />
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

            <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-2 transition-colors duration-200">
              <div className="flex items-center justify-between text-theme-muted">
                <span className="text-xs font-semibold uppercase tracking-wider">Pending</span>
                <Clock className="w-5 h-5 text-amber-400" />
              </div>
              <p className="text-3xl font-extrabold text-amber-400">{pendingBookings}</p>
            </div>
          </>
        )}
      </div>

      {/* Recent Bookings Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        <div className="flex items-center justify-between pb-4 border-b border-theme">
          <div>
            <h3 className="text-lg font-bold text-theme-primary">Recent Booking Activity</h3>
            <p className="text-xs text-theme-muted">Your recent vehicle reservations</p>
          </div>
          <Link
            to="/dashboard/bookings"
            className="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1"
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
            <Car className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Reservations Yet</p>
            <p className="text-xs text-theme-muted max-w-xs mx-auto">
              Ready for a trip? Browse our catalog and book your first vehicle today.
            </p>
            <Link
              to="/vehicles"
              className="inline-block px-5 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-semibold"
            >
              Browse Vehicles
            </Link>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-hover text-theme-muted border-b border-theme">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Reference</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Pickup Date</th>
                  <th className="py-3.5 px-4 font-semibold">Return Date</th>
                  <th className="py-3.5 px-4 font-semibold">Total</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {bookings.map((booking) => (
                  <tr key={booking.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-mono text-xs text-blue-400 font-bold">
                      {booking.booking_reference}
                    </td>
                    <td className="py-4 px-4 font-medium text-theme-primary">
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

export default CustomerDashboard;
