import React, { useState, useEffect } from 'react';
import { AlertTriangle, Loader2, Search } from 'lucide-react';
import damageApi from '../../api/damageApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

export default function FleetDamage() {
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState('');
  const [search, setSearch] = useState('');
  const toast = useToast();

  const load = () => {
    setLoading(true);
    damageApi.getAll({ per_page: 100, repair_status: filterStatus || undefined })
      .then((res) => setRecords(res.data || []))
      .catch(() => toast.error('Failed to load damage records.'))
      .finally(() => setLoading(false));
  };

  useEffect(load, [filterStatus]);

  const filtered = records.filter((r) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return (
      r.description?.toLowerCase().includes(q) ||
      r.vehicle?.registration_number?.toLowerCase().includes(q)
    );
  });

  const updateRepairStatus = async (id, repair_status) => {
    try {
      await damageApi.update(id, { repair_status });
      toast.success('Damage record updated.');
      load();
    } catch (err) {
      toast.error(err.message || 'Update failed.');
    }
  };

  return (
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Fleet Operations"
        title="Damage Records"
        description="Track reported vehicle damage, severity, and repair progress"
        actions={
          <ManagementButton variant="secondary" onClick={load}>
            <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            Refresh
          </ManagementButton>
        }
      />

      <ManagementCard className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input className="mgmt-input pl-10" placeholder="Search damage records..." value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
        <select className="mgmt-input" value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)}>
          <option value="">All repair statuses</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="waived">Waived</option>
        </select>
      </ManagementCard>

      <ManagementCard className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="mgmt-table-head">
            <tr>
              <th className="py-3.5 px-4">Vehicle</th>
              <th className="py-3.5 px-4">Type</th>
              <th className="py-3.5 px-4">Severity</th>
              <th className="py-3.5 px-4">Description</th>
              <th className="py-3.5 px-4">Est. Cost</th>
              <th className="py-3.5 px-4">Reported</th>
              <th className="py-3.5 px-4">Repair</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[#E2E8F0]">
            {loading && [...Array(5)].map((_, i) => <TableRowSkeleton key={i} cols={7} />)}
            {!loading && filtered.map((r) => (
              <tr key={r.id} className="mgmt-table-row">
                <td className="py-4 px-4 font-medium">{r.vehicle?.brand} {r.vehicle?.model}<div className="text-xs font-mono text-[#64748B]">{r.vehicle?.registration_number}</div></td>
                <td className="py-4 px-4 capitalize">{formatStatus(r.damage_type)}</td>
                <td className="py-4 px-4"><span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(r.severity)}`}>{formatStatus(r.severity)}</span></td>
                <td className="py-4 px-4 text-xs max-w-xs truncate">{r.description}</td>
                <td className="py-4 px-4 font-bold text-[#16A34A]">{formatCurrency(r.estimated_repair_cost || 0)}</td>
                <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(r.reported_at)}</td>
                <td className="py-4 px-4">
                  <select className="text-xs border rounded-lg px-2 py-1" value={r.repair_status} onChange={(e) => updateRepairStatus(r.id, e.target.value)}>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="waived">Waived</option>
                  </select>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {!loading && filtered.length === 0 && (
          <ManagementEmptyState icon={AlertTriangle} title="No damage records" description="Damage reported on returns or inspections will appear here." />
        )}
      </ManagementCard>
    </div>
  );
}
