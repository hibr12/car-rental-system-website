import React, { useState, useEffect, useCallback } from 'react';
import {
  CalendarCheck,
  Key,
  CornerDownLeft,
  Filter,
  Archive,
  Search,
  CheckCircle2,
  XCircle,
  Eye,
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import useAuthStore from '../../store/authStore';
import { isAdminRole, isBranchManagerRole } from '../../utils/roles';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const SUMMARY_CARDS = [
  { key: 'awaiting_branch_approval', label: 'Pending Branch Approval' },
  { key: 'payment_required', label: 'Payment Required' },
  { key: 'payment_processing', label: 'Payment Processing' },
  { key: 'awaiting_admin_approval', label: 'Awaiting Admin Approval' },
  { key: 'confirmed', label: 'Confirmed' },
  { key: 'ready_for_pickup', label: 'Ready for Pickup' },
  { key: 'active', label: 'Active' },
  { key: 'return_pending', label: 'Return Pending' },
  { key: 'completed', label: 'Completed' },
  { key: 'cancelled', label: 'Cancelled' },
  { key: 'rejected', label: 'Rejected' },
];

const hasAction = (booking, action) => (booking.allowed_actions || []).includes(action);

export const AdminBookings = () => {
  const toast = useToast();
  const { user } = useAuthStore();
  const isAdmin = isAdminRole(user?.role);
  const isBranchMgr = isBranchManagerRole(user?.role);
  const [bookings, setBookings] = useState([]);
  const [summary, setSummary] = useState({});
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [paymentFilter, setPaymentFilter] = useState('');
  const [branchApprovalFilter, setBranchApprovalFilter] = useState('');
  const [adminApprovalFilter, setAdminApprovalFilter] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [rejectReason, setRejectReason] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [archiveModalOpen, setArchiveModalOpen] = useState(false);
  const [archiveReason, setArchiveReason] = useState('');
  const [pickupModalOpen, setPickupModalOpen] = useState(false);
  const [returnModalOpen, setReturnModalOpen] = useState(false);
  const [viewModalOpen, setViewModalOpen] = useState(false);
  const [pickupForm, setPickupForm] = useState({
    identity_verification_status: 'verified',
    license_verification_status: 'verified',
    pickup_mileage: '',
    pickup_fuel_level: 'full',
  });
  const [returnForm, setReturnForm] = useState({
    return_mileage: '',
    return_fuel_level: 'full',
    damage_notes: '',
    requires_maintenance: false,
  });

  const fetchAdminBookings = useCallback(async () => {
    try {
      setLoading(true);
      const params = {
        page,
        per_page: 10,
      };
      if (statusFilter) params.status = statusFilter;
      if (paymentFilter) params.payment_status = paymentFilter;
      if (branchApprovalFilter) params.branch_approval_status = branchApprovalFilter;
      if (adminApprovalFilter) params.admin_approval_status = adminApprovalFilter;
      if (searchQuery.trim()) params.search = searchQuery.trim();

      const res = await bookingApi.getAdminBookings(params);
      setBookings(res.data || []);
      setSummary(res.summary || {});
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load system bookings.');
    } finally {
      setLoading(false);
    }
  }, [page, statusFilter, paymentFilter, branchApprovalFilter, adminApprovalFilter, searchQuery, toast]);

  useEffect(() => {
    fetchAdminBookings();
  }, [fetchAdminBookings]);

  const handleConfirm = async (id) => {
    if (!window.confirm('Approve this booking?')) return;
    try {
      const res = await bookingApi.confirm(id);
      toast.success(res.message || (isBranchMgr ? 'Branch approval recorded.' : 'Booking approved.'));
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to approve booking.');
    }
  };

  const handlePreparePickup = async (id) => {
    if (!window.confirm('Mark this booking as ready for pickup?')) return;
    try {
      await bookingApi.preparePickup(id);
      toast.success('Booking is ready for pickup.');
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to prepare pickup.');
    }
  };

  const handlePickupSubmit = async (e) => {
    e.preventDefault();
    if (!selectedBooking) return;
    try {
      setSubmitting(true);
      await bookingApi.pickup(selectedBooking.id, {
        ...pickupForm,
        pickup_mileage: Number(pickupForm.pickup_mileage) || 0,
      });
      toast.success('Vehicle marked as picked up (Active).');
      setPickupModalOpen(false);
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to mark vehicle as picked up.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleReturnSubmit = async (e) => {
    e.preventDefault();
    if (!selectedBooking) return;
    try {
      setSubmitting(true);
      await bookingApi.returnVehicle(selectedBooking.id, {
        ...returnForm,
        return_mileage: Number(returnForm.return_mileage) || 0,
      });
      toast.success('Vehicle marked as returned (Completed).');
      setReturnModalOpen(false);
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to mark vehicle as returned.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleRejectSubmit = async (e) => {
    e.preventDefault();
    if (!selectedBooking) return;
    if (rejectReason.trim().length < 3) {
      toast.error('A rejection reason is required.');
      return;
    }
    try {
      setSubmitting(true);
      await bookingApi.reject(selectedBooking.id, rejectReason);
      toast.success('Booking rejected.');
      setRejectModalOpen(false);
      setRejectReason('');
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to reject booking.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleArchiveSubmit = async (e) => {
    e.preventDefault();
    if (!selectedBooking) return;
    try {
      setSubmitting(true);
      await bookingApi.archive(selectedBooking.id, archiveReason);
      toast.success('Booking archived. Record preserved in archive.');
      setArchiveModalOpen(false);
      setArchiveReason('');
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to archive booking.');
    } finally {
      setSubmitting(false);
    }
  };

  const openPickup = (b) => {
    setSelectedBooking(b);
    setPickupForm({
      identity_verification_status: 'verified',
      license_verification_status: 'verified',
      pickup_mileage: b.vehicle?.mileage ?? '',
      pickup_fuel_level: 'full',
    });
    setPickupModalOpen(true);
  };

  const openReturn = (b) => {
    setSelectedBooking(b);
    setReturnForm({
      return_mileage: b.pickup_mileage || b.vehicle?.mileage || '',
      return_fuel_level: 'full',
      damage_notes: '',
      requires_maintenance: false,
    });
    setReturnModalOpen(true);
  };

  const renderActions = (b) => {
    const actions = [];

    actions.push(
      <ManagementButton
        key="view"
        variant="secondary"
        onClick={() => {
          setSelectedBooking(b);
          setViewModalOpen(true);
        }}
      >
        <Eye className="w-3.5 h-3.5" />
        View
      </ManagementButton>
    );

    if (hasAction(b, 'approve_branch') || hasAction(b, 'approve_admin')) {
      actions.push(
        <ManagementButton key="approve" variant="success" onClick={() => handleConfirm(b.id)}>
          <CheckCircle2 className="w-3.5 h-3.5" />
          Approve
        </ManagementButton>
      );
    }

    if (hasAction(b, 'reject_branch') || hasAction(b, 'reject_admin')) {
      actions.push(
        <ManagementButton
          key="reject"
          variant="dangerOutline"
          onClick={() => {
            setSelectedBooking(b);
            setRejectModalOpen(true);
          }}
        >
          <XCircle className="w-3.5 h-3.5" />
          Reject
        </ManagementButton>
      );
    }

    if (hasAction(b, 'prepare_pickup')) {
      actions.push(
        <ManagementButton key="prepare" onClick={() => handlePreparePickup(b.id)}>
          Prepare Pickup
        </ManagementButton>
      );
    }

    if (hasAction(b, 'mark_picked_up')) {
      actions.push(
        <ManagementButton key="pickup" onClick={() => openPickup(b)}>
          <Key className="w-3.5 h-3.5" />
          Mark Picked Up
        </ManagementButton>
      );
    }

    if (hasAction(b, 'mark_returned') || hasAction(b, 'complete_return')) {
      actions.push(
        <ManagementButton key="return" onClick={() => openReturn(b)}>
          <CornerDownLeft className="w-3.5 h-3.5" />
          Mark Returned
        </ManagementButton>
      );
    }

    if (hasAction(b, 'archive') && isAdmin) {
      actions.push(
        <ManagementButton
          key="archive"
          variant="secondary"
          onClick={() => {
            setSelectedBooking(b);
            setArchiveModalOpen(true);
          }}
        >
          <Archive className="w-3.5 h-3.5" />
          Archive
        </ManagementButton>
      );
    }

    return <div className="flex flex-wrap justify-end gap-2">{actions}</div>;
  };

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="Booking Operations"
        description="Payment, approvals, pickup, and returns — driven by backend workflow rules."
      />

      <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
        {SUMMARY_CARDS.map((card) => (
          <button
            key={card.key}
            type="button"
            onClick={() => {
              const map = {
                awaiting_branch_approval: 'pending_branch_approval',
                payment_required: 'payment_required',
                payment_processing: 'payment_processing',
                awaiting_admin_approval: 'pending_admin_approval',
                confirmed: 'confirmed',
                ready_for_pickup: 'ready_for_pickup',
                active: 'active',
                return_pending: 'return_pending',
                completed: 'completed',
                cancelled: 'cancelled',
                rejected: 'rejected',
              };
              setStatusFilter(map[card.key] || '');
              setPage(1);
            }}
            className="bg-white border border-[#E2E8F0] rounded-xl p-3 text-left hover:border-[#2563EB] transition-colors"
          >
            <p className="text-[10px] uppercase tracking-wide text-[#64748B] font-semibold">{card.label}</p>
            <p className="text-xl font-bold text-[#0F172A] mt-1">{summary[card.key] ?? 0}</p>
          </button>
        ))}
      </div>

      <ManagementCard className="space-y-4">
        <div className="flex flex-col lg:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-[#64748B] absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
                setPage(1);
              }}
              placeholder="Search reference, customer, vehicle, branch..."
              className="w-full pl-10 pr-3 py-2 bg-white border border-[#CBD5E1] rounded-xl text-sm text-[#0F172A]"
            />
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Filter className="w-4 h-4 text-[#64748B]" />
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                setPage(1);
              }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2 text-xs text-[#334155]"
            >
              <option value="">All Statuses</option>
              <option value="pending_branch_approval">Pending Branch Approval</option>
              <option value="payment_required">Payment Required</option>
              <option value="payment_processing">Payment Processing</option>
              <option value="pending_payment">Pending Payment (Legacy)</option>
              <option value="pending_admin_approval">Awaiting Admin Approval</option>
              <option value="confirmed">Confirmed</option>
              <option value="ready_for_pickup">Ready for Pickup</option>
              <option value="active">Active</option>
              <option value="return_pending">Return Pending</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
              <option value="rejected">Rejected</option>
            </select>
            <select
              value={paymentFilter}
              onChange={(e) => {
                setPaymentFilter(e.target.value);
                setPage(1);
              }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2 text-xs text-[#334155]"
            >
              <option value="">All Payments</option>
              <option value="unpaid">Unpaid</option>
              <option value="pending">Pending</option>
              <option value="cash_pending">Cash Pending</option>
              <option value="paid">Paid</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
            </select>
            <select
              value={branchApprovalFilter}
              onChange={(e) => {
                setBranchApprovalFilter(e.target.value);
                setPage(1);
              }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2 text-xs text-[#334155]"
            >
              <option value="">Branch Approval</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="not_required">Not Required</option>
            </select>
            <select
              value={adminApprovalFilter}
              onChange={(e) => {
                setAdminApprovalFilter(e.target.value);
                setPage(1);
              }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2 text-xs text-[#334155]"
            >
              <option value="">Admin Approval</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="not_required">Not Required</option>
            </select>
          </div>
        </div>

        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading reservations...</div>
        ) : bookings.length === 0 ? (
          <ManagementEmptyState
            icon={CalendarCheck}
            title={
              statusFilter === 'pending_branch_approval' || statusFilter === 'pending_admin_approval'
                ? 'No bookings awaiting approval.'
                : statusFilter === 'completed'
                ? 'No completed rentals yet.'
                : 'No Reservations Found'
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155] min-w-[1100px]">
              <thead className="text-xs uppercase bg-[#F8FAFC] text-[#334155] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Reference</th>
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Payment</th>
                  <th className="py-3.5 px-4 font-semibold">Branch Approval</th>
                  <th className="py-3.5 px-4 font-semibold">Admin Approval</th>
                  <th className="py-3.5 px-4 font-semibold">Booking Status</th>
                  <th className="py-3.5 px-4 font-semibold">Pickup / Return</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {bookings.map((b) => (
                  <tr key={b.id} className="hover:bg-[#F8FAFC] transition-colors">
                    <td className="py-4 px-4 font-mono text-xs text-[#2563EB] font-bold">
                      {b.booking_reference}
                    </td>
                    <td className="py-4 px-4">
                      <p className="font-bold text-[#0F172A]">{b.user?.name || 'Customer'}</p>
                      <p className="text-[11px] text-[#64748B]">{b.user?.email}</p>
                    </td>
                    <td className="py-4 px-4 font-medium text-[#334155]">
                      {b.vehicle ? `${b.vehicle.brand} ${b.vehicle.model}` : `Vehicle #${b.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{b.branch?.name || '—'}</td>
                    <td className="py-4 px-4">
                      <p className="font-bold text-[#16A34A] text-xs">{formatCurrency(b.total_price)}</p>
                      <span className={`inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(b.payment_status)}`}>
                        {formatStatus(b.payment_status)}
                      </span>
                      {b.payment_verification && (
                        <span className={`inline-block mt-1 ml-1 px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(b.payment_verification)}`}>
                          {formatStatus(b.payment_verification)}
                        </span>
                      )}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(b.branch_approval_status || b.branch_approval)}`}>
                        {formatStatus(b.branch_approval_status || b.branch_approval || 'pending')}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(b.admin_approval_status || b.admin_approval)}`}>
                        {formatStatus(b.admin_approval_status || b.admin_approval || 'not_required')}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(b.booking_status || b.status)}`}>
                        {formatStatus(b.booking_status || b.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">
                      <div>P: {formatDate(b.pickup_date)}</div>
                      <div>R: {formatDate(b.return_date)}</div>
                    </td>
                    <td className="py-4 px-4 text-right">{renderActions(b)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 && (
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(p) => setPage(p)}
          />
        )}
      </ManagementCard>

      {selectedBooking && viewModalOpen && (
        <Modal
          isOpen={viewModalOpen}
          onClose={() => setViewModalOpen(false)}
          title={`Booking ${selectedBooking.booking_reference}`}
          maxWidth="max-w-lg"
        >
          <div className="space-y-3 text-sm text-[#334155]">
            <p><span className="font-semibold">Customer:</span> {selectedBooking.user?.name}</p>
            <p><span className="font-semibold">Vehicle:</span> {selectedBooking.vehicle?.brand} {selectedBooking.vehicle?.model}</p>
            <p><span className="font-semibold">Branch:</span> {selectedBooking.branch?.name || '—'}</p>
            <p><span className="font-semibold">Payment:</span> {formatStatus(selectedBooking.payment_status)} / {formatStatus(selectedBooking.payment_verification || '—')}</p>
            <p><span className="font-semibold">Branch approval:</span> {formatStatus(selectedBooking.branch_approval_status)}</p>
            <p><span className="font-semibold">Admin approval:</span> {formatStatus(selectedBooking.admin_approval_status)}</p>
            <p><span className="font-semibold">Status:</span> {formatStatus(selectedBooking.booking_status || selectedBooking.status)}</p>
            <p><span className="font-semibold">Review:</span> {selectedBooking.has_review ? 'Submitted' : 'Not submitted'}</p>
          </div>
        </Modal>
      )}

      {selectedBooking && (
        <Modal
          isOpen={rejectModalOpen}
          onClose={() => setRejectModalOpen(false)}
          title={`Reject ${selectedBooking.booking_reference}`}
          maxWidth="max-w-md"
        >
          <form onSubmit={handleRejectSubmit} className="space-y-4 text-xs">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Reason for Rejection (required)</label>
              <textarea
                rows="3"
                required
                minLength={3}
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Vehicle unavailable, documents issue, operational conflict..."
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-3 text-[#0F172A]"
              />
            </div>
            <ManagementButton type="submit" variant="danger" disabled={submitting} className="w-full py-3.5">
              {submitting ? 'Rejecting...' : 'Confirm Rejection'}
            </ManagementButton>
          </form>
        </Modal>
      )}

      {selectedBooking && (
        <Modal
          isOpen={pickupModalOpen}
          onClose={() => setPickupModalOpen(false)}
          title={`Confirm Vehicle Handover — ${selectedBooking.booking_reference}`}
          maxWidth="max-w-md"
        >
          <form onSubmit={handlePickupSubmit} className="space-y-3 text-xs">
            <p className="text-[#64748B]">
              {selectedBooking.user?.name} · {selectedBooking.vehicle?.brand} {selectedBooking.vehicle?.model} · {selectedBooking.branch?.name}
            </p>
            <div>
              <label className="font-semibold text-[#334155]">Identity verification</label>
              <select
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2"
                value={pickupForm.identity_verification_status}
                onChange={(e) => setPickupForm({ ...pickupForm, identity_verification_status: e.target.value })}
              >
                <option value="verified">Verified</option>
                <option value="not_required">Not required</option>
                <option value="unverified">Unverified</option>
              </select>
            </div>
            <div>
              <label className="font-semibold text-[#334155]">Driver license</label>
              <select
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2"
                value={pickupForm.license_verification_status}
                onChange={(e) => setPickupForm({ ...pickupForm, license_verification_status: e.target.value })}
              >
                <option value="verified">Verified</option>
                <option value="not_required">Not required</option>
                <option value="unverified">Unverified</option>
              </select>
            </div>
            <div>
              <label className="font-semibold text-[#334155]">Mileage</label>
              <input
                type="number"
                required
                min="0"
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2"
                value={pickupForm.pickup_mileage}
                onChange={(e) => setPickupForm({ ...pickupForm, pickup_mileage: e.target.value })}
              />
            </div>
            <div>
              <label className="font-semibold text-[#334155]">Fuel level</label>
              <select
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2"
                value={pickupForm.pickup_fuel_level}
                onChange={(e) => setPickupForm({ ...pickupForm, pickup_fuel_level: e.target.value })}
              >
                <option value="full">Full</option>
                <option value="three_quarter">3/4</option>
                <option value="half">Half</option>
                <option value="quarter">Quarter</option>
                <option value="empty">Empty</option>
              </select>
            </div>
            <ManagementButton type="submit" disabled={submitting} className="w-full py-3">
              {submitting ? 'Processing...' : 'Confirm Vehicle Handover'}
            </ManagementButton>
          </form>
        </Modal>
      )}

      {selectedBooking && (
        <Modal
          isOpen={returnModalOpen}
          onClose={() => setReturnModalOpen(false)}
          title={`Complete Return — ${selectedBooking.booking_reference}`}
          maxWidth="max-w-md"
        >
          <form onSubmit={handleReturnSubmit} className="space-y-3 text-xs">
            <div>
              <label className="font-semibold text-[#334155]">Return mileage</label>
              <input
                type="number"
                required
                min="0"
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2"
                value={returnForm.return_mileage}
                onChange={(e) => setReturnForm({ ...returnForm, return_mileage: e.target.value })}
              />
            </div>
            <div>
              <label className="font-semibold text-[#334155]">Fuel level</label>
              <select
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2"
                value={returnForm.return_fuel_level}
                onChange={(e) => setReturnForm({ ...returnForm, return_fuel_level: e.target.value })}
              >
                <option value="full">Full</option>
                <option value="three_quarter">3/4</option>
                <option value="half">Half</option>
                <option value="quarter">Quarter</option>
                <option value="empty">Empty</option>
              </select>
            </div>
            <div>
              <label className="font-semibold text-[#334155]">Damage notes</label>
              <textarea
                rows="2"
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2"
                value={returnForm.damage_notes}
                onChange={(e) => setReturnForm({ ...returnForm, damage_notes: e.target.value })}
              />
            </div>
            <label className="flex items-center gap-2 text-[#334155]">
              <input
                type="checkbox"
                checked={returnForm.requires_maintenance}
                onChange={(e) => setReturnForm({ ...returnForm, requires_maintenance: e.target.checked })}
              />
              Send vehicle to maintenance
            </label>
            <ManagementButton type="submit" disabled={submitting} className="w-full py-3">
              {submitting ? 'Processing...' : 'Complete Return'}
            </ManagementButton>
          </form>
        </Modal>
      )}

      {selectedBooking && (
        <Modal
          isOpen={archiveModalOpen}
          onClose={() => setArchiveModalOpen(false)}
          title={`Archive Booking #${selectedBooking.booking_reference}`}
          maxWidth="max-w-md"
        >
          <form onSubmit={handleArchiveSubmit} className="space-y-4 text-xs">
            <p className="text-[#64748B]">
              This removes the booking from active lists but keeps the full record in the archive for compliance.
            </p>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Reason (optional)</label>
              <textarea
                rows="2"
                value={archiveReason}
                onChange={(e) => setArchiveReason(e.target.value)}
                className="w-full border border-[#E2E8F0] rounded-xl p-3 text-[#0F172A] bg-white"
              />
            </div>
            <ManagementButton type="submit" variant="secondary" disabled={submitting} className="w-full py-3">
              {submitting ? 'Archiving...' : 'Archive Booking'}
            </ManagementButton>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default AdminBookings;
