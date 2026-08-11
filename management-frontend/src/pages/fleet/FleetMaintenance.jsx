import React, { useState, useEffect } from 'react';
import {
  Wrench,
  Loader2,
  Search,
  Filter,
  Plus,
  Edit3,
  Trash2,
  CalendarCheck
} from 'lucide-react';
import maintenanceApi from '../../api/maintenanceApi';
import vehicleApi from '../../api/vehicleApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { Modal } from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';

const emptyForm = {
  vehicle_id: '',
  maintenance_type: '',
  description: '',
  scheduled_date: '',
  cost: '',
  status: 'scheduled',
};

export const FleetMaintenance = () => {
  const [records, setRecords] = useState([]);
  const [vehicles, setVehicles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [editingRecord, setEditingRecord] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [submitting, setSubmitting] = useState(false);
  const toast = useToast();

  const fetchRecords = () => {
    setLoading(true);
    maintenanceApi
      .getAll({ per_page: 100 })
      .then((res) => {
        setRecords(res.data || []);
      })
      .catch((err) => {
        console.error('Failed to load maintenance records:', err);
        toast.error('Failed to load maintenance records.');
      })
      .finally(() => setLoading(false));
  };

  const fetchVehicles = () => {
    vehicleApi
      .getAll({ per_page: 200 })
      .then((res) => {
        setVehicles(res.data || []);
      })
      .catch((err) => {
        console.error('Failed to load vehicles:', err);
      });
  };

  useEffect(() => {
    fetchRecords();
    fetchVehicles();
  }, []);

  const openCreate = () => {
    setEditingRecord(null);
    setForm(emptyForm);
    setModalOpen(true);
  };

  const openEdit = (record) => {
    setEditingRecord(record);
    setForm({
      vehicle_id: record.vehicle_id || '',
      maintenance_type: record.maintenance_type || record.type || '',
      description: record.description || '',
      scheduled_date: record.scheduled_date ? record.scheduled_date.split('T')[0] : '',
      cost: record.cost || '',
      status: record.status || 'scheduled',
    });
    setModalOpen(true);
  };

  const handleChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const payload = {
        ...form,
        vehicle_id: Number(form.vehicle_id),
        cost: form.cost ? Number(form.cost) : 0,
      };

      if (editingRecord) {
        await maintenanceApi.update(editingRecord.id, payload);
        toast.success('Maintenance record updated successfully!');
      } else {
        await maintenanceApi.create(payload);
        toast.success('Maintenance record created successfully!');
      }
      setModalOpen(false);
      fetchRecords();
    } catch (err) {
      toast.error(err.message || 'Failed to save maintenance record.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this maintenance record?')) return;
    try {
      await maintenanceApi.delete(id);
      toast.success('Maintenance record deleted.');
      fetchRecords();
    } catch (err) {
      toast.error(err.message || 'Failed to delete maintenance record.');
    }
  };

  const filteredRecords = records.filter((r) => {
    const matchesStatus = !filterStatus || r.status === filterStatus;
    const vehicleName = r.vehicle ? `${r.vehicle.brand} ${r.vehicle.model}` : '';
    const matchesSearch =
      !searchQuery ||
      vehicleName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      r.maintenance_type?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      r.description?.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-theme pb-6">
        <div>
          <span className="text-xs uppercase font-extrabold tracking-wider text-indigo-400">
            Fleet Management
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Maintenance Records</h1>
          <p className="text-sm text-theme-muted mt-1">Schedule and track all vehicle maintenance activities</p>
        </div>
        <div className="flex items-center gap-3">
          <button
            onClick={fetchRecords}
            className="px-4 py-2.5 rounded-xl bg-theme-secondary border border-theme hover:bg-theme-hover text-theme-secondary font-semibold text-xs flex items-center gap-2 transition-colors"
          >
            <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            Refresh
          </button>
          <button
            onClick={openCreate}
            className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs flex items-center gap-2 shadow-md shadow-blue-600/20"
          >
            <Plus className="w-4 h-4" />
            Add Record
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-theme-card border border-theme rounded-2xl p-4 flex flex-col sm:flex-row gap-4 transition-colors duration-200">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by vehicle, type, or description..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
          />
        </div>
        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-theme-muted" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="bg-theme-input border border-theme rounded-xl px-3 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            <option value="">All Statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      {/* Maintenance Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        {loading ? (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <tbody>
                {[1, 2, 3, 4, 5].map((i) => (
                  <TableRowSkeleton key={i} cols={7} />
                ))}
              </tbody>
            </table>
          </div>
        ) : filteredRecords.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <Wrench className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Maintenance Records Found</p>
            <p className="text-xs text-theme-muted">
              {searchQuery || filterStatus ? 'Try adjusting your filters.' : 'No maintenance records yet.'}
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-hover text-theme-muted border-b border-theme">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Type</th>
                  <th className="py-3.5 px-4 font-semibold">Description</th>
                  <th className="py-3.5 px-4 font-semibold">Scheduled</th>
                  <th className="py-3.5 px-4 font-semibold">Cost</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {filteredRecords.map((record) => (
                  <tr key={record.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-medium text-theme-primary">
                      {record.vehicle ? `${record.vehicle.brand} ${record.vehicle.model}` : `Vehicle #${record.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-theme-secondary capitalize">{record.maintenance_type || record.type || 'General'}</td>
                    <td className="py-4 px-4 text-xs text-theme-muted max-w-[200px] truncate">{record.description || '-'}</td>
                    <td className="py-4 px-4 text-xs text-theme-muted">{formatDate(record.scheduled_date || record.created_at)}</td>
                    <td className="py-4 px-4 font-bold text-emerald-400">{formatCurrency(record.cost || 0)}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(record.status)}`}>
                        {formatStatus(record.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => openEdit(record)}
                          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition-colors"
                        >
                          <Edit3 className="w-3 h-3 inline mr-1" />
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(record.id)}
                          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition-colors"
                        >
                          <Trash2 className="w-3 h-3 inline mr-1" />
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Create / Edit Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingRecord ? 'Edit Maintenance Record' : 'Schedule Maintenance'}
      >
        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">Vehicle *</label>
            <select
              name="vehicle_id"
              value={form.vehicle_id}
              onChange={handleChange}
              required
              className="w-full bg-theme-input border border-theme rounded-xl px-4 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
            >
              <option value="">Select a vehicle</option>
              {vehicles.map((v) => (
                <option key={v.id} value={v.id}>{v.brand} {v.model} ({v.registration_number})</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">Maintenance Type *</label>
            <input
              name="maintenance_type"
              value={form.maintenance_type}
              onChange={handleChange}
              required
              placeholder="e.g. Oil Change, Brake Inspection"
              className="w-full bg-theme-input border border-theme rounded-xl px-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">Description</label>
            <textarea
              name="description"
              value={form.description}
              onChange={handleChange}
              rows={3}
              placeholder="Describe the maintenance work..."
              className="w-full bg-theme-input border border-theme rounded-xl px-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors resize-none"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">Scheduled Date *</label>
              <input
                name="scheduled_date"
                type="date"
                value={form.scheduled_date}
                onChange={handleChange}
                required
                className="w-full bg-theme-input border border-theme rounded-xl px-4 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">Cost</label>
              <input
                name="cost"
                type="number"
                min="0"
                step="0.01"
                value={form.cost}
                onChange={handleChange}
                placeholder="0.00"
                className="w-full bg-theme-input border border-theme rounded-xl px-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">Status</label>
            <select
              name="status"
              value={form.status}
              onChange={handleChange}
              className="w-full bg-theme-input border border-theme rounded-xl px-4 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
            >
              <option value="scheduled">Scheduled</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div className="flex items-center gap-3 pt-4 border-t border-theme">
            <button
              type="submit"
              disabled={submitting}
              className="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-semibold text-sm transition-colors"
            >
              {submitting ? (
                <Loader2 className="w-4 h-4 animate-spin inline mr-2" />
              ) : (
                <CalendarCheck className="w-4 h-4 inline mr-2" />
              )}
              {editingRecord ? 'Update Record' : 'Schedule Maintenance'}
            </button>
            <button
              type="button"
              onClick={() => setModalOpen(false)}
              className="px-6 py-2.5 rounded-xl bg-theme-secondary border border-theme hover:bg-theme-hover text-theme-secondary font-semibold text-sm transition-colors"
            >
              Cancel
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};

export default FleetMaintenance;
