import React, { useState, useEffect } from 'react';
import { Car, Loader2, CheckCircle2, LogIn, AlertCircle, X } from 'lucide-react';
import branchApi from '../../api/branchApi';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const FUEL_OPTIONS = ['empty', 'quarter', 'half', 'three_quarter', 'full'];
const COND_OPTIONS = ['excellent', 'good', 'fair', 'poor'];

export default function BranchRentalsPage() {
  const [rentals, setRentals]       = useState([]);
  const [loading, setLoading]       = useState(true);
  const [filter, setFilter]         = useState('confirmed');
  const [modal, setModal]           = useState(null); // { type: 'checkout'|'checkin', booking }
  const [form, setForm]             = useState({});
  const [saving, setSaving]         = useState(false);
  const [error, setError]           = useState('');

  const load = () => {
    setLoading(true);
    branchApi.getRentals({ status: filter }).then(r => setRentals(r.data?.data || [])).finally(() => setLoading(false));
  };

  useEffect(load, [filter]);

  const openCheckout = (b) => {
    setForm({ start_mileage: b.vehicle?.mileage || '', start_fuel_level: 'full', exterior_condition: 'good', interior_condition: 'good', existing_damage: '', notes: '' });
    setError(''); setModal({ type: 'checkout', booking: b });
  };

  const openCheckin = (b) => {
    setForm({ end_mileage: '', end_fuel_level: 'full', exterior_condition: 'good', interior_condition: 'good', new_damage: '', additional_charges: '0', notes: '' });
    setError(''); setModal({ type: 'checkin', booking: b });
  };

  const submit = async (e) => {
    e.preventDefault(); setSaving(true); setError('');
    try {
      if (modal.type === 'checkout') await branchApi.checkOut(modal.booking.id, form);
      else await branchApi.checkIn(modal.booking.id, form);
      setModal(null); load();
    } catch (err) {
      setError(err.response?.data?.message || 'Operation failed.');
    } finally { setSaving(false); }
  };

  const F = ({ label, children }) => (
    <div>
      <label className="block text-xs font-semibold text-[#334155] mb-1">{label}</label>
      {children}
    </div>
  );

  return (
    <div className="mgmt-page space-y-6">
      <ManagementPageHeader
        eyebrow="Operations"
        title="Rentals & Check-in/Out"
      />

      {/* Filter */}
      <div className="flex gap-2 flex-wrap">
        {[
          { value: 'confirmed', label: 'Ready for Checkout' },
          { value: 'active', label: 'Active Rentals' },
          { value: 'completed', label: 'Completed' },
        ].map(f => (
          <button key={f.value} onClick={() => setFilter(f.value)}
            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ${filter === f.value ? 'bg-[#2563EB] text-white' : 'bg-white text-[#334155] border border-[#E2E8F0] hover:bg-[#F8FAFC]'}`}>
            {f.label}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><Loader2 className="w-8 h-8 animate-spin text-[#2563EB]" /></div>
      ) : rentals.length === 0 ? (
        <ManagementEmptyState
          icon={Car}
          title="No rentals found"
        />
      ) : (
        <div className="space-y-3">
          {rentals.map(b => (
            <ManagementCard key={b.id} className="p-4">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <span className="font-semibold text-[#0F172A] text-sm">{b.booking_reference}</span>
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${getStatusBadgeStyle(b.status)}`}>{formatStatus(b.status)}</span>
                  </div>
                  <p className="text-sm text-[#64748B]">{b.user?.name} · {b.vehicle?.brand} {b.vehicle?.model} ({b.vehicle?.registration_number})</p>
                  <p className="text-xs text-[#64748B] mt-0.5">{b.pickup_date?.split('T')[0]} → {b.return_date?.split('T')[0]} · {formatCurrency(b.total_price)}</p>
                </div>
                <div className="flex gap-2">
                  {b.status === 'confirmed' && b.payment_status === 'paid' && (
                    <ManagementButton variant="primary" onClick={() => openCheckout(b)}>
                      <LogIn className="w-3.5 h-3.5" /> Check Out
                    </ManagementButton>
                  )}
                  {b.status === 'active' && (
                    <ManagementButton variant="success" onClick={() => openCheckin(b)}>
                      <CheckCircle2 className="w-3.5 h-3.5" /> Check In
                    </ManagementButton>
                  )}
                </div>
              </div>
            </ManagementCard>
          ))}
        </div>
      )}

      {modal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white border border-[#E2E8F0] rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">
                {modal.type === 'checkout' ? '🚗 Vehicle Check-Out' : '✅ Vehicle Check-In'}
              </h2>
              <button onClick={() => setModal(null)}><X className="w-5 h-5 text-[#64748B]" /></button>
            </div>
            <form onSubmit={submit} className="p-6 space-y-4">
              {error && <div className="bg-red-50 text-[#DC2626] text-sm p-3 rounded-lg flex gap-2"><AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />{error}</div>}

              <F label={modal.type === 'checkout' ? 'Start Mileage *' : 'End Mileage *'}>
                <input type="number" required min="0"
                  value={modal.type === 'checkout' ? form.start_mileage : form.end_mileage}
                  onChange={e => setForm(p => ({...p, [modal.type === 'checkout' ? 'start_mileage' : 'end_mileage']: e.target.value}))}
                  className="mgmt-input" />
              </F>

              <F label="Fuel Level *">
                <select required value={modal.type === 'checkout' ? form.start_fuel_level : form.end_fuel_level}
                  onChange={e => setForm(p => ({...p, [modal.type === 'checkout' ? 'start_fuel_level' : 'end_fuel_level']: e.target.value}))}
                  className="mgmt-input">
                  {FUEL_OPTIONS.map(o => <option key={o} value={o}>{o.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}</option>)}
                </select>
              </F>

              <div className="grid grid-cols-2 gap-3">
                <F label="Exterior Condition *">
                  <select required value={form.exterior_condition}
                    onChange={e => setForm(p => ({...p, exterior_condition: e.target.value}))}
                    className="mgmt-input">
                    {COND_OPTIONS.map(o => <option key={o} value={o}>{o.charAt(0).toUpperCase() + o.slice(1)}</option>)}
                  </select>
                </F>
                <F label="Interior Condition *">
                  <select required value={form.interior_condition}
                    onChange={e => setForm(p => ({...p, interior_condition: e.target.value}))}
                    className="mgmt-input">
                    {COND_OPTIONS.map(o => <option key={o} value={o}>{o.charAt(0).toUpperCase() + o.slice(1)}</option>)}
                  </select>
                </F>
              </div>

              {modal.type === 'checkout' && (
                <F label="Existing Damage Notes">
                  <textarea rows={2} value={form.existing_damage} onChange={e => setForm(p => ({...p, existing_damage: e.target.value}))}
                    className="mgmt-input resize-none" />
                </F>
              )}

              {modal.type === 'checkin' && (
                <>
                  <F label="New Damage Notes">
                    <textarea rows={2} value={form.new_damage} onChange={e => setForm(p => ({...p, new_damage: e.target.value}))}
                      className="mgmt-input resize-none" />
                  </F>
                  <F label="Additional Charges (ETB)">
                    <input type="number" min="0" step="0.01" value={form.additional_charges}
                      onChange={e => setForm(p => ({...p, additional_charges: e.target.value}))}
                      className="mgmt-input" />
                  </F>
                </>
              )}

              <F label="Notes">
                <textarea rows={2} value={form.notes} onChange={e => setForm(p => ({...p, notes: e.target.value}))}
                  className="mgmt-input resize-none" />
              </F>

              <div className="flex gap-3 pt-2">
                <ManagementButton type="button" variant="secondary" onClick={() => setModal(null)} className="flex-1 py-2 text-sm">
                  Cancel
                </ManagementButton>
                <ManagementButton type="submit" variant="success" disabled={saving} className="flex-1 py-2 text-sm">
                  {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
                  {saving ? 'Processing…' : modal.type === 'checkout' ? 'Confirm Check-Out' : 'Confirm Check-In'}
                </ManagementButton>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
