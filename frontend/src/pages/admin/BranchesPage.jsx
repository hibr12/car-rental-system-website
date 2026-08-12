import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Building2, Plus, Edit2, ToggleLeft, ToggleRight,
  MapPin, Phone, Mail, Users, Loader2, X, Check, ArrowRightLeft
} from 'lucide-react';
import adminApi from '../../api/adminApi';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const STATUS_BADGE = {
  active:   'bg-green-50 text-[#16A34A] border border-green-100',
  inactive: 'bg-[#F8FAFC] text-[#64748B] border border-[#E2E8F0]',
};

const INPUT_CLS = 'w-full px-3 py-2 text-sm border border-[#CBD5E1] rounded-lg bg-white text-[#0F172A] focus:outline-none focus:border-[#2563EB]';
const LABEL_CLS = 'block text-xs font-semibold text-[#334155] mb-1';

const EMPTY_FORM = {
  name: '', code: '', address: '', city: '',
  phone: '', email: '', opening_time: '', closing_time: '', status: 'active',
  create_manager: true,
  manager_name: '', manager_email: '', manager_password: '',
};

export default function BranchesPage() {
  const [branches, setBranches]   = useState([]);
  const [loading, setLoading]     = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editing, setEditing]     = useState(null);
  const [form, setForm]           = useState(EMPTY_FORM);
  const [saving, setSaving]       = useState(false);
  const [error, setError]         = useState('');

  const load = () => {
    setLoading(true);
    adminApi.getBranches()
      .then(r => setBranches(Array.isArray(r.data) ? r.data : []))
      .catch((err) => {
        console.error('Failed to load branches:', err);
        setBranches([]);
      })
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const openCreate = () => { setEditing(null); setForm(EMPTY_FORM); setError(''); setShowModal(true); };
  const openEdit   = (b) => {
    setEditing(b);
    setForm({
      name: b.name, code: b.code, address: b.address, city: b.city,
      phone: b.phone || '', email: b.email || '',
      opening_time: b.opening_time || '', closing_time: b.closing_time || '',
      status: b.status,
      create_manager: false,
      manager_name: b.manager?.name || '',
      manager_email: b.manager?.email || '',
      manager_password: '',
    });
    setError('');
    setShowModal(true);
  };

  const toggle = async (b) => {
    try {
      if (b.status === 'active') await adminApi.deactivateBranch(b.id);
      else await adminApi.activateBranch(b.id);
      load();
    } catch {}
  };

  const save = async (e) => {
    e.preventDefault();
    setSaving(true); setError('');
    try {
      if (editing) await adminApi.updateBranch(editing.id, form);
      else await adminApi.createBranch(form);
      setShowModal(false);
      load();
    } catch (err) {
      const msgs = err.errors;
      setError(
        msgs
          ? Object.values(msgs).flat().join(' ')
          : err.message || 'Unable to create branch. Please try again.'
      );
    } finally { setSaving(false); }
  };

  return (
    <div className="space-y-6">
      <ManagementPageHeader
        eyebrow="Company"
        title="Branches"
        description="Manage your company's physical branches"
        actions={
          <ManagementButton onClick={openCreate}>
            <Plus className="w-4 h-4" /> Add Branch
          </ManagementButton>
        }
      />

      {loading ? (
        <div className="flex flex-col justify-center items-center py-20 text-[#64748B]">
          <Loader2 className="w-8 h-8 animate-spin text-[#2563EB] mb-2" />
          <p className="text-sm">Loading branches...</p>
        </div>
      ) : branches.length === 0 ? (
        <ManagementCard>
          <ManagementEmptyState icon={Building2} title="No branches yet" />
        </ManagementCard>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          {branches.map(b => (
            <ManagementCard key={b.id} className="hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center border border-blue-100">
                    <Building2 className="w-5 h-5 text-[#2563EB]" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-[#0F172A] text-sm">{b.name}</h3>
                    <span className="text-xs font-bold text-[#64748B] tracking-wide">{b.code}</span>
                  </div>
                </div>
                <span className={`text-xs font-semibold px-2 py-1 rounded-full ${STATUS_BADGE[b.status]}`}>
                  {b.status}
                </span>
              </div>

              <div className="space-y-1.5 text-xs text-[#64748B] mb-4">
                {b.address && <div className="flex items-center gap-1.5"><MapPin className="w-3.5 h-3.5 shrink-0" />{b.address}, {b.city}</div>}
                {b.phone   && <div className="flex items-center gap-1.5"><Phone className="w-3.5 h-3.5 shrink-0" />{b.phone}</div>}
                {b.email   && <div className="flex items-center gap-1.5"><Mail className="w-3.5 h-3.5 shrink-0" />{b.email}</div>}
                {b.manager && <div className="flex items-center gap-1.5"><Users className="w-3.5 h-3.5 shrink-0" />Manager: {b.manager.name}</div>}
              </div>

              <div className="grid grid-cols-3 gap-2 text-center text-[10px] mb-3">
                <div className="bg-[#F8FAFC] rounded-lg py-2 border border-[#E2E8F0]">
                  <p className="font-bold text-[#0F172A] text-sm">{b.vehicles_count ?? 0}</p>
                  <p className="text-[#64748B]">Vehicles</p>
                </div>
                <div className="bg-[#F8FAFC] rounded-lg py-2 border border-[#E2E8F0]">
                  <p className="font-bold text-[#0F172A] text-sm">{b.staff_count ?? 0}</p>
                  <p className="text-[#64748B]">Staff</p>
                </div>
                <div className="bg-[#F8FAFC] rounded-lg py-2 border border-[#E2E8F0]">
                  <p className="font-bold text-[#0F172A] text-sm">{b.bookings_count ?? 0}</p>
                  <p className="text-[#64748B]">Bookings</p>
                </div>
              </div>

              <div className="grid grid-cols-3 gap-2 text-center text-[10px] mb-4">
                <div className="bg-amber-50 rounded-lg py-2 border border-amber-100">
                  <p className="font-bold text-[#F59E0B] text-sm">{b.pending_transfers_count ?? 0}</p>
                  <p className="text-[#64748B]">Pending</p>
                </div>
                <div className="bg-blue-50 rounded-lg py-2 border border-blue-100">
                  <p className="font-bold text-[#2563EB] text-sm">{b.incoming_transfers_count ?? 0}</p>
                  <p className="text-[#64748B]">Incoming</p>
                </div>
                <div className="bg-purple-50 rounded-lg py-2 border border-purple-100">
                  <p className="font-bold text-[#7C3AED] text-sm">{b.outgoing_transfers_count ?? 0}</p>
                  <p className="text-[#64748B]">Outgoing</p>
                </div>
              </div>

              <Link
                to={`/admin/transfers?branch_id=${b.id}`}
                className="mb-3 flex items-center justify-center gap-1.5 w-full text-xs font-semibold text-[#2563EB] hover:bg-blue-50 border border-blue-100 rounded-lg py-2 transition-colors"
              >
                <ArrowRightLeft className="w-3.5 h-3.5" /> View Transfers
              </Link>

              <div className="flex gap-2 pt-3 border-t border-[#E2E8F0]">
                <button onClick={() => openEdit(b)}
                  className="flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-medium text-[#334155] hover:text-[#2563EB] hover:bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">
                  <Edit2 className="w-3.5 h-3.5" /> Edit
                </button>
                <button onClick={() => toggle(b)}
                  className={`flex-1 inline-flex items-center justify-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors ${
                    b.status === 'active'
                      ? 'text-[#DC2626] hover:bg-red-50'
                      : 'text-[#16A34A] hover:bg-green-50'
                  }`}>
                  {b.status === 'active' ? <><ToggleLeft className="w-3.5 h-3.5" /> Deactivate</> : <><ToggleRight className="w-3.5 h-3.5" /> Activate</>}
                </button>
              </div>
            </ManagementCard>
          ))}
        </div>
      )}

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">
                {editing ? 'Edit Branch' : 'New Branch'}
              </h2>
              <button onClick={() => setShowModal(false)} className="text-[#64748B] hover:text-[#334155]">
                <X className="w-5 h-5" />
              </button>
            </div>
            <form onSubmit={save} className="p-6 space-y-4">
              {error && <div className="bg-red-50 text-[#DC2626] text-sm p-3 rounded-lg border border-red-100">{error}</div>}

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={LABEL_CLS}>Branch Name *</label>
                  <input value={form.name} onChange={e => setForm(p => ({...p, name: e.target.value}))} required className={INPUT_CLS} />
                </div>
                <div>
                  <label className={LABEL_CLS}>Branch Code *</label>
                  <input value={form.code} onChange={e => setForm(p => ({...p, code: e.target.value.toUpperCase()}))} required
                    className={`${INPUT_CLS} uppercase`} placeholder="e.g. BOLE" />
                </div>
              </div>

              <div>
                <label className={LABEL_CLS}>Address *</label>
                <input value={form.address} onChange={e => setForm(p => ({...p, address: e.target.value}))} required className={INPUT_CLS} />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={LABEL_CLS}>City *</label>
                  <input value={form.city} onChange={e => setForm(p => ({...p, city: e.target.value}))} required className={INPUT_CLS} />
                </div>
                <div>
                  <label className={LABEL_CLS}>Phone</label>
                  <input value={form.phone} onChange={e => setForm(p => ({...p, phone: e.target.value}))} className={INPUT_CLS} />
                </div>
              </div>

              <div>
                <label className={LABEL_CLS}>Email</label>
                <input type="email" value={form.email} onChange={e => setForm(p => ({...p, email: e.target.value}))} className={INPUT_CLS} />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={LABEL_CLS}>Opening Time</label>
                  <input type="time" value={form.opening_time} onChange={e => setForm(p => ({...p, opening_time: e.target.value}))} className={INPUT_CLS} />
                </div>
                <div>
                  <label className={LABEL_CLS}>Closing Time</label>
                  <input type="time" value={form.closing_time} onChange={e => setForm(p => ({...p, closing_time: e.target.value}))} className={INPUT_CLS} />
                </div>
              </div>

              {editing && (
                <div>
                  <label className={LABEL_CLS}>Status</label>
                  <select value={form.status} onChange={e => setForm(p => ({...p, status: e.target.value}))} className={INPUT_CLS}>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              )}

              {!editing && (
                <div className="space-y-3 rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] p-4">
                  <label className="flex items-center gap-2 text-sm font-medium text-[#334155]">
                    <input
                      type="checkbox"
                      checked={form.create_manager}
                      onChange={(e) => setForm(p => ({ ...p, create_manager: e.target.checked }))}
                    />
                    Create branch manager account
                  </label>
                  {form.create_manager && (
                    <>
                      <p className="text-xs text-[#64748B]">
                        Default login: <strong>{form.code ? `${form.code.toLowerCase()}.manager@apexrentals.com` : '{code}.manager@apexrentals.com'}</strong> / password: <strong>password</strong>
                      </p>
                      <div>
                        <label className={LABEL_CLS}>Manager Name (optional)</label>
                        <input value={form.manager_name} onChange={e => setForm(p => ({...p, manager_name: e.target.value}))} className={INPUT_CLS} placeholder="Auto-generated from branch name" />
                      </div>
                      <div>
                        <label className={LABEL_CLS}>Manager Email (optional)</label>
                        <input type="email" value={form.manager_email} onChange={e => setForm(p => ({...p, manager_email: e.target.value}))} className={INPUT_CLS} placeholder="Auto-generated from branch code" />
                      </div>
                      <div>
                        <label className={LABEL_CLS}>Manager Password (optional)</label>
                        <input type="password" value={form.manager_password} onChange={e => setForm(p => ({...p, manager_password: e.target.value}))} className={INPUT_CLS} placeholder="Defaults to password" />
                      </div>
                    </>
                  )}
                </div>
              )}

              {editing && form.manager_email && (
                <div className="text-xs text-[#64748B] bg-blue-50 border border-blue-100 rounded-lg p-3">
                  Current manager: <strong>{form.manager_name}</strong> ({form.manager_email})
                </div>
              )}

              <div className="flex gap-3 pt-2">
                <ManagementButton type="button" variant="secondary" onClick={() => setShowModal(false)} className="flex-1">
                  Cancel
                </ManagementButton>
                <ManagementButton type="submit" disabled={saving} className="flex-1">
                  {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Check className="w-4 h-4" />}
                  {saving ? 'Saving…' : editing ? 'Update Branch' : 'Create Branch'}
                </ManagementButton>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
