import React, { useState, useEffect, useCallback } from 'react';
import {
  ShieldCheck, ShieldX, Clock, AlertTriangle, Eye, CheckCircle2,
  Search, Filter, ChevronDown, Loader2, X, FileText,
} from 'lucide-react';
import { licenseApi } from '../../api/licenseApi';
import { useToast } from '../../components/common/Toast';
import LicenseDocumentButton from '../../components/shared/LicenseDocumentButton';

// ─── Helpers ──────────────────────────────────────────────────────────────────

const STATUS_CONFIG = {
  pending_review: { icon: Clock, color: 'text-amber-400', bg: 'bg-amber-500/10 border-amber-500/20', badge: 'bg-amber-500/15 text-amber-400', label: 'Pending' },
  verified:       { icon: ShieldCheck, color: 'text-emerald-400', bg: 'bg-emerald-500/10 border-emerald-500/20', badge: 'bg-emerald-500/15 text-emerald-400', label: 'Verified' },
  rejected:       { icon: ShieldX, color: 'text-red-400', bg: 'bg-red-500/10 border-red-500/20', badge: 'bg-red-500/15 text-red-400', label: 'Rejected' },
  expired:        { icon: AlertTriangle, color: 'text-orange-400', bg: 'bg-orange-500/10 border-orange-500/20', badge: 'bg-orange-500/15 text-orange-400', label: 'Expired' },
};

const CATEGORIES = {
  automobile: 'Automobile',
  motorcycle: 'Motorcycle',
  commercial: 'Commercial',
  minibus: 'Minibus',
  heavy: 'Heavy Vehicle',
};

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

// ─── Rejection modal ──────────────────────────────────────────────────────────

const REJECTION_PRESETS = [
  'Image unclear — please resubmit a clear photo.',
  'Missing back side of the license.',
  'License appears to be expired.',
  'Information on the license does not match.',
  'Invalid or unsupported document type.',
  'Incorrect license category for this request.',
];

function RejectModal({ license, onClose, onConfirm }) {
  const [reason, setReason] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleConfirm = async () => {
    if (!reason.trim() || reason.trim().length < 5) return;
    setSubmitting(true);
    await onConfirm(reason.trim());
    setSubmitting(false);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-labelledby="reject-title">
      <div className="bg-theme-card border border-theme rounded-2xl w-full max-w-lg shadow-2xl">
        <div className="flex items-center justify-between p-5 border-b border-theme">
          <h2 id="reject-title" className="font-bold text-theme-primary text-lg">Reject License</h2>
          <button onClick={onClose} className="p-1.5 hover:bg-white/5 rounded-lg" aria-label="Close"><X className="w-5 h-5" /></button>
        </div>
        <div className="p-5 space-y-4">
          <p className="text-sm text-theme-muted">
            Customer: <span className="text-theme-primary font-semibold">{license?.customer?.name}</span>
          </p>
          <div>
            <p className="text-xs font-semibold text-theme-secondary mb-2">Quick select reason:</p>
            <div className="flex flex-col gap-1.5">
              {REJECTION_PRESETS.map((preset) => (
                <button
                  key={preset}
                  type="button"
                  onClick={() => setReason(preset)}
                  className={`text-left text-xs px-3 py-2 rounded-lg border transition-colors ${
                    reason === preset ? 'border-red-500/50 bg-red-500/10 text-red-300' : 'border-theme text-theme-muted hover:text-theme-primary hover:border-theme'
                  }`}
                >
                  {preset}
                </button>
              ))}
            </div>
          </div>
          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5">
              Or write a custom reason *
            </label>
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              rows={3}
              placeholder="Describe why the license cannot be verified…"
              className="w-full bg-theme-secondary border border-theme rounded-xl px-3.5 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-red-500 resize-none"
            />
            {reason.trim().length > 0 && reason.trim().length < 5 && (
              <p className="text-xs text-red-400 mt-1">Reason must be at least 5 characters.</p>
            )}
          </div>
        </div>
        <div className="flex gap-3 p-5 border-t border-theme">
          <button
            onClick={handleConfirm}
            disabled={submitting || reason.trim().length < 5}
            className="flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white font-semibold rounded-xl text-sm transition-colors"
          >
            {submitting ? <Loader2 className="w-4 h-4 animate-spin" /> : <ShieldX className="w-4 h-4" />}
            Reject License
          </button>
          <button onClick={onClose} className="px-5 py-2.5 border border-theme hover:bg-white/5 text-theme-secondary font-semibold rounded-xl text-sm transition-colors">
            Cancel
          </button>
        </div>
      </div>
    </div>
  );
}

