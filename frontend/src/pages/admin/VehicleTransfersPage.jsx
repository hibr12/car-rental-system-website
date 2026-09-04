import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  ArrowRightLeft, Plus, Check, X, Loader2, TruckIcon, CheckCircle2, XCircle, Eye
} from 'lucide-react';
import transferApi from '../../api/transferApi';
import adminApi from '../../api/adminApi';
import vehicleApi from '../../api/vehicleApi';
import useAuthStore from '../../store/authStore';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const STATUS_STYLES = {
  pending:  'bg-amber-50 text-[#F59E0B] border border-amber-100',
  requested: 'bg-amber-50 text-[#F59E0B] border border-amber-100',
  approved: 'bg-blue-50 text-[#2563EB] border border-blue-100',
  ready_for_release: 'bg-amber-50 text-[#F59E0B] border border-amber-100',
  in_transit: 'bg-blue-50 text-[#2563EB] border border-blue-100',
  received: 'bg-green-50 text-[#16A34A] border border-green-100',
  received_pending_inspection: 'bg-red-50 text-[#DC2626] border border-red-100',
  completed:  'bg-green-50 text-[#16A34A] border border-green-100',
  rejected:   'bg-red-50 text-[#DC2626] border border-red-100',
  cancelled:  'bg-slate-50 text-[#64748B] border border-slate-200',
  failed: 'bg-red-50 text-[#DC2626] border border-red-100',
};

const INPUT_CLS = 'w-full px-3 py-2 text-sm border border-[#CBD5E1] rounded-lg bg-white text-[#0F172A] focus:outline-none focus:border-[#2563EB]';
const LABEL_CLS = 'block text-xs font-semibold text-[#334155] mb-1';

