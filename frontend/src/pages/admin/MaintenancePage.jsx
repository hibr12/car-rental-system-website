import React, { useState, useEffect } from 'react';
import { Plus, Wrench, Calendar, DollarSign, Edit, Trash2 } from 'lucide-react';
import maintenanceApi from '../../api/maintenanceApi';
import vehicleApi from '../../api/vehicleApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

export const MaintenancePage = () => {
  const toast = useToast();
  const [maintenanceRecords, setMaintenanceRecords] = useState([]);
  const [vehicles, setVehicles] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });

  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  const [modalOpen, setModalOpen] = useState(false);
  const [editingRecord, setEditingRecord] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const today = new Date().toISOString().split('T')[0];

  const initialForm = {
    vehicle_id: '',
    title: '',
    description: '',
    maintenance_type: 'Oil Change & Inspection',
    cost: '',
    start_date: today,
    end_date: '',
    status: 'scheduled',
    notes: '',
  };

  const [formData, setFormData] = useState(initialForm);

  const fetchRecords = async () => {
    try {
      setLoading(true);
      const res = await maintenanceApi.getAll({ page, per_page: 10 });
      setMaintenanceRecords(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error('Failed to load maintenance records.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    vehicleApi.getAll({ per_page: 100 }).then((res) => setVehicles(res.data || []));
  }, []);

  useEffect(() => {
    fetchRecords();
  }, [page]);

  const handleOpenCreateModal = () => {
    setEditingRecord(null);
    setFormData({
      ...initialForm,
      vehicle_id: vehicles[0]?.id || '',
    });
    setModalOpen(true);
  };

  const handleOpenEditModal = (rec) => {
    setEditingRecord(rec);
    setFormData({
      vehicle_id: rec.vehicle_id || vehicles[0]?.id || '',
      title: rec.title || '',
      description: rec.description || '',
      maintenance_type: rec.maintenance_type || 'Scheduled Service',
      cost: rec.cost || '',
      start_date: rec.start_date ? rec.start_date.split('T')[0] : today,
      end_date: rec.end_date ? rec.end_date.split('T')[0] : '',
      status: rec.status || 'scheduled',
      notes: rec.notes || '',
    });
    setModalOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      const payload = {
        ...formData,
        vehicle_id: parseInt(formData.vehicle_id, 10),
        cost: formData.cost ? parseFloat(formData.cost) : 0,
        end_date: formData.end_date || undefined,
      };

      if (editingRecord) {
        await maintenanceApi.update(editingRecord.id, payload);
        toast.success('Maintenance record updated!');
      } else {
        await maintenanceApi.create(payload);
        toast.success('Maintenance record logged!');
      }

      setModalOpen(false);
      fetchRecords();
    } catch (err) {
      toast.error(err.message || 'Failed to save maintenance record.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteRecord = async (id) => {
    if (!window.confirm('Delete this maintenance record?')) return;
    try {
      await maintenanceApi.delete(id);
      toast.success('Record deleted.');
      fetchRecords();
    } catch (err) {
      toast.error(err.message || 'Failed to delete maintenance record.');
    }
  };

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="Fleet Maintenance Tracking"
        description="Schedule vehicle servicing, track repair costs, and update statuses."
        actions={
          <ManagementButton onClick={handleOpenCreateModal}>
            <Plus className="w-4 h-4" />
            <span>New Maintenance Record</span>
          </ManagementButton>
        }
      />

      <ManagementCard className="space-y-6">
        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading maintenance log...</div>
        ) : maintenanceRecords.length === 0 ? (
          <ManagementEmptyState icon={Wrench} title="No Maintenance Records Found" />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase bg-[#F8FAFC] text-[#334155] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Title / Type</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Start Date</th>
                  <th className="py-3.5 px-4 font-semibold">Cost</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {maintenanceRecords.map((m) => (
                  <tr key={m.id} className="hover:bg-[#F8FAFC] transition-colors">
                    <td className="py-4 px-4">
                      <p className="font-bold text-[#0F172A]">{m.title}</p>
                      <p className="text-[11px] text-[#64748B]">{m.maintenance_type}</p>
                    </td>
                    <td className="py-4 px-4 text-xs font-semibold text-[#334155]">
                      {m.vehicle ? `${m.vehicle.brand} ${m.vehicle.model}` : `Vehicle #${m.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(m.start_date)}</td>
                    <td className="py-4 px-4 font-bold text-[#DC2626]">
                      {formatCurrency(m.cost || 0)}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(m.status)}`}>
                        {formatStatus(m.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-right space-x-2">
                      <ManagementButton variant="secondary" onClick={() => handleOpenEditModal(m)}>
                        <Edit className="w-4 h-4" />
                      </ManagementButton>
                      <ManagementButton variant="dangerOutline" onClick={() => handleDeleteRecord(m.id)}>
                        <Trash2 className="w-4 h-4" />
                      </ManagementButton>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 && (
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(p) => setPage(p)}
          />
        )}
      </ManagementCard>

      {/* Maintenance Record Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingRecord ? `Edit Maintenance: ${editingRecord.title}` : 'Log Vehicle Maintenance'}
        maxWidth="max-w-md"
      >
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div>
            <label className="block text-[#334155] font-semibold mb-1">Target Vehicle *</label>
            <select
              value={formData.vehicle_id}
              onChange={(e) => setFormData({ ...formData, vehicle_id: e.target.value })}
              className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
            >
              {vehicles.map((v) => (
                <option key={v.id} value={v.id}>
                  {v.brand} {v.model} ({v.registration_number})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-[#334155] font-semibold mb-1">Maintenance Title *</label>
            <input
              type="text"
              required
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              placeholder="e.g. Brake Replacement & Alignment"
              className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Type *</label>
              <input
                type="text"
                required
                value={formData.maintenance_type}
                onChange={(e) => setFormData({ ...formData, maintenance_type: e.target.value })}
                placeholder="Oil Change, Engine Repair..."
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Estimated Cost ($)</label>
              <input
                type="number"
                step="0.01"
                value={formData.cost}
                onChange={(e) => setFormData({ ...formData, cost: e.target.value })}
                placeholder="250.00"
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Start Date *</label>
              <input
                type="date"
                required
                value={formData.start_date}
                onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">End Date</label>
              <input
                type="date"
                value={formData.end_date}
                onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
          </div>

          <div>
            <label className="block text-[#334155] font-semibold mb-1">Status</label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
            >
              <option value="scheduled">Scheduled</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div>
            <label className="block text-[#334155] font-semibold mb-1">Notes</label>
            <textarea
              rows="2"
              value={formData.notes}
              onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              placeholder="Parts replaced, garage details..."
              className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
            />
          </div>

          <ManagementButton type="submit" disabled={submitting} className="w-full py-3.5">
            {submitting ? 'Saving Record...' : editingRecord ? 'Update Record' : 'Save Maintenance Log'}
          </ManagementButton>
        </form>
      </Modal>
    </div>
  );
};

export default MaintenancePage;
