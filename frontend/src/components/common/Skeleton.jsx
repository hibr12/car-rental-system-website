import React from 'react';

export const VehicleCardSkeleton = () => (
  <div className="bg-slate-800/60 border border-slate-700/50 rounded-2xl overflow-hidden animate-pulse">
    <div className="h-48 bg-slate-700/60 w-full" />
    <div className="p-5 space-y-4">
      <div className="flex justify-between items-center">
        <div className="h-4 bg-slate-700/60 rounded w-1/3" />
        <div className="h-6 bg-slate-700/60 rounded-full w-16" />
      </div>
      <div className="h-6 bg-slate-700/60 rounded w-2/3" />
      <div className="grid grid-cols-3 gap-2 py-2">
        <div className="h-8 bg-slate-700/40 rounded-lg" />
        <div className="h-8 bg-slate-700/40 rounded-lg" />
        <div className="h-8 bg-slate-700/40 rounded-lg" />
      </div>
      <div className="flex justify-between items-center pt-2">
        <div className="h-7 bg-slate-700/60 rounded w-1/3" />
        <div className="h-10 bg-slate-700/60 rounded-xl w-28" />
      </div>
    </div>
  </div>
);

export const TableRowSkeleton = ({ cols = 5 }) => (
  <tr className="border-b border-slate-800 animate-pulse">
    {Array.from({ length: cols }).map((_, i) => (
      <td key={i} className="py-4 px-4">
        <div className="h-4 bg-slate-700/50 rounded w-3/4" />
      </td>
    ))}
  </tr>
);

export const StatCardSkeleton = () => (
  <div className="bg-slate-800/50 border border-slate-700/50 p-6 rounded-2xl animate-pulse space-y-3">
    <div className="flex justify-between items-center">
      <div className="h-4 bg-slate-700/50 rounded w-1/2" />
      <div className="w-10 h-10 bg-slate-700/50 rounded-xl" />
    </div>
    <div className="h-8 bg-slate-700/60 rounded w-1/3" />
    <div className="h-3 bg-slate-700/40 rounded w-2/3" />
  </div>
);

export default { VehicleCardSkeleton, TableRowSkeleton, StatCardSkeleton };
