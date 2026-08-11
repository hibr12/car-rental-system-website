import React, { useState, useEffect } from 'react';
import { Plus, Search, Edit, Trash2, Car, Image as ImageIcon, Star, Check, AlertCircle } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import categoryApi from '../../api/categoryApi';
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

export const VehicleManagement = () => {
  const toast = useToast();
  const [vehicles, setVehicles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });

  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

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

  const fetchVehicles = async () => {
    try {
      setLoading(true);
      const res = await vehicleApi.getAll({ search, page, per_page: 10 });
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
  }, []);

  useEffect(() => {
    fetchVehicles();
  }, [search, page]);

  const handleOpenCreateModal = () => {
    setEditingVehicle(null);
    setFormData({
      ...initialForm,
      category_id: categories[0]?.id || '',
    });
    setModalOpen(true);
  };

  const handleOpenEditModal = (vehicle) => {
    setEditingVehicle(vehicle);
    setFormData({
      category_id: vehicle.category?.id || categories[0]?.id || '',
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
        <div className="relative">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by brand, model, registration..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full bg-white border border-[#CBD5E1] rounded-xl pl-10 pr-4 py-2.5 text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:border-[#2563EB]"
          />
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
                  <th className="py-3.5 px-4 font-semibold">Reg Number</th>
                  <th className="py-3.5 px-4 font-semibold">Price/Day</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
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
                      {v.featured ? (
                        <span className="px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-blue-50 text-[#2563EB] border border-blue-100">
                          Featured
                        </span>
                      ) : (
                        <span className="text-xs text-[#64748B]">Standard</span>
                      )}
                    </td>
                    <td className="py-4 px-4 text-right space-x-2">
                      <ManagementButton variant="secondary" onClick={() => handleOpenEditModal(v)} title="Edit Vehicle">
                        <Edit className="w-4 h-4" />
                      </ManagementButton>
                      <ManagementButton variant="dangerOutline" onClick={() => handleDeleteVehicle(v.id)} title="Delete Vehicle">
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

          <div className="grid grid-cols-3 gap-3">
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
    </div>
  );
};

export default VehicleManagement;
