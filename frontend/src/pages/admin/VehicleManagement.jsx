import React, { useState, useEffect } from 'react';
import { Plus, Search, Edit, Trash2, Car, Image as ImageIcon, Star, Check, AlertCircle } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import categoryApi from '../../api/categoryApi';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

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
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-theme pb-6">
        <div>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Fleet Vehicle Management</h1>
          <p className="text-sm text-theme-muted">Add, edit, update status, and manage vehicle specs & images.</p>
        </div>
        <button
          onClick={handleOpenCreateModal}
          className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-theme-primary font-bold text-xs shadow-lg shadow-blue-600/20 flex items-center gap-2 self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>Add New Vehicle</span>
        </button>
      </div>

      {/* Search Input Bar */}
      <div className="flex items-center gap-4 bg-theme-card border border-theme p-4 rounded-2xl">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by brand, model, registration..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full bg-theme-secondary border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
          />
        </div>
      </div>

      {/* Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
        {loading ? (
          <div className="py-12 text-center text-theme-muted text-sm">Loading vehicle fleet...</div>
        ) : vehicles.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <Car className="w-12 h-12 text-slate-700 mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Vehicles Found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-secondary/60 text-theme-muted border-b border-theme">
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
              <tbody className="divide-y divide-slate-800/60">
                {vehicles.map((v) => (
                  <tr key={v.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-medium text-theme-primary flex items-center gap-3">
                      <div className="w-12 h-9 rounded-lg overflow-hidden bg-theme-secondary border border-theme shrink-0">
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
                        <p className="text-[11px] text-theme-muted">{v.year} • {v.fuel_type}</p>
                      </div>
                    </td>
                    <td className="py-4 px-4 text-xs font-semibold text-theme-secondary">
                      {v.category?.name || 'Uncategorized'}
                    </td>
                    <td className="py-4 px-4 text-xs font-mono text-theme-muted">
                      {v.registration_number}
                    </td>
                    <td className="py-4 px-4 font-bold text-emerald-400">
                      {formatCurrency(v.rental_price_per_day)}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(v.status)}`}>
                        {formatStatus(v.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      {v.featured ? (
                        <span className="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                          Featured
                        </span>
                      ) : (
                        <span className="text-xs text-slate-600">Standard</span>
                      )}
                    </td>
                    <td className="py-4 px-4 text-right space-x-2">
                      <button
                        onClick={() => handleOpenEditModal(v)}
                        className="p-2 rounded-lg bg-theme-hover hover:bg-theme-hover text-theme-secondary"
                        title="Edit Vehicle"
                      >
                        <Edit className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => handleDeleteVehicle(v.id)}
                        className="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400"
                        title="Delete Vehicle"
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

      {/* Vehicle Form Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingVehicle ? `Edit Vehicle: ${editingVehicle.brand} ${editingVehicle.model}` : 'Add New Fleet Vehicle'}
      >
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Brand *</label>
              <input
                type="text"
                required
                value={formData.brand}
                onChange={(e) => setFormData({ ...formData, brand: e.target.value })}
                placeholder="e.g. BMW, Mercedes, Toyota"
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              />
            </div>
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Model *</label>
              <input
                type="text"
                required
                value={formData.model}
                onChange={(e) => setFormData({ ...formData, model: e.target.value })}
                placeholder="e.g. M5, C-Class, RAV4"
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              />
            </div>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Year *</label>
              <input
                type="number"
                required
                value={formData.year}
                onChange={(e) => setFormData({ ...formData, year: e.target.value })}
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              />
            </div>
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Category *</label>
              <select
                value={formData.category_id}
                onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              >
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Daily Price ($) *</label>
              <input
                type="number"
                step="0.01"
                required
                value={formData.rental_price_per_day}
                onChange={(e) => setFormData({ ...formData, rental_price_per_day: e.target.value })}
                placeholder="100.00"
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Registration Number *</label>
              <input
                type="text"
                required
                value={formData.registration_number}
                onChange={(e) => setFormData({ ...formData, registration_number: e.target.value })}
                placeholder="ABC-1234"
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              />
            </div>
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">VIN Number</label>
              <input
                type="text"
                value={formData.vin_number}
                onChange={(e) => setFormData({ ...formData, vin_number: e.target.value })}
                placeholder="17-digit VIN"
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              />
            </div>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Fuel Type</label>
              <select
                value={formData.fuel_type}
                onChange={(e) => setFormData({ ...formData, fuel_type: e.target.value })}
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              >
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="electric">Electric</option>
                <option value="hybrid">Hybrid</option>
              </select>
            </div>

            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Transmission</label>
              <select
                value={formData.transmission}
                onChange={(e) => setFormData({ ...formData, transmission: e.target.value })}
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
              >
                <option value="automatic">Automatic</option>
                <option value="manual">Manual</option>
              </select>
            </div>

            <div>
              <label className="block text-theme-secondary font-semibold mb-1">Status</label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
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
            <label className="block text-theme-secondary font-semibold mb-1">Description</label>
            <textarea
              rows="3"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Vehicle features, condition, specs..."
              className="w-full bg-theme-secondary border border-theme rounded-xl p-2.5 text-theme-primary"
            />
          </div>

          {/* Featured Checkbox */}
          <div className="flex items-center gap-2 pt-1">
            <input
              type="checkbox"
              id="featured_check"
              checked={formData.featured}
              onChange={(e) => setFormData({ ...formData, featured: e.target.checked })}
              className="w-4 h-4 rounded bg-theme-secondary border-theme text-blue-600"
            />
            <label htmlFor="featured_check" className="text-theme-secondary font-semibold cursor-pointer">
              Mark as Featured Vehicle on Homepage
            </label>
          </div>

          {/* Image URLs */}
          <div className="space-y-2 pt-2 border-t border-theme">
            <div className="flex justify-between items-center">
              <label className="block text-theme-secondary font-semibold">Image URLs</label>
              <button
                type="button"
                onClick={handleAddImageField}
                className="text-[11px] text-blue-400 hover:underline"
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
                  className="flex-1 bg-theme-secondary border border-theme rounded-xl p-2 text-theme-primary"
                />
              </div>
            ))}
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="w-full py-3.5 rounded-2xl bg-blue-600 text-theme-primary font-bold text-sm shadow-lg shadow-blue-600/25"
          >
            {submitting ? 'Saving Vehicle...' : editingVehicle ? 'Update Vehicle' : 'Save New Vehicle'}
          </button>
        </form>
      </Modal>
    </div>
  );
};

export default VehicleManagement;
