import React, { useState, useEffect } from 'react';
import { Plus, Edit, Trash2, FolderTree } from 'lucide-react';
import categoryApi from '../../api/categoryApi';
import Modal from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

export const CategoryManagement = () => {
  const toast = useToast();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);

  const [modalOpen, setModalOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const fetchCategories = async () => {
    try {
      setLoading(true);
      const res = await categoryApi.getAll();
      setCategories(res.data || []);
    } catch (err) {
      toast.error('Failed to load categories.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const handleOpenCreateModal = () => {
    setEditingCategory(null);
    setName('');
    setDescription('');
    setModalOpen(true);
  };

  const handleOpenEditModal = (cat) => {
    setEditingCategory(cat);
    setName(cat.name || '');
    setDescription(cat.description || '');
    setModalOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      if (editingCategory) {
        await categoryApi.update(editingCategory.id, { name, description });
        toast.success('Category updated successfully!');
      } else {
        await categoryApi.create({ name, description });
        toast.success('Category created successfully!');
      }
      setModalOpen(false);
      fetchCategories();
    } catch (err) {
      toast.error(err.message || 'Failed to save category.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDeleteCategory = async (id) => {
    if (!window.confirm('Are you sure you want to delete this category?')) return;
    try {
      await categoryApi.delete(id);
      toast.success('Category deleted successfully.');
      fetchCategories();
    } catch (err) {
      toast.error(err.message || 'Failed to delete category.');
    }
  };

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="Category Management"
        description="Organize vehicle categories (SUVs, Sedans, Luxury, Electric)."
        actions={
          <ManagementButton onClick={handleOpenCreateModal}>
            <Plus className="w-4 h-4" />
            <span>Add New Category</span>
          </ManagementButton>
        }
      />

      <ManagementCard className="space-y-6">
        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading categories...</div>
        ) : categories.length === 0 ? (
          <ManagementEmptyState icon={FolderTree} title="No Categories Found" />
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {categories.map((c) => (
              <div key={c.id} className="bg-white p-6 rounded-xl border border-[#E2E8F0] space-y-3 flex flex-col justify-between">
                <div>
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="text-lg font-bold text-[#0F172A]">{c.name}</h3>
                    <span className="text-[10px] font-mono font-semibold text-[#2563EB] bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                      {c.slug}
                    </span>
                  </div>
                  <p className="text-xs text-[#64748B] leading-relaxed">
                    {c.description || 'No description provided.'}
                  </p>
                </div>

                <div className="pt-4 border-t border-[#E2E8F0] flex items-center justify-between text-xs">
                  <span className="text-[#64748B] font-semibold">{c.vehicles_count ?? 0} Vehicles</span>
                  <div className="flex items-center gap-2">
                    <ManagementButton variant="secondary" onClick={() => handleOpenEditModal(c)}>
                      <Edit className="w-3.5 h-3.5" />
                    </ManagementButton>
                    <ManagementButton variant="dangerOutline" onClick={() => handleDeleteCategory(c.id)}>
                      <Trash2 className="w-3.5 h-3.5" />
                    </ManagementButton>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </ManagementCard>

      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingCategory ? `Edit Category: ${editingCategory.name}` : 'Create Vehicle Category'}
        maxWidth="max-w-md"
      >
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div>
            <label className="block text-[#334155] font-semibold mb-1">Category Name *</label>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="e.g. Sports & Performance"
              className="w-full bg-white border border-[#CBD5E1] rounded-xl p-3 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
            />
          </div>

          <div>
            <label className="block text-[#334155] font-semibold mb-1">Description</label>
            <textarea
              rows="3"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Description of vehicles in this category..."
              className="w-full bg-white border border-[#CBD5E1] rounded-xl p-3 text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
            />
          </div>

          <ManagementButton type="submit" disabled={submitting} className="w-full py-3.5">
            {submitting ? 'Saving...' : editingCategory ? 'Update Category' : 'Create Category'}
          </ManagementButton>
        </form>
      </Modal>
    </div>
  );
};

export default CategoryManagement;
