import React, { useState, useEffect, useCallback } from 'react';
import {
  CalendarCheck,
  CheckCircle2,
  XCircle,
  RefreshCw,
  Filter,
  Search,
  Key,
  CornerDownLeft,
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { useToast } from '../../components/common/Toast';
import Modal from '../../components/common/Modal';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const hasAction = (booking, action) => (booking.allowed_actions || []).includes(action);

export const StaffBookings = () => {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [rejectOpen, setRejectOpen] = useState(false);
  const [selected, setSelected] = useState(null);
  const [rejectReason, setRejectReason] = useState('');
  const toast = useToast();

  const fetchBookings = useCallback(() => {
    setLoading(true);
    const params = { per_page: 50 };
    if (filterStatus) params.status = filterStatus;
    if (searchQuery.trim()) params.search = searchQuery.trim();

    bookingApi
      .getAdminBookings(params)
      .then((res) => {
        setBookings(res.data || []);
      })
      .catch((err) => {
        toast.error(err.message || 'Failed to load bookings.');
      })
      .finally(() => setLoading(false));
  }, [filterStatus, searchQuery, toast]);

  useEffect(() => {
    fetchBookings();
  }, [fetchBookings]);

  const handleConfirm = async (bookingId) => {
    if (!window.confirm('Approve this booking for your branch?')) return;
    try {
      const res = await bookingApi.confirm(bookingId);
      toast.success(res.message || 'Booking approved.');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to approve booking.');
    }
  };

  const handleRejectSubmit = async (e) => {
    e.preventDefault();
    if (!selected) return;
    if (rejectReason.trim().length < 3) {
      toast.error('A rejection reason is required.');
      return;
    }
    try {
      await bookingApi.reject(selected.id, rejectReason);
      toast.success('Booking rejected.');
      setRejectOpen(false);
      setRejectReason('');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to reject booking.');
    }
  };

  const handlePreparePickup = async (bookingId) => {
    try {
      await bookingApi.preparePickup(bookingId);
      toast.success('Ready for pickup.');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to prepare pickup.');
    }
  };

  const handlePickup = async (booking) => {
    if (!window.confirm(`Confirm vehicle handover for ${booking.booking_reference}?`)) return;
    try {
      await bookingApi.pickup(booking.id, {
        identity_verification_status: 'verified',
        license_verification_status: 'verified',
        pickup_mileage: booking.vehicle?.mileage || 0,
        pickup_fuel_level: 'full',
      });
      toast.success('Vehicle pickup recorded!');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to record pickup.');
    }
  };

  const handleReturn = async (booking) => {
    if (!window.confirm(`Complete return for ${booking.booking_reference}?`)) return;
    try {
      await bookingApi.returnVehicle(booking.id, {
        return_mileage: booking.pickup_mileage || booking.vehicle?.mileage || 0,
        return_fuel_level: 'full',
      });
      toast.success('Vehicle return processed!');
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to process return.');
    }
  };

  const getActionButtons = (booking) => (
    <div className="flex flex-wrap items-center gap-2">
      {(hasAction(booking, 'approve_branch') || hasAction(booking, 'approve_admin')) && (
        <button
          onClick={() => handleConfirm(booking.id)}
          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-green-50 text-[#16A34A] border border-green-200 hover:bg-green-100"
        >
          <CheckCircle2 className="w-3 h-3 inline mr-1" />
          Approve
        </button>
      )}
      {(hasAction(booking, 'reject_branch') || hasAction(booking, 'reject_admin')) && (
        <button
          onClick={() => {
            setSelected(booking);
            setRejectOpen(true);
          }}
          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-red-50 text-[#DC2626] border border-red-200 hover:bg-red-100"
        >
          <XCircle className="w-3 h-3 inline mr-1" />
          Reject
        </button>
      )}
      {hasAction(booking, 'prepare_pickup') && (
        <button
          onClick={() => handlePreparePickup(booking.id)}
          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-[#2563EB] border border-blue-200"
        >
          Prepare Pickup
        </button>
      )}
      {hasAction(booking, 'mark_picked_up') && (
        <button
          onClick={() => handlePickup(booking)}
          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-[#2563EB] border border-blue-200"
        >
          <Key className="w-3 h-3 inline mr-1" />
          Mark Picked Up
        </button>
      )}
      {(hasAction(booking, 'mark_returned') || hasAction(booking, 'complete_return')) && (
        <button
          onClick={() => handleReturn(booking)}
          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-[#2563EB] border border-blue-200"
        >
          <CornerDownLeft className="w-3 h-3 inline mr-1" />
          Mark Returned
        </button>
      )}
    </div>
  );

  return (
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Staff Workstation"
        title="Bookings Management"
        description="Approve branch bookings, process pickups and returns for your branch only."
        actions={
          <ManagementButton variant="secondary" onClick={fetchBookings}>
            <RefreshCw className="w-4 h-4" />
            Refresh
          </ManagementButton>
        }
      />

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
            <option value="pending_branch_approval">Pending Branch Approval</option>
            <option value="payment_required">Payment Required</option>
            <option value="payment_processing">Payment Processing</option>
            <option value="pending_payment">Pending Payment (Legacy)</option>
            <option value="confirmed">Confirmed</option>
            <option value="ready_for_pickup">Ready for Pickup</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
      </ManagementCard>

      <ManagementCard className="rounded-2xl space-y-6">
        {loading ? (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <tbody>
                {[1, 2, 3, 4, 5].map((i) => (
                  <TableRowSkeleton key={i} cols={8} />
                ))}
              </tbody>
            </table>
          </div>
        ) : bookings.length === 0 ? (
          <ManagementEmptyState
            icon={CalendarCheck}
            title="No Bookings Found"
            description={
              searchQuery || filterStatus ? 'Try adjusting your filters.' : 'No bookings for your branch yet.'
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
                  <th className="py-3.5 px-4 font-semibold">Payment</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Dates</th>
                  <th className="py-3.5 px-4 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {bookings.map((booking) => (
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
                      <p className="text-[11px] text-[#64748B]">{booking.branch?.name}</p>
                    </td>
                    <td className="py-4 px-4">
                      <p className="font-bold text-[#16A34A] text-xs">{formatCurrency(booking.total_price)}</p>
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(booking.payment_status)}`}>
                        {formatStatus(booking.payment_status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(booking.branch_approval_status)}`}>
                        {formatStatus(booking.branch_approval_status || 'pending')}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(booking.booking_status || booking.status)}`}>
                        {formatStatus(booking.booking_status || booking.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">
                      <p>{formatDate(booking.pickup_date)}</p>
                      <p>to {formatDate(booking.return_date)}</p>
                    </td>
                    <td className="py-4 px-4">{getActionButtons(booking)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </ManagementCard>

      {selected && (
        <Modal isOpen={rejectOpen} onClose={() => setRejectOpen(false)} title="Reject Booking" maxWidth="max-w-md">
          <form onSubmit={handleRejectSubmit} className="space-y-3 text-xs">
            <textarea
              required
              minLength={3}
              rows={3}
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              placeholder="Reason required..."
              className="w-full border border-[#CBD5E1] rounded-xl p-3"
            />
            <ManagementButton type="submit" variant="danger" className="w-full py-3">
              Confirm Rejection
            </ManagementButton>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default StaffBookings;
