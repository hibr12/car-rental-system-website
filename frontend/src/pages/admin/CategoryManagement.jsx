import React, { useState, useEffect } from 'react';
import { Plus, Edit, Trash2, FolderTree } from 'lucide-react';
import categoryApi from '../../api/categoryApi';
import Modal from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';

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
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <h1 className="text-3xl font-extrabold text-white tracking-tight">Category Management</h1>
          <p className="text-sm text-slate-400">Organize vehicle categories (SUVs, Sedans, Luxury, Electric).</p>
        </div>
        <button
          onClick={handleOpenCreateModal}
          className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-lg flex items-center gap-2 self-start sm:self-auto"
        >
          <Plus className="w-4 h-4" />
          <span>Add New Category</span>
        </button>
      </div>

      <div className="bg-theme-card border border-theme rounded-xl p-6 sm:p-8 space-y-6 shadow-xl">
        {loading ? (
          <div className="py-12 text-center text-slate-400 text-sm">Loading categories...</div>
        ) : categories.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <FolderTree className="w-12 h-12 text-slate-700 mx-auto" />
            <p className="text-sm font-semibold text-slate-300">No Categories Found</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {categories.map((c) => (
              <div key={c.id} className="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-3 flex flex-col justify-between">
                <div>
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="text-lg font-bold text-white">{c.name}</h3>
                    <span className="text-[10px] font-mono font-semibold text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full border border-blue-500/20">
                      {c.slug}
                    </span>
                  </div>
                  <p className="text-xs text-slate-400 leading-relaxed">
                    {c.description || 'No description provided.'}
                  </p>
                </div>

                <div className="pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
                  <span className="text-slate-500 font-semibold">{c.vehicles_count ?? 0} Vehicles</span>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => handleOpenEditModal(c)}
                      className="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200"
                    >
                      <Edit className="w-3.5 h-3.5" />
                    </button>
                    <button
                      onClick={() => handleDeleteCategory(c.id)}
                      className="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingCategory ? `Edit Category: ${editingCategory.name}` : 'Create Vehicle Category'}
        maxWidth="max-w-md"
      >
        <form onSubmit={handleSubmit} className="space-y-4 text-xs">
          <div>
            <label className="block text-slate-300 font-semibold mb-1">Category Name *</label>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="e.g. Sports & Performance"
              className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-100"
            />
          </div>

          <div>
            <label className="block text-slate-300 font-semibold mb-1">Description</label>
            <textarea
              rows="3"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Description of vehicles in this category..."
              className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-100"
            />
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg"
          >
            {submitting ? 'Saving...' : editingCategory ? 'Update Category' : 'Create Category'}
          </button>
        </form>
      </Modal>
    </div>
  );
};

export default CategoryManagement;
