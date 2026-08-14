import React, { useState, useEffect } from 'react';
import { Users, Plus, Edit2, Trash2, Loader2, X, Check } from 'lucide-react';
import apiClient from '../../api/client';
import adminApi from '../../api/adminApi';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const ROLE_LABEL = {
  admin: 'Company Admin',
  branch_manager: 'Branch Manager',
  fleet_manager: 'Fleet Manager',
  staff: 'Branch Staff',
};

const ROLE_BADGE = {
  admin: 'bg-blue-50 text-[#2563EB]',
  branch_manager: 'bg-blue-50 text-[#2563EB] border border-blue-100',
  fleet_manager: 'bg-[#F8FAFC] text-[#334155] border border-[#E2E8F0]',
  staff: 'bg-[#F8FAFC] text-[#64748B] border border-[#E2E8F0]',
};

const INPUT_CLS = 'w-full px-3 py-2 text-sm border border-[#CBD5E1] rounded-lg bg-white text-[#0F172A] focus:outline-none focus:border-[#2563EB]';
const LABEL_CLS = 'block text-xs font-semibold text-[#334155] mb-1';

const EMPTY_FORM = { name: '', email: '', password: '', phone: '', role: 'staff', branch_id: '' };

export default function StaffManagementPage() {
  const [staff, setStaff]         = useState([]);
  const [branches, setBranches]   = useState([]);
  const [loading, setLoading]     = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editing, setEditing]     = useState(null);
  const [form, setForm]           = useState(EMPTY_FORM);
  const [saving, setSaving]       = useState(false);
  const [error, setError]         = useState('');

  const load = () => {
    setLoading(true);
    apiClient.get('/staff').then(r => setStaff(r.data?.data || [])).finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
    adminApi.getBranches({ status: 'active' }).then(r => setBranches(r.data?.data || []));
  }, []);

  const openCreate = () => { setEditing(null); setForm(EMPTY_FORM); setError(''); setShowModal(true); };
  const openEdit   = (s) => {
    setEditing(s);
    setForm({ name: s.name, email: s.email, password: '', phone: s.phone || '', role: s.role, branch_id: s.branch_id || '' });
    setError(''); setShowModal(true);
  };

  const remove = async (id) => {
    if (!window.confirm('Remove this staff member?')) return;
    try { await apiClient.delete(`/staff/${id}`); load(); } catch {}
  };

  const save = async (e) => {
    e.preventDefault(); setSaving(true); setError('');
    try {
      const payload = { ...form };
      if (editing && !payload.password) delete payload.password;
      if (editing) await apiClient.put(`/staff/${editing.id}`, payload);
      else await apiClient.post('/staff', payload);
      setShowModal(false); load();
    } catch (err) {
      const msgs = err.response?.data?.errors;
      setError(msgs ? Object.values(msgs).flat().join(' ') : err.response?.data?.message || 'Failed to save.');
    } finally { setSaving(false); }
  };

  return (
    <div className="space-y-6">
      <ManagementPageHeader
        eyebrow="Company"
        title="Staff Management"
        description="Manage all staff across branches"
        actions={
          <ManagementButton onClick={openCreate}>
            <Plus className="w-4 h-4" /> Add Staff
          </ManagementButton>
        }
      />

      {loading ? (
        <div className="flex justify-center py-20"><Loader2 className="w-8 h-8 animate-spin text-[#2563EB]" /></div>
      ) : staff.length === 0 ? (
        <ManagementCard>
          <ManagementEmptyState icon={Users} title="No staff members found" />
        </ManagementCard>
      ) : (
        <ManagementCard padding={false} className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-[#334155]">
              <thead>
                <tr className="border-b border-[#E2E8F0] bg-[#F8FAFC]">
                  {['Name', 'Email', 'Phone', 'Role', 'Branch', 'Actions'].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-[#334155] px-4 py-3">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {staff.map(s => (
                  <tr key={s.id} className="hover:bg-[#F8FAFC]">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-full bg-blue-50 text-[#2563EB] flex items-center justify-center font-semibold text-xs border border-blue-100">
                          {s.name?.[0]?.toUpperCase()}
                        </div>
                        <span className="font-medium text-[#0F172A]">{s.name}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-[#64748B]">{s.email}</td>
                    <td className="px-4 py-3 text-[#64748B]">{s.phone || '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`text-xs font-semibold px-2 py-1 rounded-full ${ROLE_BADGE[s.role] || 'bg-[#F8FAFC] text-[#64748B]'}`}>
                        {ROLE_LABEL[s.role] || s.role}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-[#64748B]">{s.branch?.name || '—'}</td>
                    <td className="px-4 py-3">
                      <div className="flex gap-1">
                        <button onClick={() => openEdit(s)} className="p-1.5 rounded-lg text-[#64748B] hover:text-[#2563EB] hover:bg-blue-50 transition-colors">
                          <Edit2 className="w-4 h-4" />
                        </button>
                        <button onClick={() => remove(s.id)} className="p-1.5 rounded-lg text-[#64748B] hover:text-[#DC2626] hover:bg-red-50 transition-colors">
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </ManagementCard>
      )}

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">{editing ? 'Edit Staff' : 'Add Staff Member'}</h2>
              <button onClick={() => setShowModal(false)} className="text-[#64748B] hover:text-[#334155]"><X className="w-5 h-5" /></button>
            </div>
            <form onSubmit={save} className="p-6 space-y-4">
              {error && <div className="bg-red-50 text-[#DC2626] text-sm p-3 rounded-lg border border-red-100">{error}</div>}
              <div>
                <label className={LABEL_CLS}>Full Name *</label>
                <input required value={form.name} onChange={e => setForm(p => ({...p, name: e.target.value}))} className={INPUT_CLS} />
              </div>
              <div>
                <label className={LABEL_CLS}>Email *</label>
                <input type="email" required value={form.email} onChange={e => setForm(p => ({...p, email: e.target.value}))} className={INPUT_CLS} />
              </div>
              <div>
                <label className={LABEL_CLS}>{editing ? 'New Password (leave blank to keep)' : 'Password *'}</label>
                <input type="password" required={!editing} value={form.password} onChange={e => setForm(p => ({...p, password: e.target.value}))} className={INPUT_CLS} />
              </div>
              <div>
                <label className={LABEL_CLS}>Phone</label>
                <input value={form.phone} onChange={e => setForm(p => ({...p, phone: e.target.value}))} className={INPUT_CLS} />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={LABEL_CLS}>Role *</label>
                  <select required value={form.role} onChange={e => setForm(p => ({...p, role: e.target.value}))} className={INPUT_CLS}>
                    <option value="staff">Branch Staff</option>
                    <option value="branch_manager">Branch Manager</option>
                    <option value="fleet_manager">Fleet Manager</option>
                  </select>
                </div>
                <div>
                  <label className={LABEL_CLS}>Branch *</label>
                  <select required value={form.branch_id} onChange={e => setForm(p => ({...p, branch_id: e.target.value}))} className={INPUT_CLS}>
                    <option value="">Select branch</option>
                    {branches.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                  </select>
                </div>
              </div>
              <div className="flex gap-3 pt-2">
                <ManagementButton type="button" variant="secondary" onClick={() => setShowModal(false)} className="flex-1">
                  Cancel
                </ManagementButton>
                <ManagementButton type="submit" disabled={saving} className="flex-1">
                  {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Check className="w-4 h-4" />}
                  {saving ? 'Saving…' : editing ? 'Update' : 'Add Staff'}
                </ManagementButton>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
