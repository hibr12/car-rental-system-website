import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  CheckCircle2,
  ArrowRight,
  Home,
  Calendar,
  MapPin,
  Car,
  CreditCard,
  Loader2,
  AlertCircle,
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, getStatusBadgeStyle, formatStatus } from '../../utils/formatters';

export const BookingConfirmationPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchBooking = async () => {
      try {
        setLoading(true);
        const res = await bookingApi.getById(id);
        const data = res.data?.booking || res.data;
        setBooking(data);
      } catch (err) {
        setError(err.message || 'Failed to load booking details.');
      } finally {
        setLoading(false);
      }
    };

    if (id) fetchBooking();
  }, [id]);

  if (loading) {
    return (
      <div className="max-w-2xl mx-auto px-4 sm:px-6 py-16 space-y-6 animate-pulse">
        <div className="h-8 bg-theme-hover rounded w-1/3 mx-auto" />
        <div className="h-4 bg-theme-hover rounded w-2/3 mx-auto" />
        <div className="h-64 bg-theme-hover rounded-3xl" />
        <div className="h-48 bg-theme-hover rounded-3xl" />
      </div>
    );
  }

  if (error || !booking) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-20 text-center space-y-4">
        <AlertCircle className="w-16 h-16 text-rose-400 mx-auto" />
        <h2 className="text-2xl font-bold text-theme-primary">
          {error || 'Booking Not Found'}
        </h2>
        <p className="text-sm text-theme-muted">
          Unable to load booking details. Please try again.
        </p>
        <button
          onClick={() => navigate('/dashboard/bookings')}
          className="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold text-sm"
        >
          View My Bookings
        </button>
      </div>
    );
  }

  const vehicle = booking.vehicle;
  const vehicleName = vehicle ? `${vehicle.brand} ${vehicle.model}` : `Vehicle #${booking.vehicle_id}`;

  return (
    <div className="max-w-2xl mx-auto px-4 sm:px-6 py-16 space-y-8">
      {/* Success Header */}
      <div className="text-center space-y-4">
        <div className="w-20 h-20 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/10">
          <CheckCircle2 className="w-10 h-10 text-emerald-500" />
        </div>
        <div className="space-y-1">
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">
            Booking Confirmed
          </h1>
          <p className="text-sm text-theme-muted">
            Your booking has been successfully confirmed. Here are the details.
          </p>
        </div>
      </div>

      {/* Booking Reference */}
      <div className="bg-theme-card border border-theme rounded-2xl p-6 text-center">
        <p className="text-xs uppercase font-bold tracking-wider text-theme-muted mb-1">
          Booking Reference
        </p>
        <p className="text-lg font-mono font-bold text-blue-600">
          {booking.booking_reference}
        </p>
      </div>

      {/* Vehicle Details */}
      <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-4">
        <div className="flex items-center gap-2 pb-3 border-b border-theme">
          <Car className="w-4 h-4 text-blue-600" />
          <h2 className="text-sm font-bold text-theme-primary">Vehicle Details</h2>
        </div>

        {vehicle?.image_url && (
          <div className="rounded-xl overflow-hidden">
            <img
              src={vehicle.image_url}
              alt={vehicleName}
              className="w-full h-48 object-cover"
            />
          </div>
        )}

        <div className="space-y-3 text-sm">
          <div className="flex justify-between">
            <span className="text-theme-muted">Vehicle</span>
            <span className="font-semibold text-theme-primary">{vehicleName}</span>
          </div>
          {vehicle?.year && (
            <div className="flex justify-between">
              <span className="text-theme-muted">Year</span>
              <span className="font-semibold text-theme-primary">{vehicle.year}</span>
            </div>
          )}
          {vehicle?.license_plate && (
            <div className="flex justify-between">
              <span className="text-theme-muted">License Plate</span>
              <span className="font-mono font-semibold text-theme-primary">{vehicle.license_plate}</span>
            </div>
          )}
        </div>
      </div>

      {/* Rental Period */}
      <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-4">
        <div className="flex items-center gap-2 pb-3 border-b border-theme">
          <Calendar className="w-4 h-4 text-blue-600" />
          <h2 className="text-sm font-bold text-theme-primary">Rental Period</h2>
        </div>

        <div className="space-y-3 text-sm">
          <div className="flex justify-between">
            <span className="text-theme-muted">Pickup Date</span>
            <span className="font-semibold text-theme-primary">
              {formatDate(booking.pickup_date)}
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-theme-muted">Return Date</span>
            <span className="font-semibold text-theme-primary">
              {formatDate(booking.return_date)}
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-theme-muted">Duration</span>
            <span className="font-semibold text-theme-primary">
              {booking.number_of_days} {booking.number_of_days === 1 ? 'day' : 'days'}
            </span>
          </div>
        </div>
      </div>

      {/* Locations */}
      <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-4">
        <div className="flex items-center gap-2 pb-3 border-b border-theme">
          <MapPin className="w-4 h-4 text-blue-600" />
          <h2 className="text-sm font-bold text-theme-primary">Locations</h2>
        </div>

        <div className="space-y-3 text-sm">
          <div className="flex justify-between">
            <span className="text-theme-muted">Pickup Location</span>
            <span className="font-semibold text-theme-primary text-right">
              {booking.pickup_location || 'Main Office'}
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-theme-muted">Return Location</span>
            <span className="font-semibold text-theme-primary text-right">
              {booking.return_location || 'Main Office'}
            </span>
          </div>
        </div>
      </div>

      {/* Payment & Status */}
      <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-4">
        <div className="flex items-center gap-2 pb-3 border-b border-theme">
          <CreditCard className="w-4 h-4 text-blue-600" />
          <h2 className="text-sm font-bold text-theme-primary">Payment & Status</h2>
        </div>

        <div className="space-y-3 text-sm">
          <div className="flex justify-between items-center">
            <span className="text-theme-muted">Total Amount</span>
            <span className="font-extrabold text-lg text-blue-600">
              {formatCurrency(booking.total_price)}
            </span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-theme-muted">Payment Status</span>
            <span
              className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(
                booking.payment_status
              )}`}
            >
              {formatStatus(booking.payment_status)}
            </span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-theme-muted">Booking Status</span>
            <span
              className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(
                booking.status
              )}`}
            >
              {formatStatus(booking.status)}
            </span>
          </div>
        </div>
      </div>

      {/* Action Buttons */}
      <div className="flex flex-col sm:flex-row gap-3 justify-center pt-4">
        <button
          onClick={() => navigate('/dashboard/bookings')}
          className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm transition-colors flex items-center justify-center gap-2"
        >
          View My Bookings
          <ArrowRight className="w-4 h-4" />
        </button>
        <button
          onClick={() => navigate('/')}
          className="px-6 py-3 rounded-xl border border-theme text-theme-secondary hover:text-theme-primary font-semibold text-sm transition-colors flex items-center justify-center gap-2"
        >
          <Home className="w-4 h-4" />
          Back to Home
        </button>
      </div>
    </div>
  );
};

export default BookingConfirmationPage;