// ─── License detail panel ─────────────────────────────────────────────────────

function LicenseDetailPanel({ license, onApprove, onReject, onClose }) {
  const cfg = STATUS_CONFIG[license.status] || STATUS_CONFIG.pending_review;
  const Icon = cfg.icon;

  return (
    <div className="fixed inset-0 z-40 flex items-center justify-end bg-black/40 backdrop-blur-sm" onClick={onClose}>
      <div className="bg-theme-card border-l border-theme w-full max-w-xl h-full overflow-y-auto shadow-2xl" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between p-5 border-b border-theme sticky top-0 bg-theme-card z-10">
          <h3 className="font-bold text-theme-primary text-base">License Review</h3>
          <button onClick={onClose} className="p-1.5 hover:bg-white/5 rounded-lg" aria-label="Close"><X className="w-5 h-5" /></button>
        </div>

        <div className="p-5 space-y-5">
          {/* Status */}
          <div className={`rounded-xl border p-3 flex items-center gap-3 ${cfg.bg}`}>
            <Icon className={`w-5 h-5 ${cfg.color}`} />
            <span className={`text-sm font-bold ${cfg.color}`}>{cfg.label}</span>
            {license.rejection_reason && (
              <p className="text-xs text-theme-muted ml-2">{license.rejection_reason}</p>
            )}
          </div>

          {/* Customer */}
          <Section title="Customer">
            <Row label="Name" value={license.customer?.name} />
            <Row label="Email" value={license.customer?.email} />
          </Section>

          {/* License Info */}
          <Section title="License Details">
            <Row label="License No." value={license.license_number} />
            <Row label="Full Name" value={license.full_name} />
            <Row label="Category" value={CATEGORIES[license.license_category] || license.license_category} />
            <Row label="Issue Date" value={fmtDate(license.issue_date)} />
            <Row label="Expiry Date" value={fmtDate(license.expiry_date)} />
            <Row label="Authority" value={license.issuing_authority || '—'} />
            <Row label="Country" value={license.issuing_country || '—'} />
            <Row label="Submitted" value={fmtDate(license.submitted_at)} />
          </Section>

          {/* Documents */}
          <Section title="Documents">
            <div className="flex gap-2">
              {license.has_front_document ? (
                <LicenseDocumentButton licenseId={license.id} side="front" label="Front" />
              ) : <span className="text-xs text-theme-muted">No front image</span>}

              {license.has_back_document ? (
                <LicenseDocumentButton licenseId={license.id} side="back" label="Back" />
              ) : <span className="text-xs text-theme-muted ml-2">No back image</span>}
            </div>
          </Section>

          {/* Actions */}
          {license.status === 'pending_review' && (
            <div className="flex gap-3 pt-2 flex-wrap">
              <button
                onClick={() => onApprove(license.id)}
                className="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl text-sm transition-colors"
              >
                <ShieldCheck className="w-4 h-4" />
                Approve
              </button>
              <button
                onClick={() => onReject(license)}
                className="flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white font-semibold rounded-xl text-sm transition-colors"
              >
                <ShieldX className="w-4 h-4" />
                Reject
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function Section({ title, children }) {
  return (
    <div>
      <p className="text-xs font-bold text-theme-muted uppercase tracking-wide mb-2">{title}</p>
      <div className="bg-theme-secondary/50 rounded-xl border border-theme divide-y divide-theme">
        {children}
      </div>
    </div>
  );
}

function Row({ label, value }) {
  return (
    <div className="flex items-center justify-between px-3 py-2.5">
      <span className="text-xs text-theme-muted">{label}</span>
      <span className="text-xs text-theme-primary font-medium text-right max-w-[60%]">{value || '—'}</span>
    </div>
  );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export const LicenseReviewPage = () => {
  const toast = useToast();
  const [licenses, setLicenses] = useState([]);
  const [summary, setSummary] = useState({});
  const [meta, setMeta] = useState({});
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);
  const [selectedLicense, setSelectedLicense] = useState(null);
  const [rejectTarget, setRejectTarget] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await licenseApi.adminList({ status: statusFilter, search, page, per_page: 20 });
      setLicenses(res.data || []);
      setSummary(res.summary || {});
      setMeta(res.meta || {});
    } catch {
      toast.error('Failed to load licenses.');
    } finally {
      setLoading(false);
    }
  }, [statusFilter, search, page]);

  useEffect(() => { load(); }, [load]);

  const handleApprove = async (id) => {
    try {
      const res = await licenseApi.approve(id);
      toast.success('License approved.');
      setLicenses((prev) => prev.map((l) => l.id === id ? res.data : l));
      setSelectedLicense(res.data);
    } catch (err) {
      toast.error(err.message || 'Failed to approve.');
    }
  };

  const handleReject = async (reason) => {
    try {
      const res = await licenseApi.reject(rejectTarget.id, reason);
      toast.success('License rejected.');
      setLicenses((prev) => prev.map((l) => l.id === rejectTarget.id ? res.data : l));
      setSelectedLicense(res.data);
      setRejectTarget(null);
    } catch (err) {
      toast.error(err.message || 'Failed to reject.');
    }
  };

  const statusTabs = [
    { key: '', label: 'All', count: (summary.pending || 0) + (summary.verified || 0) + (summary.rejected || 0) + (summary.expired || 0) },
    { key: 'pending_review', label: 'Pending', count: summary.pending || 0, color: 'text-amber-400' },
    { key: 'verified', label: 'Verified', count: summary.verified || 0, color: 'text-emerald-400' },
    { key: 'rejected', label: 'Rejected', count: summary.rejected || 0, color: 'text-red-400' },
    { key: 'expired', label: 'Expired', count: summary.expired || 0, color: 'text-orange-400' },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-extrabold text-theme-primary tracking-tight">License Verification</h1>
          <p className="text-sm text-theme-muted">Review and verify customer driver's licenses.</p>
        </div>
      </div>

      {/* Summary counts */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {[
          { label: 'Pending', value: summary.pending || 0, color: 'text-amber-400', bg: 'bg-amber-500/10' },
          { label: 'Verified', value: summary.verified || 0, color: 'text-emerald-400', bg: 'bg-emerald-500/10' },
          { label: 'Rejected', value: summary.rejected || 0, color: 'text-red-400', bg: 'bg-red-500/10' },
          { label: 'Expired', value: summary.expired || 0, color: 'text-orange-400', bg: 'bg-orange-500/10' },
        ].map((s) => (
          <button
            key={s.label}
            onClick={() => { setStatusFilter(s.label.toLowerCase() === 'pending' ? 'pending_review' : s.label.toLowerCase()); setPage(1); }}
            className={`${s.bg} border border-theme rounded-2xl p-4 text-left hover:opacity-80 transition-opacity`}
          >
            <p className={`text-2xl font-extrabold ${s.color}`}>{s.value}</p>
            <p className="text-xs text-theme-muted mt-1">{s.label}</p>
          </button>
        ))}
      </div>

      {/* Filters */}
      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search className="w-4 h-4 text-theme-muted absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by name or email…"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="w-full bg-theme-secondary border border-theme rounded-xl pl-9 pr-4 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
          />
        </div>

        <div className="flex gap-1 bg-theme-secondary border border-theme rounded-xl p-1 overflow-x-auto">
          {statusTabs.map((tab) => (
            <button
              key={tab.key}
              onClick={() => { setStatusFilter(tab.key); setPage(1); }}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors ${
                statusFilter === tab.key
                  ? 'bg-blue-600 text-white'
                  : 'text-theme-muted hover:text-theme-primary'
              }`}
            >
              {tab.label}
              {tab.count > 0 && (
                <span className={`text-[10px] font-bold ${statusFilter === tab.key ? 'opacity-80' : (tab.color || '')}`}>
                  {tab.count}
                </span>
              )}
            </button>
          ))}
        </div>
      </div>

      {/* Table */}
      <div className="bg-theme-card border border-theme rounded-2xl overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <Loader2 className="w-8 h-8 animate-spin text-blue-400" />
          </div>
        ) : licenses.length === 0 ? (
          <div className="text-center py-16">
            <FileText className="w-10 h-10 text-theme-muted mx-auto mb-3" />
            <p className="text-sm text-theme-muted">No licenses found.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-theme">
                  <th className="text-left px-5 py-3 text-xs font-bold text-theme-muted uppercase tracking-wide">Customer</th>
                  <th className="text-left px-5 py-3 text-xs font-bold text-theme-muted uppercase tracking-wide">License No.</th>
                  <th className="text-left px-5 py-3 text-xs font-bold text-theme-muted uppercase tracking-wide">Category</th>
                  <th className="text-left px-5 py-3 text-xs font-bold text-theme-muted uppercase tracking-wide">Expiry</th>
                  <th className="text-left px-5 py-3 text-xs font-bold text-theme-muted uppercase tracking-wide">Status</th>
                  <th className="text-left px-5 py-3 text-xs font-bold text-theme-muted uppercase tracking-wide">Submitted</th>
                  <th className="px-5 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {licenses.map((lic) => {
                  const cfg = STATUS_CONFIG[lic.status] || STATUS_CONFIG.pending_review;
                  const Icon = cfg.icon;
                  return (
                    <tr key={lic.id} className="hover:bg-white/2 transition-colors">
                      <td className="px-5 py-3.5">
                        <p className="font-medium text-theme-primary">{lic.customer?.name || '—'}</p>
                        <p className="text-xs text-theme-muted">{lic.customer?.email}</p>
                      </td>
                      <td className="px-5 py-3.5 font-mono text-theme-secondary text-xs">{lic.license_number_masked}</td>
                      <td className="px-5 py-3.5 text-theme-secondary">{CATEGORIES[lic.license_category] || lic.license_category}</td>
                      <td className="px-5 py-3.5 text-theme-secondary">{fmtDate(lic.expiry_date)}</td>
                      <td className="px-5 py-3.5">
                        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold ${cfg.badge}`}>
                          <Icon className="w-3 h-3" aria-hidden="true" />
                          {cfg.label}
                        </span>
                      </td>
                      <td className="px-5 py-3.5 text-theme-muted text-xs">{fmtDate(lic.submitted_at)}</td>
                      <td className="px-5 py-3.5">
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => setSelectedLicense(lic)}
                            className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-400 hover:bg-blue-500/10 border border-blue-500/20 rounded-lg transition-colors"
                          >
                            <Eye className="w-3.5 h-3.5" />
                            Review
                          </button>
                          {lic.status === 'pending_review' && (
                            <>
                              <button
                                onClick={() => handleApprove(lic.id)}
                                className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-400 hover:bg-emerald-500/10 border border-emerald-500/20 rounded-lg transition-colors"
                                aria-label={`Approve license for ${lic.customer?.name}`}
                              >
                                <ShieldCheck className="w-3.5 h-3.5" />
                                Approve
                              </button>
                              <button
                                onClick={() => setRejectTarget(lic)}
                                className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-400 hover:bg-red-500/10 border border-red-500/20 rounded-lg transition-colors"
                                aria-label={`Reject license for ${lic.customer?.name}`}
                              >
                                <ShieldX className="w-3.5 h-3.5" />
                                Reject
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Pagination */}
      {meta.last_page > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-xs text-theme-muted">
            Page {meta.current_page} of {meta.last_page} — {meta.total} total
          </p>
          <div className="flex gap-2">
            <button
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
              className="px-3 py-1.5 text-xs font-semibold border border-theme rounded-lg disabled:opacity-40 hover:bg-white/5 transition-colors"
            >
              Previous
            </button>
            <button
              disabled={page >= meta.last_page}
              onClick={() => setPage((p) => p + 1)}
              className="px-3 py-1.5 text-xs font-semibold border border-theme rounded-lg disabled:opacity-40 hover:bg-white/5 transition-colors"
            >
              Next
            </button>
          </div>
        </div>
      )}

      {/* Detail panel */}
      {selectedLicense && (
        <LicenseDetailPanel
          license={selectedLicense}
          onApprove={handleApprove}
          onReject={(lic) => { setRejectTarget(lic); setSelectedLicense(null); }}
          onClose={() => setSelectedLicense(null)}
        />
      )}

      {/* Reject modal */}
      {rejectTarget && (
        <RejectModal
          license={rejectTarget}
          onClose={() => setRejectTarget(null)}
          onConfirm={handleReject}
        />
      )}
    </div>
  );
};

export default LicenseReviewPage;
