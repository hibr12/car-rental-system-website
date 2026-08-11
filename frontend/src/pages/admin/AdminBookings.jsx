import React, { useState, useEffect } from 'react';
import { CalendarCheck, CheckCircle2, XCircle, Key, CornerDownLeft, Filter, Archive, Eye } from 'lucide-react';
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

export const AdminBookings = () => {
  const toast = useToast();
  const { user } = useAuthStore();
  const isAdmin = isAdminRole(user?.role);
  const isBranchMgr = isBranchManagerRole(user?.role);
  const [bookings, setBookings] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');

  // Modal State for Reject Reason
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [rejectReason, setRejectReason] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [archiveModalOpen, setArchiveModalOpen] = useState(false);
  const [archiveReason, setArchiveReason] = useState('');

  const ARCHIVABLE_STATUSES = ['completed', 'cancelled', 'rejected', 'expired'];

  const fetchAdminBookings = async () => {
    try {
      setLoading(true);
      const res = await bookingApi.getAdminBookings({ page, per_page: 10 });
      setBookings(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error('Failed to load system bookings.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAdminBookings();
  }, [page]);

  const handleConfirm = async (id) => {
    try {
      await bookingApi.confirm(id);
      toast.success(isBranchMgr ? 'Booking branch-approved. Awaiting admin approval.' : 'Booking confirmed successfully!');
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to confirm booking.');
    }
  };

  const handlePickup = async (id) => {
    try {
      await bookingApi.pickup(id);
      toast.success('Vehicle marked as picked up (Active)!');
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to mark vehicle as picked up.');
    }
  };

  const handleReturn = async (id) => {
    try {
      await bookingApi.returnVehicle(id);
      toast.success('Vehicle marked as returned (Completed)!');
      fetchAdminBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to mark vehicle as returned.');
    }
  };

  const handleRejectSubmit = async (e) => {
    e.preventDefault();
    if (!selectedBooking) return;
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

  const filteredBookings = statusFilter
    ? bookings.filter((b) => b.status === statusFilter)
    : bookings;

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="Booking Operations Workstation"
        description="Confirm reservations, process vehicle pickups, and record returns."
        actions={
          <div className="flex items-center gap-2">
            <Filter className="w-4 h-4 text-[#64748B]" />
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2 text-xs text-[#334155] focus:outline-none focus:border-[#2563EB]"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="rejected">Rejected</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        }
      />

      <ManagementCard className="space-y-6">
        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading reservations...</div>
        ) : filteredBookings.length === 0 ? (
          <ManagementEmptyState icon={CalendarCheck} title="No Reservations Found" />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase bg-[#F8FAFC] text-[#334155] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Reference</th>
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Pickup / Return</th>
                  <th className="py-3.5 px-4 font-semibold">Total Price</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Branch Approval</th>
                  <th className="py-3.5 px-4 font-semibold">Admin Approval</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Workflow Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {filteredBookings.map((b) => (
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
                    <td className="py-4 px-4 text-xs text-[#64748B]">
                      <div>P: {formatDate(b.pickup_date)}</div>
                      <div>R: {formatDate(b.return_date)}</div>
                    </td>
                    <td className="py-4 px-4 font-bold text-[#16A34A]">
                      {formatCurrency(b.total_price)}
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">
                      {b.branch?.name || '—'}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(b.branch_approval_status || 'pending')}`}>
                        {formatStatus(b.branch_approval_status || 'pending')}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(b.admin_approval_status || 'pending')}`}>
                        {formatStatus(b.admin_approval_status || 'pending')}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(b.status)}`}>
                        {formatStatus(b.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-right space-x-2">
                      {b.status === 'pending' && isBranchMgr && b.branch_approval_status === 'pending' && (
                        <>
                          <ManagementButton variant="success" onClick={() => handleConfirm(b.id)}>
                            Approve (Branch)
                          </ManagementButton>
                          <ManagementButton variant="dangerOutline" onClick={() => { setSelectedBooking(b); setRejectModalOpen(true); }}>
                            Reject
                          </ManagementButton>
                        </>
                      )}
                      {b.status === 'pending' && isAdmin && b.branch_approval_status === 'approved' && b.admin_approval_status === 'pending' && (
                        <>
                          <ManagementButton variant="success" onClick={() => handleConfirm(b.id)}>
                            Final Approve
                          </ManagementButton>
                          <ManagementButton variant="dangerOutline" onClick={() => { setSelectedBooking(b); setRejectModalOpen(true); }}>
                            Reject
                          </ManagementButton>
                        </>
                      )}
                      {b.status === 'pending' && isAdmin && b.branch_approval_status === 'pending' && (
                        <span className="text-[10px] text-[#64748B]">Awaiting branch approval</span>
                      )}

                      {b.status === 'confirmed' && (
                        <ManagementButton onClick={() => handlePickup(b.id)} className="ml-auto">
                          <Key className="w-3.5 h-3.5" />
                          <span>Mark Picked Up</span>
                        </ManagementButton>
                      )}

                      {b.status === 'active' && (
                        <ManagementButton onClick={() => handleReturn(b.id)} className="ml-auto">
                          <CornerDownLeft className="w-3.5 h-3.5" />
                          <span>Mark Returned</span>
                        </ManagementButton>
                      )}

                      {isAdmin && ARCHIVABLE_STATUSES.includes(b.status) && (
                        <ManagementButton
                          variant="secondary"
                          onClick={() => { setSelectedBooking(b); setArchiveModalOpen(true); }}
                          title="Archive (record preserved)"
                        >
                          <Archive className="w-3.5 h-3.5" />
                          Archive
                        </ManagementButton>
                      )}
                    </td>
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

      {/* Reject Booking Reason Modal */}
      {selectedBooking && (
        <Modal
          isOpen={rejectModalOpen}
          onClose={() => setRejectModalOpen(false)}
          title={`Reject Reservation #${selectedBooking.booking_reference}`}
          maxWidth="max-w-md"
        >
          <form onSubmit={handleRejectSubmit} className="space-y-4 text-xs">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Reason for Rejection (Optional)</label>
              <textarea
                rows="3"
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Vehicle maintenance, scheduling conflict, etc."
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-3 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
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
                placeholder="e.g. Closed rental season 2024"
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
