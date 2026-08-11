import React, { useState, useEffect } from 'react';
import {
  CalendarCheck,
  CheckCircle2,
  XCircle,
  Car,
  RefreshCw,
  Filter,
  Search
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

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
              className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-green-50 text-[#16A34A] border border-green-200 hover:bg-green-100 transition-colors"
            >
              <CheckCircle2 className="w-3 h-3 inline mr-1" />
              Confirm
            </button>
            <button
              onClick={() => handleReject(booking.id)}
              className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-red-50 text-[#DC2626] border border-red-200 hover:bg-red-100 transition-colors"
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
            className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-[#2563EB] border border-blue-200 hover:bg-blue-100 transition-colors"
          >
            <Car className="w-3 h-3 inline mr-1" />
            Pickup
          </button>
        );
      case 'active':
        return (
          <button
            onClick={() => handleReturn(booking.id)}
            className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-[#2563EB] border border-blue-200 hover:bg-blue-100 transition-colors"
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
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Staff Workstation"
        title="Bookings Management"
        description="Process pickups, returns, and manage booking statuses"
        actions={
          <ManagementButton variant="secondary" onClick={fetchBookings}>
            <RefreshCw className="w-4 h-4" />
            Refresh
          </ManagementButton>
        }
      />

      {/* Filters */}
      <ManagementCard className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by reference, customer, or vehicle..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="mgmt-input pl-10"
          />
        </div>
        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-[#64748B]" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="mgmt-input w-auto"
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
      </ManagementCard>

      {/* Bookings Table */}
      <ManagementCard className="rounded-2xl space-y-6">
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
          <ManagementEmptyState
            icon={CalendarCheck}
            title="No Bookings Found"
            description={
              searchQuery || filterStatus ? 'Try adjusting your filters.' : 'No bookings in the system yet.'
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="mgmt-table-head">
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
              <tbody className="divide-y divide-[#E2E8F0]">
                {filteredBookings.map((booking) => (
                  <tr key={booking.id} className="mgmt-table-row">
                    <td className="py-4 px-4 font-mono text-xs text-[#2563EB] font-bold">
                      {booking.booking_reference}
                    </td>
                    <td className="py-4 px-4 font-medium text-[#0F172A]">
                      {booking.user?.name || `User #${booking.user_id}`}
                    </td>
                    <td className="py-4 px-4">
                      <p className="font-medium text-[#0F172A]">
                        {booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model}` : `#${booking.vehicle_id}`}
                      </p>
                      <p className="text-[11px] text-[#64748B]">{booking.pickup_location}</p>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">
                      <p>{formatDate(booking.pickup_date)}</p>
                      <p>to {formatDate(booking.return_date)}</p>
                    </td>
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
                    <td className="py-4 px-4">
                      {getActionButtons(booking)}
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

export default StaffBookings;
