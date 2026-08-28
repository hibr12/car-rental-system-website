import React, { useState, useEffect, useMemo } from 'react';
import { useLocation } from 'react-router-dom';
import { Plus, Search, Edit, Trash2, Car, ArrowRightLeft, History, Loader2, X, Filter, ChevronDown } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import categoryApi from '../../api/categoryApi';
import adminApi from '../../api/adminApi';
import transferApi from '../../api/transferApi';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const TRANSFER_STATUS_STYLES = {
  pending: 'bg-amber-50 text-[#F59E0B] border border-amber-100',
  approved: 'bg-blue-50 text-[#2563EB] border border-blue-100',
  in_transit: 'bg-purple-50 text-[#7C3AED] border border-purple-100',
};

const INPUT_CLS = 'w-full px-3 py-2 text-sm border border-[#CBD5E1] rounded-lg bg-white text-[#0F172A] focus:outline-none focus:border-[#2563EB]';
const LABEL_CLS = 'block text-xs font-semibold text-[#334155] mb-1';

export const VehicleManagement = () => {
  const location = useLocation();
  const transfersBase = location.pathname.startsWith('/manager') ? '/manager/transfers' : '/admin/transfers';
  const toast = useToast();
  const [vehicles, setVehicles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });

  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [branchFilter, setBranchFilter] = useState('');

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [editingVehicle, setEditingVehicle] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  // Form State
  const initialForm = {
    category_id: '',
    brand: '',
    model: '',
    year: new Date().getFullYear(),
    registration_number: '',
    vin_number: '',
    description: '',
    fuel_type: 'petrol',
    transmission: 'automatic',
    seats: 5,
    color: '',
    rental_price_per_day: '',
    status: 'available',
    featured: false,
    images: [{ image_url: '', is_primary: true }],
  };

  const [formData, setFormData] = useState(initialForm);

  const [branches, setBranches] = useState([]);
  const [transferModalOpen, setTransferModalOpen] = useState(false);
  const [transferVehicle, setTransferVehicle] = useState(null);
  const [transferForm, setTransferForm] = useState({ to_branch_id: '', transfer_date: '', reason: '', notes: '' });
  const [transferSaving, setTransferSaving] = useState(false);
  const [transferError, setTransferError] = useState('');
  const [historyModalOpen, setHistoryModalOpen] = useState(false);
  const [historyVehicle, setHistoryVehicle] = useState(null);
  const [historyItems, setHistoryItems] = useState([]);
  const [historyLoading, setHistoryLoading] = useState(false);

  const fetchVehicles = async () => {
    try {
      setLoading(true);
      const res = await vehicleApi.getAll({ search, page, per_page: 10, branch_id: branchFilter || undefined });
      setVehicles(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error('Failed to load vehicles list.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    categoryApi.getAll().then((res) => setCategories(res.data || []));
    adminApi.getBranches({ status: 'active' }).then((res) => setBranches(res.data || []));
  }, []);

  useEffect(() => {
    fetchVehicles();
  }, [search, page, branchFilter]);

  const handleOpenCreateModal = () => {
    setEditingVehicle(null);
    setFormData({
      ...initialForm,
      category_id: categories[0]?.id || '',
      branch_id: branches[0]?.id || '',
    });
    setModalOpen(true);
  };

  const handleOpenEditModal = (vehicle) => {
    setEditingVehicle(vehicle);
    setFormData({
      category_id: vehicle.category?.id || categories[0]?.id || '',
      branch_id: vehicle.branch_id || '',
      brand: vehicle.brand || '',
      model: vehicle.model || '',
      year: vehicle.year || new Date().getFullYear(),
      registration_number: vehicle.registration_number || '',
      vin_number: vehicle.vin_number || '',
      description: vehicle.description || '',
      fuel_type: vehicle.fuel_type || 'petrol',
      transmission: vehicle.transmission || 'automatic',
      seats: vehicle.seats || 5,
      color: vehicle.color || '',
      rental_price_per_day: vehicle.rental_price_per_day || '',
      status: vehicle.status || 'available',
      featured: !!vehicle.featured,
      images:
        vehicle.images && vehicle.images.length > 0
          ? vehicle.images.map((img) => ({ image_url: img.image_url, is_primary: !!img.is_primary }))
          : [{ image_url: '', is_primary: true }],
    });
    setModalOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      const validImages = formData.images.filter((img) => img.image_url.trim() !== '');

      const payload = {
        ...formData,
        category_id: parseInt(formData.category_id, 10),
        branch_id: formData.branch_id ? parseInt(formData.branch_id, 10) : undefined,
        year: parseInt(formData.year, 10),
        seats: parseInt(formData.seats, 10),
        rental_price_per_day: parseFloat(formData.rental_price_per_day),
        images: validImages.length > 0 ? validImages : undefined,
      };

      if (editingVehicle) {
        await vehicleApi.update(editingVehicle.id, payload);
        toast.success('Vehicle updated successfully!');
      } else {
        await vehicleApi.create(payload);
        toast.success('Vehicle added to fleet successfully!');
      }

      setModalOpen(false);
      fetchVehicles();
    } catch (err) {
      toast.error(err.message || 'Failed to save vehicle data.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteVehicle = async (id) => {
    if (!window.confirm('Are you sure you want to delete this vehicle from the fleet?')) return;
    try {
      await vehicleApi.delete(id);
      toast.success('Vehicle removed successfully.');
      fetchVehicles();
    } catch (err) {
      toast.error(err.message || 'Failed to delete vehicle.');
    }
  };

  const handleAddImageField = () => {
    setFormData({
      ...formData,
      images: [...formData.images, { image_url: '', is_primary: false }],
    });
  };

  const handleImageChange = (index, value) => {
    const updated = [...formData.images];
    updated[index].image_url = value;
    setFormData({ ...formData, images: updated });
  };

  const openTransferModal = (vehicle) => {
    setTransferVehicle(vehicle);
    setTransferForm({
      to_branch_id: '',
      transfer_date: new Date().toISOString().split('T')[0],
      reason: '',
      notes: '',
    });
    setTransferError('');
    setTransferModalOpen(true);
  };

  const submitTransfer = async (e) => {
    e.preventDefault();
    if (!transferVehicle) return;
    setTransferSaving(true);
    setTransferError('');
    try {
      await transferApi.create({
        vehicle_id: transferVehicle.id,
        to_branch_id: transferForm.to_branch_id,
        transfer_date: transferForm.transfer_date,
        reason: transferForm.reason || undefined,
        notes: transferForm.notes || undefined,
      });
      toast.success('Transfer request submitted.');
      setTransferModalOpen(false);
      fetchVehicles();
    } catch (err) {
      setTransferError(err.message || 'Failed to create transfer request.');
    } finally {
      setTransferSaving(false);
    }
  };

  const openHistoryModal = async (vehicle) => {
    setHistoryVehicle(vehicle);
    setHistoryModalOpen(true);
    setHistoryLoading(true);
    setHistoryItems([]);
    try {
      const res = await transferApi.getAll({ vehicle_id: vehicle.id, status: 'completed', per_page: 50 });
      setHistoryItems(res.data || []);
    } catch {
      toast.error('Failed to load transfer history.');
    } finally {
      setHistoryLoading(false);
    }
  };

  const renderTransferStatus = (vehicle) => {
    const t = vehicle.active_transfer;
    if (!t) {
      return <span className="text-xs text-[#64748B]">None</span>;
    }
    return (
      <span className={`text-[10px] font-semibold px-2 py-1 rounded-full uppercase ${TRANSFER_STATUS_STYLES[t.status] || 'bg-[#F8FAFC] text-[#64748B]'}`}>
        {t.status.replace('_', ' ')}
      </span>
    );
  };

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="Fleet Vehicle Management"
        description="Add, edit, update status, and manage vehicle specs & images."
        actions={
          <ManagementButton onClick={handleOpenCreateModal}>
            <Plus className="w-4 h-4" />
            <span>Add New Vehicle</span>
          </ManagementButton>
        }
      />

      <ManagementCard padding={false} className="p-4 sm:p-5">
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search by brand, model, registration..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-white border border-[#CBD5E1] rounded-xl pl-10 pr-4 py-2.5 text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:border-[#2563EB]"
            />
          </div>
          <div className="w-full sm:w-48">
            <select
              value={branchFilter}
              onChange={(e) => setBranchFilter(e.target.value)}
              className="w-full bg-white border border-[#CBD5E1] rounded-xl pl-4 pr-4 py-2.5 text-sm text-[#0F172A] focus:outline-none focus:border-[#2563EB] appearance-none bg-no-repeat bg-right"
              style={{ backgroundImage: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2364748B' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E\")", backgroundSize: "1.5rem", backgroundPosition: "right 0.5rem center" }}
            >
              <option value="">All Branches</option>
              {branches.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      </ManagementCard>

      <ManagementCard className="space-y-6">
        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading vehicle fleet...</div>
        ) : vehicles.length === 0 ? (
          <ManagementEmptyState icon={Car} title="No Vehicles Found" />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase bg-[#F8FAFC] text-[#334155] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Category</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Reg Number</th>
                  <th className="py-3.5 px-4 font-semibold">Price/Day</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Transfer</th>
                  <th className="py-3.5 px-4 font-semibold">Featured</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {vehicles.map((v) => (
                  <tr key={v.id} className="hover:bg-[#F8FAFC] transition-colors">
                    <td className="py-4 px-4 font-medium text-[#0F172A]">
                      <div className="flex items-center gap-3">
                        <div className="w-12 h-9 rounded-lg overflow-hidden bg-[#F8FAFC] border border-[#E2E8F0] shrink-0">
                          <img
                            src={
                              v.primary_image?.image_url ||
                              v.images?.[0]?.image_url ||
                              'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=300&q=80'
                            }
                            alt=""
                            className="w-full h-full object-cover"
                          />
                        </div>
                        <div>
                          <p className="font-bold">{v.brand} {v.model}</p>
                          <p className="text-[11px] text-[#64748B]">{v.year} • {v.fuel_type}</p>
                        </div>
                      </div>
                    </td>
                    <td className="py-4 px-4 text-xs font-semibold text-[#334155]">
                      {v.category?.name || 'Uncategorized'}
                    </td>
                    <td className="py-4 px-4 text-xs text-[#334155]">
                      {v.branch?.name || '—'}
                    </td>
                    <td className="py-4 px-4 text-xs font-mono text-[#64748B]">
                      {v.registration_number}
                    </td>
                    <td className="py-4 px-4 font-bold text-[#16A34A]">
                      {formatCurrency(v.rental_price_per_day)}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(v.status)}`}>
                        {formatStatus(v.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      {renderTransferStatus(v)}
                    </td>
                    <td className="py-4 px-4">
                      {v.featured ? (
                        <span className="px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-blue-50 text-[#2563EB] border border-blue-100">
                          Featured
                        </span>
                      ) : (
                        <span className="text-xs text-[#64748B]">Standard</span>
                      )}
                    </td>
                    <td className="py-4 px-4 text-right">
                      <div className="flex justify-end gap-1 flex-wrap">
                        {!v.active_transfer && v.status === 'available' && (
                          <ManagementButton variant="secondary" onClick={() => openTransferModal(v)} title="Transfer Vehicle">
                            <ArrowRightLeft className="w-4 h-4" />
                          </ManagementButton>
                        )}
                        {(v.completed_transfers_count > 0 || v.active_transfer) && (
                          <ManagementButton variant="secondary" onClick={() => openHistoryModal(v)} title="Transfer History">
                            <History className="w-4 h-4" />
                          </ManagementButton>
                        )}
                        <ManagementButton variant="secondary" onClick={() => handleOpenEditModal(v)} title="Edit Vehicle">
                          <Edit className="w-4 h-4" />
                        </ManagementButton>
                        <ManagementButton variant="dangerOutline" onClick={() => handleDeleteVehicle(v.id)} title="Delete Vehicle">
                          <Trash2 className="w-4 h-4" />
                        </ManagementButton>
                      </div>
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

      {/* Vehicle Form Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingVehicle ? `Edit Vehicle: ${editingVehicle.brand} ${editingVehicle.model}` : 'Add New Fleet Vehicle'}
      >
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Brand *</label>
              <input
                type="text"
                required
                value={formData.brand}
                onChange={(e) => setFormData({ ...formData, brand: e.target.value })}
                placeholder="e.g. BMW, Mercedes, Toyota"
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Model *</label>
              <input
                type="text"
                required
                value={formData.model}
                onChange={(e) => setFormData({ ...formData, model: e.target.value })}
                placeholder="e.g. M5, C-Class, RAV4"
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
          </div>

          <div className="grid grid-cols-4 gap-3">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Year *</label>
              <input
                type="number"
                required
                value={formData.year}
                onChange={(e) => setFormData({ ...formData, year: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Category *</label>
              <select
                value={formData.category_id}
                onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              >
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Branch *</label>
              <select
                value={formData.branch_id}
                onChange={(e) => setFormData({ ...formData, branch_id: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              >
                <option value="">Select Branch</option>
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>
                    {b.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Daily Price ($) *</label>
              <input
                type="number"
                step="0.01"
                required
                value={formData.rental_price_per_day}
                onChange={(e) => setFormData({ ...formData, rental_price_per_day: e.target.value })}
                placeholder="100.00"
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Registration Number *</label>
              <input
                type="text"
                required
                value={formData.registration_number}
                onChange={(e) => setFormData({ ...formData, registration_number: e.target.value })}
                placeholder="ABC-1234"
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
            <div>
              <label className="block text-[#334155] font-semibold mb-1">VIN Number</label>
              <input
                type="text"
                value={formData.vin_number}
                onChange={(e) => setFormData({ ...formData, vin_number: e.target.value })}
                placeholder="17-digit VIN"
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              />
            </div>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <div>
              <label className="block text-[#334155] font-semibold mb-1">Fuel Type</label>
              <select
                value={formData.fuel_type}
                onChange={(e) => setFormData({ ...formData, fuel_type: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              >
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="electric">Electric</option>
                <option value="hybrid">Hybrid</option>
              </select>
            </div>

            <div>
              <label className="block text-[#334155] font-semibold mb-1">Transmission</label>
              <select
                value={formData.transmission}
                onChange={(e) => setFormData({ ...formData, transmission: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              >
                <option value="automatic">Automatic</option>
                <option value="manual">Manual</option>
              </select>
            </div>

            <div>
              <label className="block text-[#334155] font-semibold mb-1">Status</label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              >
                <option value="available">Available</option>
                <option value="rented">Rented</option>
                <option value="maintenance">Maintenance</option>
                <option value="unavailable">Unavailable</option>
              </select>
            </div>
          </div>

          {/* Description */}
          <div>
            <label className="block text-[#334155] font-semibold mb-1">Description</label>
            <textarea
              rows="3"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Vehicle features, condition, specs..."
              className="w-full bg-white border border-[#CBD5E1] rounded-xl p-2.5 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
            />
          </div>

          {/* Featured Checkbox */}
          <div className="flex items-center gap-2 pt-1">
            <input
              type="checkbox"
              id="featured_check"
              checked={formData.featured}
              onChange={(e) => setFormData({ ...formData, featured: e.target.checked })}
              className="w-4 h-4 rounded border-[#CBD5E1] text-[#2563EB] focus:ring-[#2563EB]"
            />
            <label htmlFor="featured_check" className="text-[#334155] font-semibold cursor-pointer">
              Mark as Featured Vehicle on Homepage
            </label>
          </div>

          {/* Image URLs */}
          <div className="space-y-2 pt-2 border-t border-[#E2E8F0]">
            <div className="flex justify-between items-center">
              <label className="block text-[#334155] font-semibold">Image URLs</label>
              <button
                type="button"
                onClick={handleAddImageField}
                className="text-[11px] text-[#2563EB] hover:underline"
              >
                + Add Image Link
              </button>
            </div>
            {formData.images.map((img, idx) => (
              <div key={idx} className="flex gap-2 items-center">
                <input
                  type="url"
                  placeholder="https://images.unsplash.com/..."
                  value={img.image_url}
                  onChange={(e) => handleImageChange(idx, e.target.value)}
                  className="flex-1 bg-white border border-[#CBD5E1] rounded-xl p-2 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
                />
              </div>
            ))}
          </div>

          <ManagementButton type="submit" disabled={submitting} className="w-full py-3.5">
            {submitting ? 'Saving Vehicle...' : editingVehicle ? 'Update Vehicle' : 'Save New Vehicle'}
          </ManagementButton>
        </form>
      </Modal>

      {transferModalOpen && transferVehicle && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-md">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">Transfer Vehicle</h2>
              <button onClick={() => setTransferModalOpen(false)} className="text-[#64748B] hover:text-[#334155]">
                <X className="w-5 h-5" />
              </button>
            </div>
            <form onSubmit={submitTransfer} className="p-6 space-y-4">
              {transferError && (
                <div className="bg-red-50 text-[#DC2626] text-sm p-3 rounded-lg border border-red-100">{transferError}</div>
              )}
              <div className="text-sm">
                <p className="font-bold text-[#0F172A]">{transferVehicle.brand} {transferVehicle.model}</p>
                <p className="text-xs text-[#64748B]">{transferVehicle.registration_number}</p>
              </div>
              <div>
                <label className={LABEL_CLS}>Current Branch</label>
                <input readOnly value={transferVehicle.branch?.name || '—'} className={`${INPUT_CLS} bg-[#F8FAFC]`} />
              </div>
              <div>
                <label className={LABEL_CLS}>Destination Branch *</label>
                <select
                  required
                  value={transferForm.to_branch_id}
                  onChange={e => setTransferForm(p => ({ ...p, to_branch_id: e.target.value }))}
                  className={INPUT_CLS}
                >
                  <option value="">Select branch</option>
                  {branches.filter(b => String(b.id) !== String(transferVehicle.branch_id)).map(b => (
                    <option key={b.id} value={b.id}>{b.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className={LABEL_CLS}>Transfer Date *</label>
                <input
                  type="date"
                  required
                  min={new Date().toISOString().split('T')[0]}
                  value={transferForm.transfer_date}
                  onChange={e => setTransferForm(p => ({ ...p, transfer_date: e.target.value }))}
                  className={INPUT_CLS}
                />
              </div>
              <div>
                <label className={LABEL_CLS}>Reason</label>
                <textarea
                  rows={2}
                  value={transferForm.reason}
                  onChange={e => setTransferForm(p => ({ ...p, reason: e.target.value }))}
                  className={`${INPUT_CLS} resize-none`}
                />
              </div>
              <div className="flex gap-3 pt-2">
                <ManagementButton type="button" variant="secondary" onClick={() => setTransferModalOpen(false)} className="flex-1">
                  Cancel
                </ManagementButton>
                <ManagementButton type="submit" disabled={transferSaving} className="flex-1">
                  {transferSaving ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
                  {transferSaving ? 'Submitting…' : 'Submit Request'}
                </ManagementButton>
              </div>
            </form>
          </div>
        </div>
      )}

      {historyModalOpen && historyVehicle && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <div>
                <h2 className="text-lg font-bold text-[#0F172A]">Transfer History</h2>
                <p className="text-xs text-[#64748B]">{historyVehicle.brand} {historyVehicle.model}</p>
              </div>
              <button onClick={() => setHistoryModalOpen(false)} className="text-[#64748B] hover:text-[#334155]">
                <X className="w-5 h-5" />
              </button>
            </div>
            <div className="p-6">
              {historyLoading ? (
                <div className="flex justify-center py-10"><Loader2 className="w-6 h-6 animate-spin text-[#2563EB]" /></div>
              ) : historyItems.length === 0 ? (
                <p className="text-sm text-[#64748B] text-center py-8">No completed transfers for this vehicle.</p>
              ) : (
                <div className="space-y-3">
                  {historyItems.map(h => (
                    <div key={h.id} className="flex justify-between items-center border border-[#E2E8F0] rounded-lg p-3">
                      <div>
                        <p className="text-sm font-semibold text-[#0F172A]">
                          {(h.from_branch?.name || h.fromBranch?.name || '—')} → {(h.to_branch?.name || h.toBranch?.name || '—')}
                        </p>
                        <p className="text-xs text-[#64748B]">
                          {h.completed_at ? new Date(h.completed_at).toLocaleDateString() : '—'}
                        </p>
                      </div>
                      <span className="text-[10px] font-semibold uppercase text-[#16A34A]">Completed</span>
                    </div>
                  ))}
                </div>
              )}
              <div className="pt-4 mt-4 border-t border-[#E2E8F0]">
                <a
                  href={`${transfersBase}?vehicle_id=${historyVehicle.id}`}
                  className="text-xs font-semibold text-[#2563EB] hover:underline"
                >
                  View all transfers for this vehicle →
                </a>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default VehicleManagement;
