import React, { useEffect, useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { Calendar, MapPin, AlertCircle, CheckCircle, ShieldCheck, CreditCard, Loader2 } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import bookingApi from '../../api/bookingApi';
import { formatCurrency } from '../../utils/formatters';
import { useToast } from '../common/Toast';

export const RentalCalculator = ({ vehicle }) => {
  const { isAuthenticated, user } = useAuthStore();
  const navigate = useNavigate();
  const toast = useToast();

  const today = new Date().toISOString().split('T')[0];
  const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];

  const [pickupDate, setPickupDate] = useState(today);
  const [returnDate, setReturnDate] = useState(tomorrow);
  const [pickupLocation, setPickupLocation] = useState('Headquarters Terminal');
  const [returnLocation, setReturnLocation] = useState('Headquarters Terminal');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);
  const [bookingSuccess, setBookingSuccess] = useState(null);

  const rentalDays = useMemo(() => {
    if (!pickupDate || !returnDate) return 1;
    const start = new Date(pickupDate);
    const end = new Date(returnDate);
    const diffTime = end.getTime() - start.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 1;
  }, [pickupDate, returnDate]);

  const estimatedSubtotal = useMemo(() => {
    return rentalDays * (vehicle?.rental_price_per_day || 0);
  }, [rentalDays, vehicle]);

  useEffect(() => {
    if (!bookingSuccess) return;
    // After creation, go to the customer dashboard so payment is visible immediately.
    const t = setTimeout(() => navigate('/dashboard'), 1200);
    return () => clearTimeout(t);
  }, [bookingSuccess, navigate]);

  const handleBookingSubmit = async (e) => {
    e.preventDefault();

    if (!isAuthenticated) {
      toast.warning('Please log in or create an account to book a vehicle.');
      navigate('/login', { state: { from: window.location.pathname } });
      return;
    }

    if (user?.role !== 'customer') {
      toast.warning('Only customer accounts can create new rental bookings.');
      return;
    }

    try {
      setLoading(true);
      const payload = {
        vehicle_id: vehicle.id,
        pickup_location: pickupLocation,
        return_location: returnLocation,
        pickup_date: pickupDate,
        return_date: returnDate,
        notes: notes.trim() || undefined,
      };

      const res = await bookingApi.create(payload);
      const bookingData = res.data?.booking || res.data;
      
      setBookingSuccess(bookingData);
      toast.success('Booking created successfully!');
    } catch (err) {
      toast.error(err.message || 'Failed to submit booking request.');
    } finally {
      setLoading(false);
    }
  };

  if (bookingSuccess) {
    return (
      <div className="bg-theme-card border border-emerald-500/30 rounded-3xl p-8 space-y-6 text-center shadow-2xl animate-in zoom-in-95 duration-200">
        <div className="w-16 h-16 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/30 shadow-lg">
          <CheckCircle className="w-8 h-8" />
        </div>

        <div className="space-y-2">
          <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400">
            Booking Created
          </span>
          <h3 className="text-2xl font-bold text-theme-primary">Your Journey Awaits!</h3>
          <p className="text-sm text-theme-muted">
            Your vehicle has been reserved. Payment status will appear on your dashboard.
          </p>
          <p className="text-sm text-theme-muted">
            Reference Number: <span className="font-mono font-bold text-blue-400">{bookingSuccess.booking_reference}</span>
          </p>
        </div>

        <div className="bg-theme-secondary p-4 rounded-2xl text-left space-y-2 text-xs text-theme-secondary border border-theme">
          <div className="flex justify-between">
            <span className="text-theme-muted">Vehicle:</span>
            <span className="font-semibold text-theme-primary">{vehicle.brand} {vehicle.model}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-theme-muted">Duration:</span>
            <span className="font-semibold text-theme-primary">{rentalDays} Days</span>
          </div>
          <div className="flex justify-between">
            <span className="text-theme-muted">Estimated Total:</span>
            <span className="font-bold text-emerald-400 text-sm">{formatCurrency(bookingSuccess.total_price || estimatedSubtotal)}</span>
          </div>
        </div>

        <div className="pt-2 flex flex-col gap-3">
          <button
            onClick={() => navigate('/dashboard')}
            className="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-theme-primary font-bold text-sm transition-all shadow-lg"
          >
            Go to Dashboard
          </button>
          <button
            onClick={() => setBookingSuccess(null)}
            className="w-full py-2.5 rounded-xl border border-theme text-theme-muted hover:text-theme-primary text-xs"
          >
            Book Another Date
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-2xl sticky top-28">
      <div className="flex items-center justify-between pb-4 border-b border-theme">
        <div>
          <p className="text-xs font-semibold text-theme-muted uppercase tracking-wider">Rental Price</p>
          <p className="text-3xl font-extrabold text-theme-primary">
            {formatCurrency(vehicle.rental_price_per_day)}
            <span className="text-xs font-normal text-theme-muted"> / day</span>
          </p>
        </div>
        <span className="px-3 py-1 text-xs font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
          Instant Reservation
        </span>
      </div>

      <form onSubmit={handleBookingSubmit} className="space-y-4">
        {/* Pickup & Return Dates */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5 flex items-center gap-1.5">
              <Calendar className="w-3.5 h-3.5 text-blue-400" />
              <span>Pickup Date</span>
            </label>
            <input
              type="date"
              min={today}
              value={pickupDate}
              onChange={(e) => setPickupDate(e.target.value)}
              required
              className="w-full bg-theme-secondary border border-theme rounded-xl px-3 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5 flex items-center gap-1.5">
              <Calendar className="w-3.5 h-3.5 text-blue-400" />
              <span>Return Date</span>
            </label>
            <input
              type="date"
              min={pickupDate || today}
              value={returnDate}
              onChange={(e) => setReturnDate(e.target.value)}
              required
              className="w-full bg-theme-secondary border border-theme rounded-xl px-3 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
            />
          </div>
        </div>

        {/* Pickup & Return Locations */}
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5 flex items-center gap-1.5">
            <MapPin className="w-3.5 h-3.5 text-indigo-400" />
            <span>Pickup Location</span>
          </label>
          <input
            type="text"
            value={pickupLocation}
            onChange={(e) => setPickupLocation(e.target.value)}
            required
            placeholder="Airport Terminal 1, City Center Hub..."
            className="w-full bg-theme-secondary border border-theme rounded-xl px-3.5 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
          />
        </div>

        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5 flex items-center gap-1.5">
            <MapPin className="w-3.5 h-3.5 text-indigo-400" />
            <span>Return Location</span>
          </label>
          <input
            type="text"
            value={returnLocation}
            onChange={(e) => setReturnLocation(e.target.value)}
            required
            placeholder="Same as pickup or drop-off point..."
            className="w-full bg-theme-secondary border border-theme rounded-xl px-3.5 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
          />
        </div>

        {/* Notes */}
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5">
            Special Requests / Flight Number (Optional)
          </label>
          <textarea
            rows="2"
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            placeholder="Child seat request, late arrival info..."
            className="w-full bg-theme-secondary border border-theme rounded-xl px-3.5 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
          />
        </div>

        {/* Rental Price Breakdown */}
        <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-2 text-sm">
          <div className="flex justify-between text-theme-muted text-xs">
            <span>
              {formatCurrency(vehicle.rental_price_per_day)} x {rentalDays} {rentalDays === 1 ? 'day' : 'days'}
            </span>
            <span className="font-semibold text-theme-secondary">{formatCurrency(estimatedSubtotal)}</span>
          </div>
          <div className="flex justify-between text-theme-muted text-xs">
            <span>Comprehensive Insurance</span>
            <span className="font-semibold text-emerald-400">Included</span>
          </div>
          <div className="pt-2 border-t border-theme flex justify-between items-center">
            <span className="font-bold text-theme-primary text-base">Estimated Total</span>
            <span className="font-extrabold text-xl text-blue-400">{formatCurrency(estimatedSubtotal)}</span>
          </div>
          <p className="text-[11px] text-theme-muted italic pt-1">
            *Final total price is calculated and verified by the backend upon booking confirmation.
          </p>
        </div>

        {/* Submit Action */}
        <button
          type="submit"
          disabled={loading || vehicle.status !== 'available'}
          className="w-full py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-theme-primary font-bold text-base transition-all shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          {loading ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" />
              <span>Processing Reservation...</span>
            </>
          ) : vehicle.status !== 'available' ? (
            <span>Currently Unavailable</span>
          ) : (
            <span>Confirm & Book Vehicle</span>
          )}
        </button>

        <div className="flex items-center justify-center gap-4 pt-2 text-xs text-theme-muted">
          <span className="flex items-center gap-1">
            <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" /> Free Cancellation
          </span>
          <span className="flex items-center gap-1">
            <CreditCard className="w-3.5 h-3.5 text-blue-400" /> Pay at Counter / Online
          </span>
        </div>
      </form>
    </div>
  );
};

export default RentalCalculator;
