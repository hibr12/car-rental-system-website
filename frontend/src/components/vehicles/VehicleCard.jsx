import React from 'react';
import { Link } from 'react-router-dom';
import { Fuel, Gauge, Users, Star, ArrowRight, ShieldAlert } from 'lucide-react';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';

export const VehicleCard = ({ vehicle }) => {
  const primaryImage =
    vehicle.primary_image?.image_url ||
    vehicle.images?.[0]?.image_url ||
    'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80';

  const isAvailable = vehicle.status === 'available';

  return (
    <div className="group bg-slate-900 border border-slate-800 hover:border-slate-700/80 rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-2xl hover:shadow-blue-500/10 flex flex-col justify-between">
      <div>
        {/* Card Image Container */}
        <div className="relative aspect-[16/10] overflow-hidden bg-slate-950">
          <img
            src={primaryImage}
            alt={`${vehicle.brand} ${vehicle.model}`}
            className="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
            loading="lazy"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent opacity-80" />

          {/* Badges */}
          <div className="absolute top-3 left-3 flex flex-wrap gap-2">
            {vehicle.featured && (
              <span className="px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 shadow-md">
                Featured
              </span>
            )}
            {vehicle.category && (
              <span className="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-900/90 text-slate-200 border border-slate-700/50 backdrop-blur-md">
                {vehicle.category.name}
              </span>
            )}
          </div>

          <div className="absolute top-3 right-3">
            <span
              className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border backdrop-blur-md ${getStatusBadgeStyle(
                vehicle.status
              )}`}
            >
              {formatStatus(vehicle.status)}
            </span>
          </div>

          {/* Title Overlay */}
          <div className="absolute bottom-3 left-3 right-3">
            <p className="text-xs font-semibold text-blue-400 uppercase tracking-wider">
              {vehicle.year} {vehicle.brand}
            </p>
            <h3 className="text-lg font-bold text-white tracking-tight truncate">
              {vehicle.model}
            </h3>
          </div>
        </div>

        {/* Specs Grid */}
        <div className="p-5 space-y-4">
          <div className="grid grid-cols-3 gap-2 py-1 border-b border-slate-800/80 text-xs text-slate-300">
            <div className="flex items-center gap-1.5 bg-slate-950/60 p-2 rounded-xl border border-slate-800/50">
              <Fuel className="w-3.5 h-3.5 text-blue-400 shrink-0" />
              <span className="capitalize truncate">{vehicle.fuel_type}</span>
            </div>
            <div className="flex items-center gap-1.5 bg-slate-950/60 p-2 rounded-xl border border-slate-800/50">
              <Gauge className="w-3.5 h-3.5 text-indigo-400 shrink-0" />
              <span className="capitalize truncate">{vehicle.transmission}</span>
            </div>
            <div className="flex items-center gap-1.5 bg-slate-950/60 p-2 rounded-xl border border-slate-800/50">
              <Users className="w-3.5 h-3.5 text-purple-400 shrink-0" />
              <span>{vehicle.seats} Seats</span>
            </div>
          </div>
        </div>
      </div>

      {/* Card Footer Price & Button */}
      <div className="px-5 pb-5 pt-2 flex items-center justify-between border-t border-slate-800/50 bg-slate-900/50">
        <div>
          <p className="text-[11px] text-slate-400 font-medium">Daily Rate</p>
          <p className="text-xl font-extrabold text-white">
            {formatCurrency(vehicle.rental_price_per_day)}
            <span className="text-xs font-normal text-slate-400">/day</span>
          </p>
        </div>

        <Link
          to={`/vehicles/${vehicle.id}`}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition-all shadow-md shadow-blue-600/20 group-hover:gap-2.5"
        >
          <span>View Details</span>
          <ArrowRight className="w-3.5 h-3.5" />
        </Link>
      </div>
    </div>
  );
};

export default VehicleCard;