export default function VehicleTransfersPage() {
  const { user } = useAuthStore();
  const isAdmin = user?.role === 'admin' || user?.role === 'super_admin';
  const isFleetManager = user?.role === 'fleet_manager';
  const isBranchManager = user?.role === 'branch_manager';
  const branchId = user?.branch_id;
  const [searchParams, setSearchParams] = useSearchParams();
  const [transfers, setTransfers]   = useState([]);
  const [stats, setStats]           = useState({ total:0, pending:0, approved:0, in_transit:0, completed:0, rejected:0, cancelled:0 });
  const [branches, setBranches]     = useState([]);
  const [vehicles, setVehicles]     = useState([]);
  const [loading, setLoading]       = useState(true);
  const [showModal, setShowModal]   = useState(false);
  const [saving, setSaving]         = useState(false);
  const [error, setError]           = useState('');
  const [filterStatus, setFilterStatus] = useState(searchParams.get('status') || '');
  const [filterFromBranch, setFilterFromBranch] = useState(searchParams.get('from_branch_id') || '');
  const [filterToBranch, setFilterToBranch] = useState(searchParams.get('to_branch_id') || '');
  const [filterBranch, setFilterBranch] = useState(searchParams.get('branch_id') || '');
  const [filterVehicle, setFilterVehicle] = useState(searchParams.get('vehicle_id') || '');
  const [search, setSearch] = useState(searchParams.get('search') || '');
  const [form, setForm] = useState({
    vehicle_id: searchParams.get('vehicle_id') || '',
    to_branch_id: '',
    transfer_date: '',
    reason: '',
    notes: '',
  });

  const [releaseModal, setReleaseModal] = useState(null);
  const [receiveModal, setReceiveModal] = useState(null);
  const [releaseForm, setReleaseForm] = useState({ source_odometer: '', source_fuel_level: '', source_condition: 'good', release_notes: '' });
  const [receiveForm, setReceiveForm] = useState({ destination_odometer: '', destination_fuel_level: '', destination_condition: 'good', receiving_notes: '', has_damage: false, damage_report: '' });
  const [detailOpen, setDetailOpen] = useState(false);
  const [detailTransfer, setDetailTransfer] = useState(null);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [history, setHistory] = useState([]);

  const buildParams = useCallback(() => {
    const params = {};
    if (filterStatus) params.status = filterStatus;
    if (filterFromBranch) params.from_branch_id = filterFromBranch;
    if (filterToBranch) params.to_branch_id = filterToBranch;
    if (filterBranch) params.branch_id = filterBranch;
    if (filterVehicle) params.vehicle_id = filterVehicle;
    if (search.trim()) params.search = search.trim();
    return params;
  }, [filterStatus, filterFromBranch, filterToBranch, filterBranch, filterVehicle, search]);

  const load = useCallback(() => {
    setLoading(true);
    transferApi.getAll(buildParams())
      .then(r => {
        setTransfers(r.data || []);
        const st = r.meta?.stats;
        if (st) setStats(st);
      })
      .finally(() => setLoading(false));
  }, [buildParams]);

  useEffect(load, [load]);

  useEffect(() => {
    setFilterStatus(searchParams.get('status') || '');
    setFilterFromBranch(searchParams.get('from_branch_id') || '');
    setFilterToBranch(searchParams.get('to_branch_id') || '');
    setFilterBranch(searchParams.get('branch_id') || '');
    setFilterVehicle(searchParams.get('vehicle_id') || '');
    setSearch(searchParams.get('search') || '');
  }, [searchParams]);

  useEffect(() => {
    if (searchParams.get('open') === 'create') {
      setShowModal(true);
    }
  }, [searchParams]);

  useEffect(() => {
    adminApi.getBranches({ status: 'active' }).then(r => setBranches(r.data || []));
    vehicleApi.getAll({ status: 'available', per_page: 100 }).then(r => setVehicles(r.data || []));
  }, []);

  const applyFilters = (e) => {
    e?.preventDefault?.();
    const params = {};
    if (filterStatus) params.status = filterStatus;
    if (filterFromBranch) params.from_branch_id = filterFromBranch;
    if (filterToBranch) params.to_branch_id = filterToBranch;
    if (filterBranch) params.branch_id = filterBranch;
    if (filterVehicle) params.vehicle_id = filterVehicle;
    if (search.trim()) params.search = search.trim();
    setSearchParams(params);
    load();
  };

  const resetFilters = () => {
    setFilterStatus('');
    setFilterFromBranch('');
    setFilterToBranch('');
    setFilterBranch('');
    setFilterVehicle('');
    setSearch('');
    setSearchParams({});
  };

  const handleAction = async (fn) => { try { await fn(); load(); } catch {} };

  const openDetail = async (id) => {
    setDetailOpen(true);
    setDetailTransfer(null);
    try {
      const r = await transferApi.getOne(id);
      setDetailTransfer(r.data || null);
    } catch {}
  };

  const viewHistory = async (id) => {
    setHistoryLoading(true);
    try {
      const r = await transferApi.getHistory(id);
      setHistory(r.data?.data || []);
    } catch {}
    finally {
      setHistoryLoading(false);
    }
  };

  const save = async (e) => {
    e.preventDefault(); setSaving(true); setError('');
    try {
      await transferApi.create(form);
      setShowModal(false); load();
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create transfer request.');
    } finally { setSaving(false); }
  };

  return (
    <div className="space-y-6">
      <ManagementPageHeader
        eyebrow="Fleet"
        title="Vehicle Transfers"
        description="Manage vehicle movement between Apex Rentals branches."
        actions={
          (isBranchManager || isAdmin) ? (
            <ManagementButton onClick={() => { setForm({ vehicle_id: '', to_branch_id: '', transfer_date: '', reason: '', notes: '' }); setError(''); setShowModal(true); }}>
              <Plus className="w-4 h-4" /> Request Transfer
            </ManagementButton>
          ) : null
        }
      />

      {/* Top statistics */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        {[
          ['Total Transfers', stats.total],
          ['Pending', stats.pending],
          ['Approved', stats.approved],
          ['In Transit', stats.in_transit],
          ['Completed', stats.completed],
          ['Rejected', stats.rejected],
          ['Cancelled', stats.cancelled],
        ].map(([label, value]) => (
          <div key={label} className="bg-white border border-[#E2E8F0] rounded-xl p-4">
            <div className="text-[11px] font-semibold text-[#64748B]">{label}</div>
            <div className="text-2xl font-bold text-[#0F172A]">{value ?? 0}</div>
          </div>
        ))}
      </div>

      <ManagementCard className="space-y-4">
        <form onSubmit={applyFilters} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
          <div>
            <label className={LABEL_CLS}>Search</label>
            <input
              type="text"
              value={search}
              onChange={e => setSearch(e.target.value)}
              placeholder="Vehicle, plate, branch, ID..."
              className={INPUT_CLS}
            />
          </div>
          <div>
            <label className={LABEL_CLS}>Source Branch</label>
            <select value={filterFromBranch} onChange={e => setFilterFromBranch(e.target.value)} className={INPUT_CLS}>
              <option value="">All sources</option>
              {branches.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
            </select>
          </div>
          <div>
            <label className={LABEL_CLS}>Destination Branch</label>
            <select value={filterToBranch} onChange={e => setFilterToBranch(e.target.value)} className={INPUT_CLS}>
              <option value="">All destinations</option>
              {branches.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
            </select>
          </div>
          <div>
            <label className={LABEL_CLS}>Vehicle</label>
            <select value={filterVehicle} onChange={e => setFilterVehicle(e.target.value)} className={INPUT_CLS}>
              <option value="">All vehicles</option>
              {vehicles.map(v => <option key={v.id} value={v.id}>{v.brand} {v.model} — {v.registration_number}</option>)}
            </select>
          </div>
          <div className="md:col-span-2 lg:col-span-4 flex gap-2">
            <ManagementButton type="submit">Search</ManagementButton>
            <ManagementButton type="button" variant="secondary" onClick={resetFilters}>Reset</ManagementButton>
          </div>
        </form>
      </ManagementCard>

      <div className="flex gap-2 flex-wrap">
        {['', 'pending', 'ready_for_release', 'in_transit', 'received', 'completed', 'rejected', 'cancelled', 'failed'].map(s => (
          <button key={s} onClick={() => {
            setFilterStatus(s);
            setSearchParams(prev => {
              const p = new URLSearchParams(prev);
              if (s) p.set('status', s);
              else p.delete('status');
              return p;
            });
          }}
            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors ${filterStatus === s ? 'bg-[#2563EB] text-white' : 'bg-white text-[#334155] border border-[#E2E8F0] hover:bg-[#F8FAFC]'}`}>
            {s === '' ? 'All' : s.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><Loader2 className="w-8 h-8 animate-spin text-[#2563EB]" /></div>
      ) : transfers.length === 0 ? (
        <ManagementCard>
          <ManagementEmptyState icon={ArrowRightLeft} title="No transfers found" />
        </ManagementCard>
      ) : (
        <ManagementCard padding={false} className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-[#334155]">
              <thead>
                <tr className="border-b border-[#E2E8F0] bg-[#F8FAFC]">
                  {['Vehicle', 'From', 'To', 'Date', 'Requested By', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-[#334155] px-4 py-3">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {transfers.map(t => (
                  <tr key={t.id} className="hover:bg-[#F8FAFC]">
                    <td className="px-4 py-3 font-medium text-[#0F172A]">
                      {t.vehicle ? `${t.vehicle.brand} ${t.vehicle.model}` : '—'}
                      <div className="text-xs text-[#64748B]">{t.vehicle?.registration_number}</div>
                    </td>
                    <td className="px-4 py-3 text-[#334155]">{t.fromBranch?.name || t.from_branch?.name || '—'}</td>
                    <td className="px-4 py-3 text-[#334155]">{t.toBranch?.name || t.to_branch?.name || '—'}</td>
                    <td className="px-4 py-3 text-[#64748B]">{t.transfer_date || '—'}</td>
                    <td className="px-4 py-3 text-[#64748B]">{t.requester?.name || '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`text-xs font-semibold px-2 py-1 rounded-full ${STATUS_STYLES[t.status] || 'bg-[#F8FAFC] text-[#64748B]'}`}>
                        {t.status.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex gap-1 flex-wrap">
                        {isAdmin && ['pending', 'requested', 'approved', 'in_transit'].includes(t.status) && (
                          <button
                            onClick={() => handleAction(async () => {
                              if (!window.confirm('Execute full transfer now? The vehicle will be moved to the destination branch immediately.')) return;
                              await transferApi.executeNow(t.id);
                            })}
                            title="Transfer Now (Admin)"
                            className="p-1.5 rounded-lg text-white bg-[#2563EB] hover:bg-[#1D4ED8] transition-colors"
                          >
                            <ArrowRightLeft className="w-4 h-4" />
                          </button>
                        )}
                        {(t.status === 'pending' || t.status === 'requested') && (isFleetManager || isAdmin) && (
                          <>
                            <button
                              onClick={() => handleAction(async () => {
                                if (!window.confirm('Approve Vehicle Transfer?')) return;
                                await transferApi.approve(t.id);
                              })}
                              title="Approve" className="p-1.5 rounded-lg text-[#16A34A] hover:bg-green-50 transition-colors">
                              <CheckCircle2 className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => handleAction(async () => {
                                const reason = window.prompt('Rejection reason (required):');
                                if (!reason) return;
                                if (!window.confirm('Reject Vehicle Transfer?')) return;
                                await transferApi.reject(t.id, reason);
                              })}
                              title="Reject" className="p-1.5 rounded-lg text-[#DC2626] hover:bg-red-50 transition-colors">
                              <XCircle className="w-4 h-4" />
                            </button>
                          </>
                        )}
                        {(t.status === 'pending' || t.status === 'requested') && (isBranchManager || isAdmin) && String(t.from_branch_id) === String(branchId) && (
                            <button
                              onClick={() => handleAction(async () => {
                                const reason = window.prompt('Cancellation reason (optional):') || '';
                                if (!window.confirm('Cancel this transfer?')) return;
                                await transferApi.cancel(t.id, reason);
                              })}
                              title="Cancel" className="p-1.5 rounded-lg text-[#64748B] hover:bg-[#F8FAFC] transition-colors">
                              <X className="w-4 h-4" />
                            </button>
                        )}
                        {(t.status === 'ready_for_release' || t.status === 'approved') && (isBranchManager || isAdmin) && String(t.from_branch_id) === String(branchId) && (
                          <button onClick={() => {
                            setReleaseModal(t);
                            setReleaseForm({ source_odometer: '', source_fuel_level: '', source_condition: 'good', release_notes: '' });
                          }}
                            title="Release Vehicle" className="p-1.5 rounded-lg text-[#2563EB] hover:bg-blue-50 transition-colors">
                            <TruckIcon className="w-4 h-4" />
                          </button>
                        )}
                        {t.status === 'in_transit' && (isBranchManager || isAdmin) && String(t.to_branch_id) === String(branchId) && (
                          <button onClick={() => {
                            setReceiveModal(t);
                            setReceiveForm({ destination_odometer: '', destination_fuel_level: '', destination_condition: 'good', receiving_notes: '', has_damage: false, damage_report: '' });
                          }}
                            title="Receive Vehicle" className="p-1.5 rounded-lg text-[#2563EB] hover:bg-blue-50 transition-colors">
                            <Check className="w-4 h-4" />
                          </button>
                        )}
                        {t.status === 'received_pending_inspection' && (isFleetManager || isAdmin) && (
                          <button onClick={() => handleAction(async () => {
                            if (!window.confirm('Complete transfer after damage review?')) return;
                            await transferApi.complete(t.id);
                          })}
                            title="Complete Transfer" className="p-1.5 rounded-lg text-[#16A34A] hover:bg-green-50 transition-colors">
                            <CheckCircle2 className="w-4 h-4" />
                          </button>
                        )}
                        <button
                          onClick={() => openDetail(t.id)}
                          title="View"
                          className="p-1.5 rounded-lg text-[#334155] hover:bg-[#F8FAFC] transition-colors">
                          <Eye className="w-4 h-4" />
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
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-md">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">Request Transfer</h2>
              <button onClick={() => setShowModal(false)} className="text-[#64748B] hover:text-[#334155]"><X className="w-5 h-5" /></button>
            </div>
            <form onSubmit={save} className="p-6 space-y-4">
              {error && <div className="bg-red-50 text-[#DC2626] text-sm p-3 rounded-lg border border-red-100">{error}</div>}
              <div>
                <label className={LABEL_CLS}>Vehicle *</label>
                <select required value={form.vehicle_id} onChange={e => setForm(p => ({...p, vehicle_id: e.target.value}))} className={INPUT_CLS}>
                  <option value="">Select vehicle</option>
                  {vehicles.map(v => <option key={v.id} value={v.id}>{v.brand} {v.model} — {v.registration_number}</option>)}
                </select>
              </div>
              {form.vehicle_id && (() => {
                const v = vehicles.find(x => String(x.id) === String(form.vehicle_id));
                return v?.branch ? (
                  <div>
                    <label className={LABEL_CLS}>Current Branch</label>
                    <input readOnly value={v.branch.name} className={`${INPUT_CLS} bg-[#F8FAFC]`} />
                  </div>
                ) : null;
              })()}
              <div>
                <label className={LABEL_CLS}>Destination Branch *</label>
                <select required value={form.to_branch_id} onChange={e => setForm(p => ({...p, to_branch_id: e.target.value}))} className={INPUT_CLS}>
                  <option value="">Select branch</option>
                  {branches.filter(b => {
                    const v = vehicles.find(x => String(x.id) === String(form.vehicle_id));
                    return !v?.branch_id || String(b.id) !== String(v.branch_id);
                  }).map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                </select>
              </div>
              <div>
                <label className={LABEL_CLS}>Transfer Date *</label>
                <input type="date" required value={form.transfer_date} onChange={e => setForm(p => ({...p, transfer_date: e.target.value}))}
                  min={new Date().toISOString().split('T')[0]} className={INPUT_CLS} />
              </div>
              <div>
                <label className={LABEL_CLS}>Reason</label>
                <textarea rows={2} value={form.reason} onChange={e => setForm(p => ({...p, reason: e.target.value}))}
                  className={`${INPUT_CLS} resize-none`} />
              </div>
              <div>
                <label className={LABEL_CLS}>Notes</label>
                <textarea rows={2} value={form.notes} onChange={e => setForm(p => ({...p, notes: e.target.value}))}
                  className={`${INPUT_CLS} resize-none`} />
              </div>
              <div className="flex gap-3 pt-2">
                <ManagementButton type="button" variant="secondary" onClick={() => setShowModal(false)} className="flex-1">
                  Cancel
                </ManagementButton>
                <ManagementButton type="submit" disabled={saving} className="flex-1">
                  {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
                  {saving ? 'Submitting…' : 'Submit Request'}
                </ManagementButton>
              </div>
            </form>
          </div>
        </div>
      )}

      {detailOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">Transfer Detail</h2>
              <button onClick={() => setDetailOpen(false)} className="text-[#64748B] hover:text-[#334155]">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="p-6 space-y-5">
              {!detailTransfer ? (
                <div className="flex justify-center py-12 text-[#64748B] text-sm">Loading…</div>
              ) : (
                <>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <div className="text-xs font-semibold text-[#64748B]">Transfer ID</div>
                      <div className="text-sm font-bold text-[#0F172A]">TR-{String(detailTransfer.id).padStart(6,'0')}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold text-[#64748B]">Status</div>
                      <span className={`text-xs font-semibold px-2 py-1 rounded-full ${STATUS_STYLES[detailTransfer.status] || 'bg-[#F8FAFC] text-[#64748B]'}`}>
                        {detailTransfer.status.replace('_', ' ')}
                      </span>
                    </div>
                  </div>

                  <div className="border-t border-[#E2E8F0] pt-4">
                    <div className="text-xs font-semibold text-[#64748B] mb-2">Vehicle</div>
                    <div className="text-sm font-bold text-[#0F172A]">{detailTransfer.vehicle?.brand} {detailTransfer.vehicle?.model}</div>
                    <div className="text-xs text-[#64748B]">{detailTransfer.vehicle?.registration_number}</div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <div className="text-xs font-semibold text-[#64748B] mb-1">From</div>
                      <div className="text-sm font-semibold text-[#0F172A]">{detailTransfer.from_branch?.name || detailTransfer.fromBranch?.name || '—'}</div>
                      <div className="text-xs text-[#64748B]">Requested: {detailTransfer.requested_at ? new Date(detailTransfer.requested_at).toLocaleString() : '—'}</div>
                    </div>
                    <div>
                      <div className="text-xs font-semibold text-[#64748B] mb-1">To</div>
                      <div className="text-sm font-semibold text-[#0F172A]">{detailTransfer.to_branch?.name || detailTransfer.toBranch?.name || '—'}</div>
                      <div className="text-xs text-[#64748B]">Transfer Date: {detailTransfer.transfer_date ? new Date(detailTransfer.transfer_date).toLocaleDateString() : '—'}</div>
                    </div>
                  </div>

                  <div>
                    <div className="text-xs font-semibold text-[#64748B] mb-1">Reason</div>
                    <div className="text-sm text-[#0F172A]">{detailTransfer.reason || '—'}</div>
                    {detailTransfer.notes ? (
                      <div className="text-xs text-[#64748B] mt-2">
                        Notes: {detailTransfer.notes}
                      </div>
                    ) : null}
                  </div>

                  <div className="border-t border-[#E2E8F0] pt-4">
                    <div className="text-xs font-semibold text-[#64748B] mb-3">Workflow Timeline</div>
                    <div className="space-y-3 text-sm">
                      {[
                        ['requested', 'REQUESTED', detailTransfer.requested_at, detailTransfer.requester?.name],
                        ['approved', 'APPROVED', detailTransfer.approved_at, detailTransfer.approver?.name],
                        ['released', 'RELEASED', detailTransfer.released_at, detailTransfer.releasedByUser?.name],
                        ['in_transit', 'IN TRANSIT', detailTransfer.in_transit_at, detailTransfer.releasedByUser?.name],
                        ['received', 'RECEIVED', detailTransfer.received_at, detailTransfer.receivedByUser?.name],
                        ['completed', 'COMPLETED', detailTransfer.completed_at, detailTransfer.completedByUser?.name],
                      ].map(([key, label, ts, actor]) => {
                        const done = Boolean(ts);
                        return (
                          <div key={label} className="flex items-start gap-3">
                            <div className={`mt-0.5 text-xs font-bold ${done ? 'text-[#16A34A]' : 'text-[#CBD5E1]'}`}>{done ? '✓' : '•'}</div>
                            <div>
                              <div className="font-semibold text-[#0F172A]">{label}</div>
                              <div className="text-xs text-[#64748B]">{ts ? new Date(ts).toLocaleString() : '—'}{actor ? ` • ${actor}` : ''}</div>
                            </div>
                          </div>
                        );
                      })}

                      {detailTransfer.status === 'rejected' || detailTransfer.status === 'cancelled' ? (
                        <div className="flex items-start gap-3">
                          <div className="mt-0.5 text-xs font-bold text-[#DC2626]">✕</div>
                          <div>
                            <div className="font-semibold text-[#0F172A]">{detailTransfer.status.toUpperCase()}</div>
                            <div className="text-xs text-[#64748B]">
                              {detailTransfer.status === 'rejected'
                                ? (detailTransfer.rejected_at ? new Date(detailTransfer.rejected_at).toLocaleString() : '—')
                                : (detailTransfer.cancelled_at ? new Date(detailTransfer.cancelled_at).toLocaleString() : '—')}
                            </div>
                            <div className="text-xs text-[#64748B]">
                              Reason: {detailTransfer.status === 'rejected' ? (detailTransfer.rejection_reason || '—') : (detailTransfer.cancellation_reason || '—')}
                            </div>
                          </div>
                        </div>
                      ) : null}
                    </div>
                  </div>

                  <div className="flex gap-2 justify-end pt-4 border-t border-[#E2E8F0]">
                    <ManagementButton
                      variant="secondary"
                      onClick={() => {
                        // Simple: load history inline if needed.
                        viewHistory(detailTransfer.id);
                      }}
                      disabled={historyLoading}
                    >
                      View History
                    </ManagementButton>
                    <ManagementButton onClick={() => setDetailOpen(false)}>
                      Close
                    </ManagementButton>
                  </div>

                  {historyLoading ? (
                    <div className="text-xs text-[#64748B] py-4 text-center">Loading history…</div>
                  ) : history.length > 0 ? (
                    <div className="border border-[#E2E8F0] rounded-xl p-4">
                      <div className="text-xs font-semibold text-[#64748B] mb-2">Transfer History</div>
                      <div className="space-y-2">
                        {history.map(h => (
                          <div key={h.id} className="flex justify-between gap-4 text-sm">
                            <div className="text-[#0F172A] font-semibold">
                              {h.from_branch?.name || h.fromBranch?.name || '—'} → {h.to_branch?.name || h.toBranch?.name || '—'}
                            </div>
                            <div className="text-[#64748B] text-xs">
                              {h.completed_at ? new Date(h.completed_at).toLocaleDateString() : '—'}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  ) : null}
                </>
              )}
            </div>
          </div>
        </div>
      )}

      {releaseModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-md">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">Release Vehicle</h2>
              <button onClick={() => setReleaseModal(null)} className="text-[#64748B] hover:text-[#334155]"><X className="w-5 h-5" /></button>
            </div>
            <form onSubmit={async (e) => {
              e.preventDefault();
              setSaving(true);
              try {
                await transferApi.markInTransit(releaseModal.id, {
                  ...releaseForm,
                  source_odometer: releaseForm.source_odometer ? Number(releaseForm.source_odometer) : undefined,
                  source_fuel_level: releaseForm.source_fuel_level ? Number(releaseForm.source_fuel_level) : undefined,
                });
                setReleaseModal(null);
                load();
              } finally { setSaving(false); }
            }} className="p-6 space-y-4">
              <div><label className={LABEL_CLS}>Mileage (km)</label><input type="number" min="0" value={releaseForm.source_odometer} onChange={e => setReleaseForm(p => ({...p, source_odometer: e.target.value}))} className={INPUT_CLS} /></div>
              <div><label className={LABEL_CLS}>Fuel Level (%)</label><input type="number" min="0" max="100" value={releaseForm.source_fuel_level} onChange={e => setReleaseForm(p => ({...p, source_fuel_level: e.target.value}))} className={INPUT_CLS} /></div>
              <div><label className={LABEL_CLS}>Condition</label><input value={releaseForm.source_condition} onChange={e => setReleaseForm(p => ({...p, source_condition: e.target.value}))} className={INPUT_CLS} /></div>
              <div><label className={LABEL_CLS}>Notes</label><textarea rows={2} value={releaseForm.release_notes} onChange={e => setReleaseForm(p => ({...p, release_notes: e.target.value}))} className={`${INPUT_CLS} resize-none`} /></div>
              <div className="flex gap-3 pt-2">
                <ManagementButton type="button" variant="secondary" onClick={() => setReleaseModal(null)} className="flex-1">Cancel</ManagementButton>
                <ManagementButton type="submit" disabled={saving} className="flex-1">{saving ? 'Releasing…' : 'Release Vehicle'}</ManagementButton>
              </div>
            </form>
          </div>
        </div>
      )}

      {receiveModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
          <div className="bg-white rounded-xl border border-[#E2E8F0] shadow-lg w-full max-w-md">
            <div className="flex items-center justify-between p-6 border-b border-[#E2E8F0]">
              <h2 className="text-lg font-bold text-[#0F172A]">Receive Vehicle</h2>
              <button onClick={() => setReceiveModal(null)} className="text-[#64748B] hover:text-[#334155]"><X className="w-5 h-5" /></button>
            </div>
            <form onSubmit={async (e) => {
              e.preventDefault();
              setSaving(true);
              try {
                await transferApi.receive(receiveModal.id, {
                  ...receiveForm,
                  destination_odometer: receiveForm.destination_odometer ? Number(receiveForm.destination_odometer) : undefined,
                  destination_fuel_level: receiveForm.destination_fuel_level ? Number(receiveForm.destination_fuel_level) : undefined,
                });
                setReceiveModal(null);
                load();
              } finally { setSaving(false); }
            }} className="p-6 space-y-4">
              <div><label className={LABEL_CLS}>Mileage (km)</label><input type="number" min="0" value={receiveForm.destination_odometer} onChange={e => setReceiveForm(p => ({...p, destination_odometer: e.target.value}))} className={INPUT_CLS} /></div>
              <div><label className={LABEL_CLS}>Fuel Level (%)</label><input type="number" min="0" max="100" value={receiveForm.destination_fuel_level} onChange={e => setReceiveForm(p => ({...p, destination_fuel_level: e.target.value}))} className={INPUT_CLS} /></div>
              <div><label className={LABEL_CLS}>Condition</label><input value={receiveForm.destination_condition} onChange={e => setReceiveForm(p => ({...p, destination_condition: e.target.value}))} className={INPUT_CLS} /></div>
              <label className="flex items-center gap-2 text-sm text-[#334155]"><input type="checkbox" checked={receiveForm.has_damage} onChange={e => setReceiveForm(p => ({...p, has_damage: e.target.checked}))} /> Damage found during inspection</label>
              {receiveForm.has_damage && (
                <div><label className={LABEL_CLS}>Damage Report</label><textarea required rows={3} value={receiveForm.damage_report} onChange={e => setReceiveForm(p => ({...p, damage_report: e.target.value}))} className={`${INPUT_CLS} resize-none`} /></div>
              )}
              <div><label className={LABEL_CLS}>Notes</label><textarea rows={2} value={receiveForm.receiving_notes} onChange={e => setReceiveForm(p => ({...p, receiving_notes: e.target.value}))} className={`${INPUT_CLS} resize-none`} /></div>
              <div className="flex gap-3 pt-2">
                <ManagementButton type="button" variant="secondary" onClick={() => setReceiveModal(null)} className="flex-1">Cancel</ManagementButton>
                <ManagementButton type="submit" disabled={saving} className="flex-1">{saving ? 'Confirming…' : 'Confirm Receipt'}</ManagementButton>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
