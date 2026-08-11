import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  Car,
  Fuel,
  Gauge,
  Users,
  Calendar,
  Palette,
  ShieldCheck,
  Star,
  CheckCircle2,
  ChevronLeft,
  AlertCircle,
  MessageSquare
} from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import reviewApi from '../../api/reviewApi';
import VehicleGallery from '../../components/vehicles/VehicleGallery';
import RentalCalculator from '../../components/vehicles/RentalCalculator';
import StarRating from '../../components/common/StarRating';
import { formatCurrency, formatStatus, getStatusBadgeStyle, formatDate } from '../../utils/formatters';

export const VehicleDetailPage = () => {
  const { id } = useParams();
  const [vehicle, setVehicle] = useState(null);
  const [reviews, setReviews] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchVehicleAndReviews = async () => {
      try {
        setLoading(true);
        setError(null);

        const vehRes = await vehicleApi.getById(id);
        const vehData = vehRes.data?.vehicle || vehRes.data;
        setVehicle(vehData);

        try {
          const revRes = await reviewApi.getByVehicle(id);
          setReviews(revRes.data || []);
        } catch (revErr) {
          console.warn('Could not load reviews for vehicle:', revErr);
        }
      } catch (err) {
        setError(err.message || 'Failed to retrieve vehicle details.');
      } finally {
        setLoading(false);
      }
    };

    if (id) {
      fetchVehicleAndReviews();
    }
  }, [id]);

  if (loading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-8 animate-pulse">
        <div className="h-6 bg-theme-hover rounded w-1/4" />
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <div className="h-96 bg-theme-hover rounded-3xl" />
            <div className="h-32 bg-theme-hover rounded-2xl" />
          </div>
          <div className="h-[500px] bg-theme-hover rounded-3xl" />
        </div>
      </div>
    );
  }

  if (error || !vehicle) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-20 text-center space-y-4">
        <AlertCircle className="w-16 h-16 text-rose-400 mx-auto" />
        <h2 className="text-2xl font-bold text-theme-primary">Vehicle Not Found</h2>
        <p className="text-sm text-theme-muted">{error || 'The requested vehicle does not exist or has been removed.'}</p>
        <Link
          to="/vehicles"
          className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-blue-600 text-theme-primary font-semibold text-xs"
        >
          <ChevronLeft className="w-4 h-4" />
          <span>Back to Catalog</span>
        </Link>
      </div>
    );
  }

  const averageRating =
    reviews.length > 0
      ? (reviews.reduce((acc, curr) => acc + (curr.rating || 5), 0) / reviews.length).toFixed(1)
      : '5.0';

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
      {/* Breadcrumb navigation */}
      <Link
        to="/vehicles"
        className="inline-flex items-center gap-2 text-xs font-semibold text-theme-muted hover:text-blue-400 transition-colors"
      >
        <ChevronLeft className="w-4 h-4" />
        <span>Back to All Vehicles</span>
      </Link>

      {/* Title Header */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-theme pb-6">
        <div className="space-y-1">
          <div className="flex items-center gap-3">
            <span className="text-xs font-extrabold uppercase tracking-wider text-blue-400">
              {vehicle.year} {vehicle.brand}
            </span>
            <span
              className={`px-2.5 py-0.5 text-[11px] font-bold rounded-md border ${getStatusBadgeStyle(
                vehicle.status
              )}`}
            >
              {formatStatus(vehicle.status)}
            </span>
          </div>
          <h1 className="text-3xl sm:text-5xl font-extrabold text-theme-primary tracking-tight">
            {vehicle.model}
          </h1>
          {vehicle.category && (
            <p className="text-sm text-theme-muted">
              Category: <span className="font-semibold text-theme-secondary">{vehicle.category.name}</span>
            </p>
          )}
        </div>

        <div className="flex items-center gap-3 bg-theme-card border border-theme px-4 py-2.5 rounded-2xl">
          <StarRating rating={Math.round(parseFloat(averageRating))} size="md" />
          <span className="text-sm font-bold text-theme-primary">{averageRating}</span>
          <span className="text-xs text-theme-muted">({reviews.length} reviews)</span>
        </div>
      </div>

      {/* Main Grid: Gallery & Specs vs Rental Calculator */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
        {/* Left Column: Gallery & Description & Specs */}
        <div className="lg:col-span-2 space-y-10">
          {/* Photo Gallery */}
          <VehicleGallery images={vehicle.images} vehicleName={`${vehicle.brand} ${vehicle.model}`} />

          {/* Description */}
          <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-4">
            <h3 className="text-lg font-bold text-theme-primary">Vehicle Description</h3>
            <p className="text-sm text-theme-secondary leading-relaxed">
              {vehicle.description ||
                `The ${vehicle.year} ${vehicle.brand} ${vehicle.model} offers top-tier engineering, remarkable comfort, and an exhilarating driving experience. Perfect for both city cruising and long-distance travel.`}
            </p>
          </div>

          {/* Detailed Specifications */}
          <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6">
            <h3 className="text-lg font-bold text-theme-primary">Specifications & Features</h3>
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-1">
                <Fuel className="w-5 h-5 text-blue-400 mb-2" />
                <span className="text-[11px] text-theme-muted uppercase font-semibold">Fuel Type</span>
                <p className="text-sm font-bold text-theme-primary capitalize">{vehicle.fuel_type}</p>
              </div>

              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-1">
                <Gauge className="w-5 h-5 text-indigo-400 mb-2" />
                <span className="text-[11px] text-theme-muted uppercase font-semibold">Transmission</span>
                <p className="text-sm font-bold text-theme-primary capitalize">{vehicle.transmission}</p>
              </div>

              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-1">
                <Users className="w-5 h-5 text-purple-400 mb-2" />
                <span className="text-[11px] text-theme-muted uppercase font-semibold">Seating Capacity</span>
                <p className="text-sm font-bold text-theme-primary">{vehicle.seats} Passengers</p>
              </div>

              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-1">
                <Calendar className="w-5 h-5 text-amber-400 mb-2" />
                <span className="text-[11px] text-theme-muted uppercase font-semibold">Model Year</span>
                <p className="text-sm font-bold text-theme-primary">{vehicle.year}</p>
              </div>

              {vehicle.color && (
                <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-1">
                  <Palette className="w-5 h-5 text-emerald-400 mb-2" />
                  <span className="text-[11px] text-theme-muted uppercase font-semibold">Exterior Color</span>
                  <p className="text-sm font-bold text-theme-primary capitalize">{vehicle.color}</p>
                </div>
              )}

              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-1">
                <ShieldCheck className="w-5 h-5 text-cyan-400 mb-2" />
                <span className="text-[11px] text-theme-muted uppercase font-semibold">Registration</span>
                <p className="text-sm font-bold text-theme-primary truncate">{vehicle.registration_number || 'Verified'}</p>
              </div>
            </div>
          </div>

          {/* Customer Reviews Section */}
          <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6">
            <div className="flex items-center justify-between border-b border-theme pb-4">
              <div className="flex items-center gap-2">
                <MessageSquare className="w-5 h-5 text-blue-400" />
                <h3 className="text-lg font-bold text-theme-primary">Customer Reviews</h3>
              </div>
              <span className="text-xs text-theme-muted">{reviews.length} total reviews</span>
            </div>

            {reviews.length === 0 ? (
              <p className="text-xs text-theme-muted italic text-center py-6">
                No customer reviews yet for this vehicle.
              </p>
            ) : (
              <div className="space-y-4">
                {reviews.map((rev) => (
                  <div key={rev.id} className="bg-theme-secondary p-5 rounded-2xl border border-theme space-y-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <div className="w-7 h-7 rounded-full bg-blue-600/30 text-blue-400 font-bold text-xs flex items-center justify-center">
                          {rev.user?.name?.[0]?.toUpperCase() || 'U'}
                        </div>
                        <span className="text-xs font-semibold text-theme-primary">{rev.user?.name || 'Verified Renter'}</span>
                      </div>
                      <StarRating rating={rev.rating} size="sm" />
                    </div>
                    {rev.comment && <p className="text-xs text-theme-secondary leading-relaxed pt-1">"{rev.comment}"</p>}
                    <p className="text-[10px] text-theme-muted">{formatDate(rev.created_at)}</p>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Live Booking Calculator */}
        <div className="lg:col-span-1">
          <RentalCalculator vehicle={vehicle} />
        </div>
      </div>
    </div>
  );
};

export default VehicleDetailPage;