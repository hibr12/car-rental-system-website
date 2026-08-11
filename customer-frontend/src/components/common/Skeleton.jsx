import React from 'react';

export const VehicleCardSkeleton = () => (
  <div className="bg-theme-card border border-theme rounded-2xl overflow-hidden animate-pulse transition-colors duration-200">
    <div className="h-48 bg-theme-hover w-full" />
    <div className="p-5 space-y-4">
      <div className="flex justify-between items-center">
        <div className="h-4 bg-theme-hover rounded w-1/3" />
        <div className="h-6 bg-theme-hover rounded-full w-16" />
      </div>
      <div className="h-6 bg-theme-hover rounded w-2/3" />
      <div className="grid grid-cols-3 gap-2 py-2">
        <div className="h-8 bg-theme-hover rounded-lg" />
        <div className="h-8 bg-theme-hover rounded-lg" />
        <div className="h-8 bg-theme-hover rounded-lg" />
      </div>
      <div className="flex justify-between items-center pt-2">
        <div className="h-7 bg-theme-hover rounded w-1/3" />
        <div className="h-10 bg-theme-hover rounded-xl w-28" />
      </div>
    </div>
  </div>
);

export const TableRowSkeleton = ({ cols = 5 }) => (
  <tr className="border-b border-theme animate-pulse">
    {Array.from({ length: cols }).map((_, i) => (
      <td key={i} className="py-4 px-4">
        <div className="h-4 bg-theme-hover rounded w-3/4" />
      </td>
    ))}
  </tr>
);

export const StatCardSkeleton = () => (
  <div className="bg-theme-card border border-theme p-6 rounded-2xl animate-pulse space-y-3 transition-colors duration-200">
    <div className="flex justify-between items-center">
      <div className="h-4 bg-theme-hover rounded w-1/2" />
      <div className="w-10 h-10 bg-theme-hover rounded-xl" />
    </div>
    <div className="h-8 bg-theme-hover rounded w-1/3" />
    <div className="h-3 bg-theme-hover rounded w-2/3" />
  </div>
);

export default { VehicleCardSkeleton, TableRowSkeleton, StatCardSkeleton };
