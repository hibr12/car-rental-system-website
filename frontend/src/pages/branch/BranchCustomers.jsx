import React, { useState, useEffect } from 'react';
import { Users, Loader2 } from 'lucide-react';
import branchApi from '../../api/branchApi';
import { formatDate } from '../../utils/formatters';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
} from '../../components/management/ManagementUI';

export default function BranchCustomers() {
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    branchApi.getCustomers({ per_page: 50 })
      .then((r) => setCustomers(r.data || []))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Branch Operations"
        title="Branch Customers"
        description="Customers with rental history at your branch"
      />

      <ManagementCard className="overflow-x-auto">
        <table className="w-full text-sm text-left">
          <thead className="mgmt-table-head">
            <tr>
              <th className="py-3 px-4">Customer</th>
              <th className="py-3 px-4">Email</th>
              <th className="py-3 px-4">Branch Bookings</th>
              <th className="py-3 px-4">Member Since</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[#E2E8F0]">
            {!loading && customers.map((c) => (
              <tr key={c.id} className="mgmt-table-row">
                <td className="py-3 px-4 font-medium">{c.name}</td>
                <td className="py-3 px-4 text-xs text-[#64748B]">{c.email}</td>
                <td className="py-3 px-4">{c.branch_bookings_count ?? 0}</td>
                <td className="py-3 px-4 text-xs text-[#64748B]">{formatDate(c.created_at)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        {!loading && customers.length === 0 && (
          <ManagementEmptyState icon={Users} title="No branch customers yet" description="Customers appear here after booking at your branch." />
        )}
        {loading && (
          <div className="py-16 flex justify-center"><Loader2 className="w-8 h-8 animate-spin text-[#2563EB]" /></div>
        )}
      </ManagementCard>
    </div>
  );
}
