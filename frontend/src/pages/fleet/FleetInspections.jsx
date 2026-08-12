import React, { useState, useEffect } from 'react';
import { ClipboardCheck, Loader2, Search, CheckCircle2 } from 'lucide-react';
import inspectionApi from '../../api/inspectionApi';
import { formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { Modal } from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const INPUT_CLS = 'mgmt-input';
const LABEL_CLS = 'block text-xs font-semibold text-[#334155] mb-1';

export default function FleetInspections() {
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState('pending');
  const [search, setSearch] = useState('');
  const [completeModal, setCompleteModal] = useState(null);
  const [form, setForm] = useState({ result: 'passed', mileage: '', notes: '' });
  const [submitting, setSubmitting] = useState(false);
  const toast = useToast();

  const load = () => {
    setLoading(true);
    inspectionApi.getAll({ per_page: 100, status: filterStatus || undefined })
      .then((res) => setRecords(res.data || []))
      .catch(() => toast.error('Failed to load inspections.'))
      .finally(() => setLoading(false));
  };

  useEffect(load, [filterStatus]);

  const filtered = records.filter((r) => {
    if (!search) return true;
    const q = search.toLowerCase();
    const v = r.vehicle;
    return (
      v?.registration_number?.toLowerCase().includes(q) ||
      `${v?.brand} ${v?.model}`.toLowerCase().includes(q)
    );
  });

  const handleComplete = async () => {
    if (!completeModal) return;
    setSubmitting(true);
    try {
      await inspectionApi.complete(completeModal.id, {
        result: form.result,
        mileage: form.mileage ? Number(form.mileage) : undefined,
        notes: form.notes || undefined,
      });
      toast.success('Inspection completed.');
      setCompleteModal(null);
      setForm({ result: 'passed', mileage: '', notes: '' });
      load();
    } catch (err) {
      toast.error(err.message || 'Failed to complete inspection.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Fleet Operations"
        title="Vehicle Inspections"
        description="Post-return, periodic, and maintenance inspections across the fleet"
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
          <input className={`${INPUT_CLS} pl-10`} placeholder="Search vehicle..." value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
        <select className={INPUT_CLS} value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)}>
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </ManagementCard>

      <ManagementCard className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="mgmt-table-head">
            <tr>
              <th className="py-3.5 px-4">Vehicle</th>
              <th className="py-3.5 px-4">Type</th>
              <th className="py-3.5 px-4">Branch</th>
              <th className="py-3.5 px-4">Status</th>
              <th className="py-3.5 px-4">Result</th>
              <th className="py-3.5 px-4">Created</th>
              <th className="py-3.5 px-4">Action</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[#E2E8F0]">
            {loading && [...Array(5)].map((_, i) => <TableRowSkeleton key={i} cols={7} />)}
            {!loading && filtered.map((r) => (
              <tr key={r.id} className="mgmt-table-row">
                <td className="py-4 px-4 font-medium">{r.vehicle?.brand} {r.vehicle?.model}<div className="text-xs text-[#64748B] font-mono">{r.vehicle?.registration_number}</div></td>
                <td className="py-4 px-4 capitalize">{formatStatus(r.inspection_type)}</td>
                <td className="py-4 px-4 text-xs">{r.vehicle?.branch?.name || '—'}</td>
                <td className="py-4 px-4"><span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(r.status)}`}>{formatStatus(r.status)}</span></td>
                <td className="py-4 px-4"><span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(r.result)}`}>{formatStatus(r.result)}</span></td>
                <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(r.created_at)}</td>
                <td className="py-4 px-4">
                  {r.status !== 'completed' && (
                    <button type="button" onClick={() => setCompleteModal(r)} className="text-xs font-bold text-[#2563EB] hover:underline flex items-center gap-1">
                      <CheckCircle2 className="w-3.5 h-3.5" /> Complete
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {!loading && filtered.length === 0 && (
          <ManagementEmptyState icon={ClipboardCheck} title="No inspections found" description="Inspections appear here after vehicle returns or when scheduled." />
        )}
      </ManagementCard>

      <Modal isOpen={!!completeModal} onClose={() => setCompleteModal(null)} title="Complete Inspection">
        <div className="space-y-4">
          <div>
            <label className={LABEL_CLS}>Result</label>
            <select className={INPUT_CLS} value={form.result} onChange={(e) => setForm({ ...form, result: e.target.value })}>
              <option value="passed">Passed</option>
              <option value="failed">Failed</option>
              <option value="requires_maintenance">Requires Maintenance</option>
            </select>
          </div>
          <div>
            <label className={LABEL_CLS}>Mileage (km)</label>
            <input type="number" className={INPUT_CLS} value={form.mileage} onChange={(e) => setForm({ ...form, mileage: e.target.value })} />
          </div>
          <div>
            <label className={LABEL_CLS}>Notes</label>
            <textarea className={INPUT_CLS} rows={3} value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
          </div>
          <ManagementButton onClick={handleComplete} disabled={submitting}>
            {submitting ? 'Saving...' : 'Complete Inspection'}
          </ManagementButton>
        </div>
      </Modal>
    </div>
  );
}
