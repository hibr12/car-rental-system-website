import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { CalendarCheck, AlertCircle, XCircle, CheckCircle2, Eye, Filter, CreditCard } from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const CustomerBookings = () => {
  const toast = useToast();
  const [bookings, setBookings] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);

  // Selected booking for Cancellation or Detail view
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [cancelModalOpen, setCancelModalOpen] = useState(false);
  const [detailModalOpen, setDetailModalOpen] = useState(false);
  const [cancelling, setCancelling] = useState(false);

  const fetchBookings = async () => {
    try {
      setLoading(true);
      const res = await bookingApi.getUserBookings({ page, per_page: 10 });
      setBookings(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load bookings.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchBookings();
  }, [page]);

  const handleCancelBooking = async () => {
    if (!selectedBooking) return;
    try {
      setCancelling(true);
      await bookingApi.cancel(selectedBooking.id);
      toast.success('Booking cancelled successfully.');
      setCancelModalOpen(false);
      fetchBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to cancel booking.');
    } finally {
      setCancelling(false);
    }
  };

  const filteredBookings = statusFilter
    ? bookings.filter((b) => (b.booking_status || b.status) === statusFilter)
    : bookings;

  const paymentHint = (booking) => {
    const bookingStatus = booking.booking_status || booking.status;
    const paymentStatus = booking.payment_status;
    const canPay = (booking.allowed_actions || []).includes('pay');

    if (canPay) return 'Booking approved - payment required';
    if (bookingStatus === 'pending_branch_approval') return 'Payment available after branch approval';
    if (paymentStatus === 'cash_pending') return 'Awaiting cash verification at branch';
    if (paymentStatus === 'failed' || paymentStatus === 'invalid') return 'Payment failed - try again';
    if (paymentStatus === 'paid') return 'Payment verified';
    if (bookingStatus === 'cancelled' || bookingStatus === 'rejected') return 'Payment unavailable for this booking';
    return '';
  };

  return (
    <div className="space-y-8">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-theme pb-6">
        <div>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">My Rental Bookings</h1>
          <p className="text-sm text-theme-muted">View and manage your current and past vehicle reservations.</p>
        </div>

        {/* Filter Dropdown */}
        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-theme-muted" />
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="bg-theme-card border border-theme rounded-xl px-3 py-2 text-xs text-theme-primary focus:outline-none focus:border-blue-500"
          >
            <option value="">All Statuses</option>
            <option value="pending_branch_approval">Pending Branch Approval</option>
            <option value="payment_required">Payment Required</option>
            <option value="payment_processing">Payment Processing</option>
            <option value="confirmed">Confirmed</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      {/* Bookings Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
        {loading ? (
          <div className="py-12 text-center text-theme-muted text-sm">Loading bookings...</div>
        ) : filteredBookings.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <CalendarCheck className="w-12 h-12 text-slate-700 mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Reservations Found</p>
            <p className="text-xs text-theme-muted">You have no bookings matching the selected filter.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-secondary text-theme-muted border-b border-theme">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Reference</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Pickup</th>
                  <th className="py-3.5 px-4 font-semibold">Return</th>
                  <th className="py-3.5 px-4 font-semibold">Total Price</th>
                  <th className="py-3.5 px-4 font-semibold">Booking Status</th>
                  <th className="py-3.5 px-4 font-semibold">Payment</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60">
                {filteredBookings.map((booking) => (
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
                          booking.booking_status || booking.status
                        )}`}
                      >
                        {formatStatus(booking.booking_status || booking.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span
                        className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(
                          booking.payment_status
                        )}`}
                      >
                        {formatStatus(booking.payment_status)}
                      </span>
                      {paymentHint(booking) && (
                        <p className="mt-1 text-[11px] text-theme-muted">{paymentHint(booking)}</p>
                      )}
                    </td>
                    <td className="py-4 px-4 text-right space-x-2">
                      <button
                        onClick={() => {
                          setSelectedBooking(booking);
                          setDetailModalOpen(true);
                        }}
                        className="px-3 py-1.5 rounded-lg bg-theme-hover hover:bg-theme-hover text-xs font-semibold text-theme-secondary"
                      >
                        View
                      </button>

                      {(booking.allowed_actions || []).includes('pay') && (
                        <Link
                          to={`/checkout?booking_id=${booking.id}`}
                          className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold"
                        >
                          <CreditCard className="w-3 h-3" />
                          Pay Now
                        </Link>
                      )}

                      {(booking.allowed_actions || []).includes('cancel') && (
                        <button
                          onClick={() => {
                            setSelectedBooking(booking);
                            setCancelModalOpen(true);
                          }}
                          className="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold"
                        >
                          Cancel
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

      {/* Booking Detail Modal */}
      {selectedBooking && (
        <Modal
          isOpen={detailModalOpen}
          onClose={() => setDetailModalOpen(false)}
          title={`Booking Details #${selectedBooking.booking_reference}`}
        >
          <div className="space-y-6 text-sm text-theme-secondary">
            <div className="grid grid-cols-2 gap-4 bg-theme-secondary p-4 rounded-2xl border border-theme">
              <div>
                <p className="text-xs text-theme-muted">Pickup Location</p>
                <p className="font-semibold text-theme-primary">{selectedBooking.pickup_location}</p>
              </div>
              <div>
                <p className="text-xs text-theme-muted">Return Location</p>
                <p className="font-semibold text-theme-primary">{selectedBooking.return_location}</p>
              </div>
              <div>
                <p className="text-xs text-theme-muted">Pickup Date</p>
                <p className="font-semibold text-theme-primary">{formatDate(selectedBooking.pickup_date, true)}</p>
              </div>
              <div>
                <p className="text-xs text-theme-muted">Return Date</p>
                <p className="font-semibold text-theme-primary">{formatDate(selectedBooking.return_date, true)}</p>
              </div>
            </div>

            <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-2">
              <div className="flex justify-between text-xs">
                <span>Rental Duration:</span>
                <span className="font-bold text-theme-primary">{selectedBooking.number_of_days} Days</span>
              </div>
              <div className="flex justify-between text-xs">
                <span>Price / Day:</span>
                <span className="font-bold text-theme-primary">{formatCurrency(selectedBooking.price_per_day)}</span>
              </div>
              <div className="flex justify-between text-xs pt-2 border-t border-theme">
                <span className="font-bold text-theme-primary">Total Price:</span>
                <span className="font-extrabold text-emerald-400 text-base">
                  {formatCurrency(selectedBooking.total_price)}
                </span>
              </div>
            </div>

            {selectedBooking.notes && (
              <div>
                <p className="text-xs text-theme-muted">Special Notes:</p>
                <p className="p-3 bg-theme-secondary rounded-xl text-xs italic border border-theme">
                  {selectedBooking.notes}
                </p>
              </div>
            )}
          </div>
        </Modal>
      )}

      {/* Cancel Confirmation Modal */}
      {selectedBooking && (
        <Modal
          isOpen={cancelModalOpen}
          onClose={() => setCancelModalOpen(false)}
          title="Cancel Reservation"
          maxWidth="max-w-md"
        >
          <div className="space-y-4 text-center">
            <XCircle className="w-12 h-12 text-rose-400 mx-auto" />
            <h3 className="text-lg font-bold text-theme-primary">Are you sure?</h3>
            <p className="text-xs text-theme-muted">
              Are you sure you want to cancel booking <strong className="text-theme-primary">{selectedBooking.booking_reference}</strong>? This action cannot be undone.
            </p>

            <div className="pt-4 flex gap-3">
              <button
                onClick={() => setCancelModalOpen(false)}
                className="w-full py-2.5 rounded-xl border border-theme text-theme-secondary hover:bg-theme-hover text-xs font-semibold"
              >
                No, Keep Booking
              </button>
              <button
                onClick={handleCancelBooking}
                disabled={cancelling}
                className="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md shadow-rose-600/20"
              >
                {cancelling ? 'Cancelling...' : 'Yes, Cancel Reservation'}
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
};

export default CustomerBookings;
