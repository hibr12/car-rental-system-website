import React, { useState, useEffect } from 'react';
import { FileText, Loader2, Plus, Search, Trash2 } from 'lucide-react';
import documentApi from '../../api/documentApi';
import vehicleApi from '../../api/vehicleApi';
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

const emptyForm = {
  vehicle_id: '',
  document_type: 'insurance',
  document_number: '',
  issue_date: '',
  expiry_date: '',
  attachment_url: '',
  notes: '',
  is_required: true,
};

export default function FleetDocuments() {
  const [records, setRecords] = useState([]);
  const [vehicles, setVehicles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState('');
  const [search, setSearch] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [submitting, setSubmitting] = useState(false);
  const toast = useToast();

  const load = () => {
    setLoading(true);
    Promise.all([
      documentApi.getAll({ per_page: 100, status: filterStatus || undefined }),
      vehicleApi.getAll({ per_page: 200 }),
    ])
      .then(([docRes, vehRes]) => {
        setRecords(docRes.data || []);
        setVehicles(vehRes.data || []);
      })
      .catch(() => toast.error('Failed to load documents.'))
      .finally(() => setLoading(false));
  };

  useEffect(load, [filterStatus]);

  const filtered = records.filter((r) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return (
      r.document_number?.toLowerCase().includes(q) ||
      r.vehicle?.registration_number?.toLowerCase().includes(q)
    );
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await documentApi.create(form);
      toast.success('Document added.');
      setModalOpen(false);
      setForm(emptyForm);
      load();
    } catch (err) {
      toast.error(err.message || 'Failed to save document.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Delete this document record?')) return;
    try {
      await documentApi.delete(id);
      toast.success('Document deleted.');
      load();
    } catch (err) {
      toast.error(err.message || 'Failed to delete.');
    }
  };

  return (
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Fleet Compliance"
        title="Vehicle Documents"
        description="Registration, insurance, and certification tracking with expiry alerts"
        actions={
          <>
            <ManagementButton onClick={() => setModalOpen(true)}>
              <Plus className="w-4 h-4" /> Add Document
            </ManagementButton>
            <ManagementButton variant="secondary" onClick={load}>
              <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            </ManagementButton>
          </>
        }
      />

      <ManagementCard className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input className={`${INPUT_CLS} pl-10`} placeholder="Search documents..." value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
        <select className={INPUT_CLS} value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)}>
          <option value="">All statuses</option>
          <option value="valid">Valid</option>
          <option value="expiring_soon">Expiring Soon</option>
          <option value="expired">Expired</option>
        </select>
      </ManagementCard>

      <ManagementCard className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="mgmt-table-head">
            <tr>
              <th className="py-3.5 px-4">Vehicle</th>
              <th className="py-3.5 px-4">Type</th>
              <th className="py-3.5 px-4">Number</th>
              <th className="py-3.5 px-4">Expiry</th>
              <th className="py-3.5 px-4">Status</th>
              <th className="py-3.5 px-4">Required</th>
              <th className="py-3.5 px-4"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[#E2E8F0]">
            {loading && [...Array(5)].map((_, i) => <TableRowSkeleton key={i} cols={7} />)}
            {!loading && filtered.map((r) => (
              <tr key={r.id} className="mgmt-table-row">
                <td className="py-4 px-4 font-medium">{r.vehicle?.brand} {r.vehicle?.model}<div className="text-xs font-mono text-[#64748B]">{r.vehicle?.registration_number}</div></td>
                <td className="py-4 px-4 capitalize">{formatStatus(r.document_type)}</td>
                <td className="py-4 px-4 font-mono text-xs">{r.document_number || '—'}</td>
                <td className="py-4 px-4 text-xs">{formatDate(r.expiry_date)}</td>
                <td className="py-4 px-4"><span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(r.status)}`}>{formatStatus(r.status)}</span></td>
                <td className="py-4 px-4 text-xs">{r.is_required ? 'Yes' : 'No'}</td>
                <td className="py-4 px-4">
                  <button type="button" onClick={() => handleDelete(r.id)} className="text-[#DC2626] hover:opacity-70"><Trash2 className="w-4 h-4" /></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {!loading && filtered.length === 0 && (
          <ManagementEmptyState icon={FileText} title="No documents yet" description="Add registration, insurance, and inspection certificates for fleet vehicles." />
        )}
      </ManagementCard>

      <Modal isOpen={modalOpen} onClose={() => setModalOpen(false)} title="Add Vehicle Document">
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className={LABEL_CLS}>Vehicle</label>
            <select className={INPUT_CLS} required value={form.vehicle_id} onChange={(e) => setForm({ ...form, vehicle_id: e.target.value })}>
              <option value="">Select vehicle</option>
              {vehicles.map((v) => (
                <option key={v.id} value={v.id}>{v.brand} {v.model} — {v.registration_number}</option>
              ))}
            </select>
          </div>
          <div>
            <label className={LABEL_CLS}>Document Type</label>
            <select className={INPUT_CLS} value={form.document_type} onChange={(e) => setForm({ ...form, document_type: e.target.value })}>
              <option value="registration">Registration</option>
              <option value="insurance">Insurance</option>
              <option value="inspection_certificate">Inspection Certificate</option>
              <option value="roadworthiness">Roadworthiness</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label className={LABEL_CLS}>Document Number</label>
            <input className={INPUT_CLS} value={form.document_number} onChange={(e) => setForm({ ...form, document_number: e.target.value })} />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={LABEL_CLS}>Issue Date</label>
              <input type="date" className={INPUT_CLS} value={form.issue_date} onChange={(e) => setForm({ ...form, issue_date: e.target.value })} />
            </div>
            <div>
              <label className={LABEL_CLS}>Expiry Date</label>
              <input type="date" className={INPUT_CLS} value={form.expiry_date} onChange={(e) => setForm({ ...form, expiry_date: e.target.value })} />
            </div>
          </div>
          <div>
            <label className={LABEL_CLS}>Attachment URL</label>
            <input className={INPUT_CLS} value={form.attachment_url} onChange={(e) => setForm({ ...form, attachment_url: e.target.value })} />
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" checked={form.is_required} onChange={(e) => setForm({ ...form, is_required: e.target.checked })} />
            Required for rental eligibility
          </label>
          <ManagementButton type="submit" disabled={submitting}>{submitting ? 'Saving...' : 'Save Document'}</ManagementButton>
        </form>
      </Modal>
    </div>
  );
}
