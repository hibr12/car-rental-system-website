import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Archive, CalendarCheck, CreditCard } from 'lucide-react';

const ArchiveIndexPage = () => (
  <div className="space-y-6 bg-white">
    <div className="border-b border-[#E2E8F0] pb-6">
      <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">Archive</h1>
      <p className="text-sm text-[#64748B] mt-1">
        Archived records remain in the database for compliance and audit. Nothing is permanently deleted.
      </p>
    </div>

    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <Link
        to="/admin/archive/bookings"
        className="bg-white border border-[#E2E8F0] rounded-xl p-6 hover:border-[#2563EB] transition-colors group"
      >
        <CalendarCheck className="w-8 h-8 text-[#2563EB] mb-3" />
        <h2 className="font-bold text-[#0F172A] group-hover:text-[#2563EB]">Archived Bookings</h2>
        <p className="text-xs text-[#64748B] mt-1">Search closed bookings removed from active operations lists.</p>
      </Link>
      <Link
        to="/admin/archive/payments"
        className="bg-white border border-[#E2E8F0] rounded-xl p-6 hover:border-[#2563EB] transition-colors group"
      >
        <CreditCard className="w-8 h-8 text-[#2563EB] mb-3" />
        <h2 className="font-bold text-[#0F172A] group-hover:text-[#2563EB]">Archived Payments</h2>
        <p className="text-xs text-[#64748B] mt-1">Non-financial payment clutter archived from active views. Paid records stay in history.</p>
      </Link>
    </div>

    <div className="bg-amber-50 border border-[#F59E0B]/30 rounded-xl p-4 flex gap-3 text-sm text-[#334155]">
      <Archive className="w-5 h-5 text-[#F59E0B] shrink-0 mt-0.5" />
      <p>
        <strong>Retention policy:</strong> Staff and branch managers cannot delete bookings or payments.
        Main admin may archive old operational records only. Successful payments and audit logs are never deleted.
      </p>
    </div>
  </div>
);

export default ArchiveIndexPage;
