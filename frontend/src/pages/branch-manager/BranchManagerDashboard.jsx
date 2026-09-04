import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import {
  CalendarCheck,
  Car,
  DollarSign,
  Users,
  Clock,
  CheckCircle,
  AlertTriangle,
  TrendingUp,
} from "lucide-react";
import branchApi from "../../api/branchApi";
import { formatCurrency, formatDate, formatStatus } from "../../utils/formatters";
import { StatCardSkeleton } from "../../components/common/Skeleton";

const BranchManagerDashboard = () => {
  const [stats, setStats] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchDashboard();
  }, []);

  const fetchDashboard = async () => {
    try {
      setIsLoading(true);
      const response = await branchApi.getDashboard();
      setStats(response.data?.data || response.data);
    } catch (error) {
      console.error("Failed to fetch branch dashboard:", error);
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {[...Array(4)].map((_, i) => (
            <StatCardSkeleton key={i} />
          ))}
        </div>
      </div>
    );
  }

  const statCards = [
    {
      label: "Today's Bookings",
      value: stats?.today_bookings || 0,
      icon: CalendarCheck,
      color: "blue",
    },
    {
      label: "Active Rentals",
      value: stats?.active_rentals || 0,
      icon: Car,
      color: "emerald",
    },
    {
      label: "Pending Approvals",
      value: stats?.pending_approvals || 0,
      icon: Clock,
      color: "amber",
    },
    {
      label: "Available Vehicles",
      value: stats?.available_vehicles || 0,
      icon: CheckCircle,
      color: "cyan",
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-theme-primary">
          Branch Manager Dashboard
        </h1>
        <p className="text-theme-secondary text-sm mt-1">
          Overview of your branch operations
        </p>
      </div>

      {/* Stat Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((card, index) => {
          const Icon = card.icon;
          const colorMap = {
            blue: "from-blue-500/20 to-blue-600/10 border-blue-500/30",
            emerald: "from-emerald-500/20 to-emerald-600/10 border-emerald-500/30",
            amber: "from-amber-500/20 to-amber-600/10 border-amber-500/30",
            cyan: "from-cyan-500/20 to-cyan-600/10 border-cyan-500/30",
          };
          const iconColorMap = {
            blue: "text-blue-400",
            emerald: "text-emerald-400",
            amber: "text-amber-400",
            cyan: "text-cyan-400",
          };

          return (
            <div
              key={index}
              className={`bg-gradient-to-br ${colorMap[card.color]} border rounded-xl p-4`}
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-theme-secondary text-xs font-medium">
                    {card.label}
                  </p>
                  <p className="text-2xl font-bold text-theme-primary mt-1">
                    {card.value}
                  </p>
                </div>
                <Icon className={`w-8 h-8 ${iconColorMap[card.color]} opacity-80`} />
              </div>
            </div>
          );
        })}
      </div>

      {/* Revenue Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div className="bg-theme-card border border-theme rounded-xl p-6">
          <div className="flex items-center gap-3">
            <div className="p-3 rounded-xl bg-emerald-500/20">
              <DollarSign className="w-6 h-6 text-emerald-400" />
            </div>
            <div>
              <p className="text-theme-secondary text-sm">Today's Revenue</p>
              <p className="text-2xl font-bold text-theme-primary">
                {formatCurrency(stats?.today_revenue || 0)}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-theme-card border border-theme rounded-xl p-6">
          <div className="flex items-center gap-3">
            <div className="p-3 rounded-xl bg-blue-500/20">
              <TrendingUp className="w-6 h-6 text-blue-400" />
            </div>
            <div>
              <p className="text-theme-secondary text-sm">Monthly Revenue</p>
              <p className="text-2xl font-bold text-theme-primary">
                {formatCurrency(stats?.monthly_revenue || 0)}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Fleet Status */}
      <div className="bg-theme-card border border-theme rounded-xl p-6">
        <h2 className="text-lg font-semibold text-theme-primary mb-4">
          Fleet Status
        </h2>
        <div className="grid grid-cols-3 gap-4">
          <div className="text-center p-4 bg-theme-secondary rounded-lg">
            <p className="text-2xl font-bold text-emerald-400">
              {stats?.available_vehicles || 0}
            </p>
            <p className="text-theme-secondary text-xs mt-1">Available</p>
          </div>
          <div className="text-center p-4 bg-theme-secondary rounded-lg">
            <p className="text-2xl font-bold text-amber-400">
              {stats?.active_rentals || 0}
            </p>
            <p className="text-theme-secondary text-xs mt-1">Rented</p>
          </div>
          <div className="text-center p-4 bg-theme-secondary rounded-lg">
            <p className="text-2xl font-bold text-rose-400">
              {stats?.maintenance_vehicles || 0}
            </p>
            <p className="text-theme-secondary text-xs mt-1">Maintenance</p>
          </div>
        </div>
      </div>

      {/* Recent Bookings */}
      <div className="bg-theme-card border border-theme rounded-xl p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-theme-primary">
            Recent Reservations
          </h2>
          <Link
            to="/branch-manager/bookings"
            className="text-sm text-blue-500 hover:text-blue-600"
          >
            View All
          </Link>
        </div>
        {stats?.recent_bookings?.length === 0 ? (
          <p className="text-theme-muted text-center py-4">
            No recent reservations.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-theme-muted border-b border-theme">
                  <th className="pb-3 font-medium">Reference</th>
                  <th className="pb-3 font-medium">Customer</th>
                  <th className="pb-3 font-medium">Vehicle</th>
                  <th className="pb-3 font-medium">Status</th>
                  <th className="pb-3 font-medium">Date</th>
                </tr>
              </thead>
              <tbody>
                {stats?.recent_bookings?.map((booking) => (
                  <tr
                    key={booking.id}
                    className="border-b border-theme last:border-0"
                  >
                    <td className="py-3 font-mono text-xs">
                      {booking.booking_reference}
                    </td>
                    <td className="py-3">{booking.user?.name}</td>
                    <td className="py-3">
                      {booking.vehicle?.brand} {booking.vehicle?.model}
                    </td>
                    <td className="py-3">
                      <span
                        className={`px-2 py-1 rounded-full text-xs font-medium ${
                          booking.status === "completed"
                            ? "bg-emerald-500/20 text-emerald-400"
                            : booking.status === "active"
                            ? "bg-blue-500/20 text-blue-400"
                            : booking.status === "pending"
                            ? "bg-amber-500/20 text-amber-400"
                            : "bg-theme-secondary text-theme-secondary"
                        }`}
                      >
                        {formatStatus(booking.status)}
                      </span>
                    </td>
                    <td className="py-3 text-theme-muted">
                      {formatDate(booking.created_at)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Staff */}
      {stats?.staff?.length > 0 && (
        <div className="bg-theme-card border border-theme rounded-xl p-6">
          <h2 className="text-lg font-semibold text-theme-primary mb-4">
            Branch Staff
          </h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {stats.staff.map((member) => (
              <div
                key={member.id}
                className="flex items-center gap-3 p-3 bg-theme-secondary rounded-lg"
              >
                <div className="w-10 h-10 rounded-full bg-blue-600/30 text-blue-400 flex items-center justify-center font-bold text-sm">
                  {member.name?.[0]?.toUpperCase()}
                </div>
                <div>
                  <p className="font-medium text-theme-primary text-sm">
                    {member.name}
                  </p>
                  <p className="text-theme-muted text-xs">
                    {formatStatus(member.role)}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default BranchManagerDashboard;
