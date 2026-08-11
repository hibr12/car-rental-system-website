import React, { useState, useEffect } from 'react';
import {
  CalendarCheck,
  CheckCircle2,
  XCircle,
  Car,
  Loader2,
  RefreshCw,
  Filter,
  Search
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { useToast } from '../../components/common/Toast';

export const StaffBookings = () => {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const toast = useToast();

  const fetchBookings = () => {
    setLoading(true);
    bookingApi
      .getAdminBookings({ per_page: 50 })
      .then((res) => {
        setBookings(res.data || []);
      })
      .catch((err) => {
        console.error('Failed to load bookings:', err);
        toast.error('Failed to load bookings.');
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchBookings();
  }, []);

  const handleConfirm = async (bookingId) => {
    try {
      await bookingApi.confirm(bookingId);
      toast.success('Booking confirmed successfully!');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to confirm booking.');
    }
  };

  const handleReject = async (bookingId) => {
    try {
      await bookingApi.reject(bookingId);
      toast.success('Booking rejected.');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to reject booking.');
    }
  };

  const handlePickup = async (bookingId) => {
    try {
      await bookingApi.pickup(bookingId);
      toast.success('Vehicle pickup recorded!');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to record pickup.');
    }
  };

  const handleReturn = async (bookingId) => {
    try {
      await bookingApi.returnVehicle(bookingId);
      toast.success('Vehicle return processed!');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to process return.');
    }
  };

  const filteredBookings = bookings.filter((b) => {
    const matchesStatus = !filterStatus || b.status === filterStatus;
    const matchesSearch =
      !searchQuery ||
      b.booking_reference?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      b.user?.name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      b.vehicle?.brand?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      b.vehicle?.model?.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  const getActionButtons = (booking) => {
    switch (booking.status) {
      case 'pending':
        return (
          <div className="flex items-center gap-2">
            <button
              onClick={() => handleConfirm(booking.id)}
              className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors"
            >
              <CheckCircle2 className="w-3 h-3 inline mr-1" />
              Confirm
            </button>
            <button
              onClick={() => handleReject(booking.id)}
              className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-colors"
            >
              <XCircle className="w-3 h-3 inline mr-1" />
              Reject
            </button>
          </div>
        );
      case 'confirmed':
        return (
          <button
            onClick={() => handlePickup(booking.id)}
            className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition-colors"
          >
            <Car className="w-3 h-3 inline mr-1" />
            Pickup
          </button>
        );
      case 'active':
        return (
          <button
            onClick={() => handleReturn(booking.id)}
            className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-purple-500/10 text-purple-400 border border-purple-500/20 hover:bg-purple-500/20 transition-colors"
          >
            <RefreshCw className="w-3 h-3 inline mr-1" />
            Return
          </button>
        );
      default:
        return null;
    }
  };

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-theme pb-6">
        <div>
          <span className="text-xs uppercase font-extrabold tracking-wider text-cyan-400">
            Staff Workstation
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Bookings Management</h1>
          <p className="text-sm text-theme-muted mt-1">Process pickups, returns, and manage booking statuses</p>
        </div>
        <button
          onClick={fetchBookings}
          className="px-4 py-2.5 rounded-xl bg-theme-secondary border border-theme hover:bg-theme-hover text-theme-secondary font-semibold text-xs flex items-center gap-2 transition-colors"
        >
          <RefreshCw className="w-4 h-4" />
          Refresh
        </button>
      </div>

      {/* Filters */}
      <div className="bg-theme-card border border-theme rounded-2xl p-4 flex flex-col sm:flex-row gap-4 transition-colors duration-200">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by reference, customer, or vehicle..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-cyan-500 transition-colors"
          />
        </div>
        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-theme-muted" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="bg-theme-input border border-theme rounded-xl px-3 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-cyan-500 transition-colors"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
      </div>

      {/* Bookings Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        {loading ? (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <tbody>
                {[1, 2, 3, 4, 5].map((i) => (
                  <TableRowSkeleton key={i} cols={7} />
                ))}
              </tbody>
            </table>
          </div>
        ) : filteredBookings.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <CalendarCheck className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Bookings Found</p>
            <p className="text-xs text-theme-muted">
              {searchQuery || filterStatus ? 'Try adjusting your filters.' : 'No bookings in the system yet.'}
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
                  <th className="py-3.5 px-4 font-semibold">Dates</th>
                  <th className="py-3.5 px-4 font-semibold">Total</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {filteredBookings.map((booking) => (
                  <tr key={booking.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-mono text-xs text-cyan-400 font-bold">
                      {booking.booking_reference}
                    </td>
                    <td className="py-4 px-4 font-medium text-theme-primary">
                      {booking.user?.name || `User #${booking.user_id}`}
                    </td>
                    <td className="py-4 px-4">
                      <p className="font-medium text-theme-primary">
                        {booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model}` : `#${booking.vehicle_id}`}
                      </p>
                      <p className="text-[11px] text-theme-muted">{booking.pickup_location}</p>
                    </td>
                    <td className="py-4 px-4 text-xs text-theme-muted">
                      <p>{formatDate(booking.pickup_date)}</p>
                      <p className="text-theme-muted/70">to {formatDate(booking.return_date)}</p>
                    </td>
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
                    <td className="py-4 px-4">
                      {getActionButtons(booking)}
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

export default StaffBookings;
