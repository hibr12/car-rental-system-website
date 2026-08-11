import React, { useState, useEffect } from 'react';
import { Plus, Wrench, Calendar, DollarSign, Edit, Trash2 } from 'lucide-react';
import maintenanceApi from '../../api/maintenanceApi';
import vehicleApi from '../../api/vehicleApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

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
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <h1 className="text-3xl font-extrabold text-white tracking-tight">Fleet Maintenance Tracking</h1>
          <p className="text-sm text-slate-400">Schedule vehicle servicing, track repair costs, and update statuses.</p>
        </div>
        <button
          onClick={handleOpenCreateModal}
          className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-lg flex items-center gap-2 self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>New Maintenance Record</span>
        </button>
      </div>

      <div className="bg-theme-card border border-theme rounded-xl p-6 sm:p-8 space-y-6 shadow-xl">
        {loading ? (
          <div className="py-12 text-center text-slate-400 text-sm">Loading maintenance log...</div>
        ) : maintenanceRecords.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <Wrench className="w-12 h-12 text-slate-700 mx-auto" />
            <p className="text-sm font-semibold text-slate-300">No Maintenance Records Found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-slate-300">
              <thead className="text-xs uppercase bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Title / Type</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Start Date</th>
                  <th className="py-3.5 px-4 font-semibold">Cost</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60">
                {maintenanceRecords.map((m) => (
                  <tr key={m.id} className="hover:bg-slate-800/40 transition-colors">
                    <td className="py-4 px-4">
                      <p className="font-bold text-white">{m.title}</p>
                      <p className="text-[11px] text-slate-500">{m.maintenance_type}</p>
                    </td>
                    <td className="py-4 px-4 text-xs font-semibold text-slate-200">
                      {m.vehicle ? `${m.vehicle.brand} ${m.vehicle.model}` : `Vehicle #${m.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-xs text-slate-400">{formatDate(m.start_date)}</td>
                    <td className="py-4 px-4 font-bold text-rose-400">
                      {formatCurrency(m.cost || 0)}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(m.status)}`}>
                        {formatStatus(m.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-right space-x-2">
                      <button
                        onClick={() => handleOpenEditModal(m)}
                        className="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200"
                      >
                        <Edit className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => handleDeleteRecord(m.id)}
                        className="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
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
      </div>

      {/* Maintenance Record Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingRecord ? `Edit Maintenance: ${editingRecord.title}` : 'Log Vehicle Maintenance'}
        maxWidth="max-w-md"
      >
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div>
            <label className="block text-slate-300 font-semibold mb-1">Target Vehicle *</label>
            <select
              value={formData.vehicle_id}
              onChange={(e) => setFormData({ ...formData, vehicle_id: e.target.value })}
              className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
            >
              {vehicles.map((v) => (
                <option key={v.id} value={v.id}>
                  {v.brand} {v.model} ({v.registration_number})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-slate-300 font-semibold mb-1">Maintenance Title *</label>
            <input
              type="text"
              required
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              placeholder="e.g. Brake Replacement & Alignment"
              className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-slate-300 font-semibold mb-1">Type *</label>
              <input
                type="text"
                required
                value={formData.maintenance_type}
                onChange={(e) => setFormData({ ...formData, maintenance_type: e.target.value })}
                placeholder="Oil Change, Engine Repair..."
                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
              />
            </div>
            <div>
              <label className="block text-slate-300 font-semibold mb-1">Estimated Cost ($)</label>
              <input
                type="number"
                step="0.01"
                value={formData.cost}
                onChange={(e) => setFormData({ ...formData, cost: e.target.value })}
                placeholder="250.00"
                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-slate-300 font-semibold mb-1">Start Date *</label>
              <input
                type="date"
                required
                value={formData.start_date}
                onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
              />
            </div>
            <div>
              <label className="block text-slate-300 font-semibold mb-1">End Date</label>
              <input
                type="date"
                value={formData.end_date}
                onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
              />
            </div>
          </div>

          <div>
            <label className="block text-slate-300 font-semibold mb-1">Status</label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
            >
              <option value="scheduled">Scheduled</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div>
            <label className="block text-slate-300 font-semibold mb-1">Notes</label>
            <textarea
              rows="2"
              value={formData.notes}
              onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              placeholder="Parts replaced, garage details..."
              className="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-slate-100"
            />
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg"
          >
            {submitting ? 'Saving Record...' : editingRecord ? 'Update Record' : 'Save Maintenance Log'}
          </button>
        </form>
      </Modal>
    </div>
  );
};

export default MaintenancePage;
