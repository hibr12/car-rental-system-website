import React, { useState, useEffect } from 'react';
import { Wrench, Plus, Loader2 } from 'lucide-react';
import branchApi from '../../api/branchApi';
import vehicleApi from '../../api/vehicleApi';
import { formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { Modal } from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const emptyForm = { vehicle_id: '', title: '', description: '', priority: 'medium' };

export default function BranchMaintenanceRequests() {
  const [records, setRecords] = useState([]);
  const [vehicles, setVehicles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [submitting, setSubmitting] = useState(false);
  const toast = useToast();

  const load = () => {
    setLoading(true);
    branchApi.getMaintenanceRequests({ per_page: 50 })
      .then((r) => setRecords(r.data || []))
      .catch(() => toast.error('Failed to load maintenance requests.'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
    vehicleApi.getAll({ per_page: 100 }).then((r) => setVehicles(r.data || []));
  }, []);

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await branchApi.createMaintenanceRequest(form);
      toast.success('Maintenance request submitted.');
      setModalOpen(false);
      setForm(emptyForm);
      load();
    } catch (err) {
      toast.error(err.message || 'Failed to submit request.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Branch Operations"
        title="Maintenance Requests"
        description="Request fleet maintenance for branch vehicles"
        actions={
          <ManagementButton onClick={() => setModalOpen(true)}>
            <Plus className="w-4 h-4" /> New Request
          </ManagementButton>
        }
      />

      <ManagementCard className="overflow-x-auto">
        <table className="w-full text-sm text-left">
          <thead className="mgmt-table-head">
            <tr>
              <th className="py-3 px-4">Vehicle</th>
              <th className="py-3 px-4">Title</th>
              <th className="py-3 px-4">Priority</th>
              <th className="py-3 px-4">Status</th>
              <th className="py-3 px-4">Requested</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[#E2E8F0]">
            {!loading && records.map((r) => (
              <tr key={r.id} className="mgmt-table-row">
                <td className="py-3 px-4">{r.vehicle?.brand} {r.vehicle?.model}<div className="text-xs font-mono text-[#64748B]">{r.vehicle?.registration_number}</div></td>
                <td className="py-3 px-4">{r.title}</td>
                <td className="py-3 px-4 capitalize">{r.priority}</td>
                <td className="py-3 px-4"><span className={`px-2 py-1 text-xs font-bold rounded-lg border ${getStatusBadgeStyle(r.status)}`}>{formatStatus(r.status)}</span></td>
                <td className="py-3 px-4 text-xs text-[#64748B]">{formatDate(r.created_at)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        {!loading && records.length === 0 && (
          <ManagementEmptyState icon={Wrench} title="No maintenance requests" description="Submit a request when a vehicle needs fleet attention." />
        )}
      </ManagementCard>

      <Modal isOpen={modalOpen} onClose={() => setModalOpen(false)} title="New Maintenance Request">
        <form onSubmit={submit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold mb-1">Vehicle</label>
            <select className="mgmt-input" required value={form.vehicle_id} onChange={(e) => setForm({ ...form, vehicle_id: e.target.value })}>
              <option value="">Select vehicle</option>
              {vehicles.map((v) => <option key={v.id} value={v.id}>{v.brand} {v.model} — {v.registration_number}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-xs font-semibold mb-1">Title</label>
            <input className="mgmt-input" required value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
          </div>
          <div>
            <label className="block text-xs font-semibold mb-1">Description</label>
            <textarea className="mgmt-input" rows={3} required value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
          </div>
          <div>
            <label className="block text-xs font-semibold mb-1">Priority</label>
            <select className="mgmt-input" value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })}>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <ManagementButton type="submit" disabled={submitting}>{submitting ? 'Submitting...' : 'Submit Request'}</ManagementButton>
        </form>
      </Modal>
    </div>
  );
}
