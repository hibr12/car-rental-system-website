import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  CalendarCheck,
  Car,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  UserCheck,
  RotateCcw
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import useAuthStore from '../../store/authStore';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { StatCardSkeleton } from '../../components/common/Skeleton';
import { ManagementCard, ManagementEmptyState } from '../../components/management/ManagementUI';

export const StaffDashboard = () => {
  const { user } = useAuthStore();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    bookingApi
      .getAdminBookings({ per_page: 20 })
      .then((res) => {
        setBookings(res.data || []);
      })
      .catch((err) => console.error('Failed to load bookings:', err))
      .finally(() => setLoading(false));
  }, []);

  const today = new Date().toISOString().split('T')[0];
  const pendingBookings = bookings.filter((b) => b.status === 'pending').length;
  const activeBookings = bookings.filter((b) => b.status === 'active' || b.status === 'confirmed').length;
  const todayPickups = bookings.filter(
    (b) => b.pickup_date && b.pickup_date.startsWith(today) && (b.status === 'confirmed' || b.status === 'active')
  ).length;
  const todayReturns = bookings.filter(
    (b) => b.return_date && b.return_date.startsWith(today) && b.status === 'active'
  ).length;

  return (
    <div className="mgmt-page space-y-8">
      {/* Welcome Banner */}
      <ManagementCard className="rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-2">
          <span className="text-xs uppercase font-semibold tracking-wider text-[#64748B]">
            Staff Workstation
          </span>
          <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">
            Welcome, {user?.name || 'Staff Member'}!
          </h1>
          <p className="text-sm text-[#64748B] max-w-xl">
            Manage bookings, process vehicle pickups and returns, and assist customers with their reservations.
          </p>
        </div>
        <Link
          to="/staff/bookings"
          className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-sm transition-all self-start md:self-auto"
        >
          <CalendarCheck className="w-4 h-4" />
          <span>View All Bookings</span>
        </Link>
      </ManagementCard>

      {/* KPI Cards */}
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
            <ManagementCard className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Today&apos;s Pickups</span>
                <div className="w-10 h-10 rounded-xl bg-blue-50 text-[#2563EB] flex items-center justify-center border border-blue-100">
                  <Car className="w-5 h-5" />
                </div>
              </div>
              <p className="text-3xl font-extrabold text-[#2563EB]">{todayPickups}</p>
              <p className="text-[11px] text-[#64748B]">Vehicles to be picked up today</p>
            </ManagementCard>

            <ManagementCard className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Today&apos;s Returns</span>
                <div className="w-10 h-10 rounded-xl bg-blue-50 text-[#2563EB] flex items-center justify-center border border-blue-100">
                  <RotateCcw className="w-5 h-5" />
                </div>
              </div>
              <p className="text-3xl font-extrabold text-[#2563EB]">{todayReturns}</p>
              <p className="text-[11px] text-[#64748B]">Vehicles to be returned today</p>
            </ManagementCard>

            <ManagementCard className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Pending Confirmations</span>
                <div className="w-10 h-10 rounded-xl bg-amber-50 text-[#F59E0B] flex items-center justify-center border border-amber-100">
                  <AlertCircle className="w-5 h-5" />
                </div>
              </div>
              <p className="text-3xl font-extrabold text-[#F59E0B]">{pendingBookings}</p>
              <p className="text-[11px] text-[#64748B]">Awaiting staff approval</p>
            </ManagementCard>

            <ManagementCard className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Active Rentals</span>
                <div className="w-10 h-10 rounded-xl bg-green-50 text-[#16A34A] flex items-center justify-center border border-green-100">
                  <CheckCircle2 className="w-5 h-5" />
                </div>
              </div>
              <p className="text-3xl font-extrabold text-[#16A34A]">{activeBookings}</p>
              <p className="text-[11px] text-[#64748B]">Currently checked out</p>
            </ManagementCard>
          </>
        )}
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <Link
          to="/staff/bookings"
          className="bg-white border border-[#E2E8F0] p-6 rounded-xl hover:border-[#2563EB] transition-all duration-200 group"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-blue-50 text-[#2563EB] flex items-center justify-center border border-blue-100">
                <UserCheck className="w-6 h-6" />
              </div>
              <div>
                <h3 className="font-bold text-[#0F172A]">Process Bookings</h3>
                <p className="text-xs text-[#64748B]">Confirm pickups and process returns</p>
              </div>
            </div>
            <ArrowRight className="w-5 h-5 text-[#64748B] group-hover:text-[#2563EB] transition-colors" />
          </div>
        </Link>

        <Link
          to="/fleet/vehicles"
          className="bg-white border border-[#E2E8F0] p-6 rounded-xl hover:border-[#2563EB] transition-all duration-200 group"
        >
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-blue-50 text-[#2563EB] flex items-center justify-center border border-blue-100">
                <Car className="w-6 h-6" />
              </div>
              <div>
                <h3 className="font-bold text-[#0F172A]">Browse Vehicles</h3>
                <p className="text-xs text-[#64748B]">View available fleet inventory</p>
              </div>
            </div>
            <ArrowRight className="w-5 h-5 text-[#64748B] group-hover:text-[#2563EB] transition-colors" />
          </div>
        </Link>
      </div>

      {/* Recent Bookings Table */}
      <ManagementCard className="rounded-2xl space-y-6">
        <div className="flex items-center justify-between pb-4 border-b border-[#E2E8F0]">
          <div>
            <h3 className="text-lg font-bold text-[#0F172A]">Recent Bookings</h3>
            <p className="text-xs text-[#64748B]">Latest reservation activity requiring attention</p>
          </div>
          <Link
            to="/staff/bookings"
            className="text-xs font-semibold text-[#2563EB] hover:text-blue-700 flex items-center gap-1"
          >
            <span>View All</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {loading ? (
          <div className="space-y-3">
            <div className="h-10 bg-[#F8FAFC] rounded-xl animate-pulse" />
            <div className="h-10 bg-[#F8FAFC] rounded-xl animate-pulse" />
            <div className="h-10 bg-[#F8FAFC] rounded-xl animate-pulse" />
          </div>
        ) : bookings.length === 0 ? (
          <ManagementEmptyState
            icon={CalendarCheck}
            title="No Bookings Found"
            description="There are no bookings in the system yet."
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="mgmt-table-head">
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
              <tbody className="divide-y divide-[#E2E8F0]">
                {bookings.slice(0, 8).map((booking) => (
                  <tr key={booking.id} className="mgmt-table-row">
                    <td className="py-4 px-4 font-mono text-xs text-[#2563EB] font-bold">
                      {booking.booking_reference}
                    </td>
                    <td className="py-4 px-4 font-medium text-[#0F172A]">
                      {booking.user?.name || `User #${booking.user_id}`}
                    </td>
                    <td className="py-4 px-4 text-[#334155]">
                      {booking.vehicle
                        ? `${booking.vehicle.brand} ${booking.vehicle.model}`
                        : `Vehicle #${booking.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(booking.pickup_date)}</td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(booking.return_date)}</td>
                    <td className="py-4 px-4 font-bold text-[#16A34A]">
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
      </ManagementCard>
    </div>
  );
};

export default StaffDashboard;
