import React, { useState, useEffect } from 'react';
import { CalendarCheck, CheckCircle2, XCircle, Key, CornerDownLeft, AlertCircle, Filter, Search } from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const AdminBookings = () => {
  const toast = useToast();
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
      toast.success('Booking confirmed successfully!');
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

  const filteredBookings = statusFilter
    ? bookings.filter((b) => b.status === statusFilter)
    : bookings;

  return (
    <div className="space-y-8">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <h1 className="text-3xl font-extrabold text-white tracking-tight">Booking Operations Workstation</h1>
          <p className="text-sm text-slate-400">Confirm reservations, process vehicle pickups, and record returns.</p>
        </div>

        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-slate-400" />
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-blue-500"
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
      </div>

      <div className="bg-theme-card border border-theme rounded-xl p-6 sm:p-8 space-y-6 shadow-xl">
        {loading ? (
          <div className="py-12 text-center text-slate-400 text-sm">Loading reservations...</div>
        ) : filteredBookings.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <CalendarCheck className="w-12 h-12 text-slate-700 mx-auto" />
            <p className="text-sm font-semibold text-slate-300">No Reservations Found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-slate-300">
              <thead className="text-xs uppercase bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Reference</th>
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Pickup / Return</th>
                  <th className="py-3.5 px-4 font-semibold">Total Price</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Workflow Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60">
                {filteredBookings.map((b) => (
                  <tr key={b.id} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-4 px-4 font-mono text-xs text-blue-400 font-bold">
                      {b.booking_reference}
                    </td>
                    <td className="py-4 px-4">
                      <p className="font-bold text-white">{b.user?.name || 'Customer'}</p>
                      <p className="text-[11px] text-slate-500">{b.user?.email}</p>
                    </td>
                    <td className="py-4 px-4 font-medium text-slate-200">
                      {b.vehicle ? `${b.vehicle.brand} ${b.vehicle.model}` : `Vehicle #${b.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-xs text-slate-400">
                      <div>P: {formatDate(b.pickup_date)}</div>
                      <div>R: {formatDate(b.return_date)}</div>
                    </td>
                    <td className="py-4 px-4 font-bold text-emerald-400">
                      {formatCurrency(b.total_price)}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(b.status)}`}>
                        {formatStatus(b.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-right space-x-2">
                      {b.status === 'pending' && (
                        <>
                          <button
                            onClick={() => handleConfirm(b.id)}
                            className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold"
                          >
                            Confirm
                          </button>
                          <button
                            onClick={() => {
                              setSelectedBooking(b);
                              setRejectModalOpen(true);
                            }}
                            className="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold"
                          >
                            Reject
                          </button>
                        </>
                      )}

                      {b.status === 'confirmed' && (
                        <button
                          onClick={() => handlePickup(b.id)}
                          className="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold flex items-center gap-1 ml-auto"
                        >
                          <Key className="w-3.5 h-3.5" />
                          <span>Mark Picked Up</span>
                        </button>
                      )}

                      {b.status === 'active' && (
                        <button
                          onClick={() => handleReturn(b.id)}
                          className="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold flex items-center gap-1 ml-auto"
                        >
                          <CornerDownLeft className="w-3.5 h-3.5" />
                          <span>Mark Returned</span>
                        </button>
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
      </div>

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
              <label className="block text-slate-300 font-semibold mb-1">Reason for Rejection (Optional)</label>
              <textarea
                rows="3"
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Vehicle maintenance, scheduling conflict, etc."
                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-100"
              />
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-sm shadow-lg shadow-rose-600/25"
            >
              {submitting ? 'Rejecting...' : 'Confirm Rejection'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default AdminBookings;
