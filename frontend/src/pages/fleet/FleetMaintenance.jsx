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
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

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
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Fleet Management"
        title="Maintenance Records"
        description="Schedule and track all vehicle maintenance activities"
        actions={
          <>
            <ManagementButton variant="secondary" onClick={fetchRecords}>
              <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
              Refresh
            </ManagementButton>
            <ManagementButton variant="primary" onClick={openCreate}>
              <Plus className="w-4 h-4" />
              Add Record
            </ManagementButton>
          </>
        }
      />

      {/* Filters */}
      <ManagementCard className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by vehicle, type, or description..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="mgmt-input pl-10"
          />
        </div>
        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-[#64748B]" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="mgmt-input w-auto"
          >
            <option value="">All Statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </ManagementCard>

      {/* Maintenance Table */}
      <ManagementCard className="rounded-2xl space-y-6">
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
          <ManagementEmptyState
            icon={Wrench}
            title="No Maintenance Records Found"
            description={
              searchQuery || filterStatus ? 'Try adjusting your filters.' : 'No maintenance records yet.'
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="mgmt-table-head">
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
              <tbody className="divide-y divide-[#E2E8F0]">
                {filteredRecords.map((record) => (
                  <tr key={record.id} className="mgmt-table-row">
                    <td className="py-4 px-4 font-medium text-[#0F172A]">
                      {record.vehicle ? `${record.vehicle.brand} ${record.vehicle.model}` : `Vehicle #${record.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-[#334155] capitalize">{record.maintenance_type || record.type || 'General'}</td>
                    <td className="py-4 px-4 text-xs text-[#64748B] max-w-[200px] truncate">{record.description || '-'}</td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(record.scheduled_date || record.created_at)}</td>
                    <td className="py-4 px-4 font-bold text-[#16A34A]">{formatCurrency(record.cost || 0)}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(record.status)}`}>
                        {formatStatus(record.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => openEdit(record)}
                          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-[#2563EB] border border-blue-200 hover:bg-blue-100 transition-colors"
                        >
                          <Edit3 className="w-3 h-3 inline mr-1" />
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(record.id)}
                          className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-red-50 text-[#DC2626] border border-red-200 hover:bg-red-100 transition-colors"
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
      </ManagementCard>

      {/* Create / Edit Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingRecord ? 'Edit Maintenance Record' : 'Schedule Maintenance'}
      >
        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Vehicle *</label>
            <select
              name="vehicle_id"
              value={form.vehicle_id}
              onChange={handleChange}
              required
              className="mgmt-input"
            >
              <option value="">Select a vehicle</option>
              {vehicles.map((v) => (
                <option key={v.id} value={v.id}>{v.brand} {v.model} ({v.registration_number})</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Maintenance Type *</label>
            <input
              name="maintenance_type"
              value={form.maintenance_type}
              onChange={handleChange}
              required
              placeholder="e.g. Oil Change, Brake Inspection"
              className="mgmt-input"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Description</label>
            <textarea
              name="description"
              value={form.description}
              onChange={handleChange}
              rows={3}
              placeholder="Describe the maintenance work..."
              className="mgmt-input resize-none"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Scheduled Date *</label>
              <input
                name="scheduled_date"
                type="date"
                value={form.scheduled_date}
                onChange={handleChange}
                required
                className="mgmt-input"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Cost</label>
              <input
                name="cost"
                type="number"
                min="0"
                step="0.01"
                value={form.cost}
                onChange={handleChange}
                placeholder="0.00"
                className="mgmt-input"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Status</label>
            <select
              name="status"
              value={form.status}
              onChange={handleChange}
              className="mgmt-input"
            >
              <option value="scheduled">Scheduled</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div className="flex items-center gap-3 pt-4 border-t border-[#E2E8F0]">
            <ManagementButton type="submit" variant="primary" disabled={submitting} className="px-6 py-2.5 text-sm">
              {submitting ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <CalendarCheck className="w-4 h-4" />
              )}
              {editingRecord ? 'Update Record' : 'Schedule Maintenance'}
            </ManagementButton>
            <ManagementButton
              type="button"
              variant="secondary"
              onClick={() => setModalOpen(false)}
              className="px-6 py-2.5 text-sm"
            >
              Cancel
            </ManagementButton>
          </div>
        </form>
      </Modal>
    </div>
  );
};

export default FleetMaintenance;
