import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  ShieldCheck, ShieldX, Clock, AlertTriangle, Upload,
  RefreshCw, CheckCircle2, X, FileText, ChevronRight, Loader2,
} from 'lucide-react';
import { licenseApi } from '../../api/licenseApi';
import { useToast } from '../../components/common/Toast';
import LicenseDocumentButton from '../../components/shared/LicenseDocumentButton';

// ─── Status helpers ───────────────────────────────────────────────────────────

const STATUS_CONFIG = {
  pending_review: {
    icon: Clock,
    color: 'text-amber-400',
    bg: 'bg-amber-500/10 border-amber-500/20',
    badge: 'bg-amber-500/15 text-amber-400',
    label: 'Pending Verification',
  },
  verified: {
    icon: ShieldCheck,
    color: 'text-emerald-400',
    bg: 'bg-emerald-500/10 border-emerald-500/20',
    badge: 'bg-emerald-500/15 text-emerald-400',
    label: 'Verified',
  },
  rejected: {
    icon: ShieldX,
    color: 'text-red-400',
    bg: 'bg-red-500/10 border-red-500/20',
    badge: 'bg-red-500/15 text-red-400',
    label: 'Rejected',
  },
  expired: {
    icon: AlertTriangle,
    color: 'text-orange-400',
    bg: 'bg-orange-500/10 border-orange-500/20',
    badge: 'bg-orange-500/15 text-orange-400',
    label: 'Expired',
  },
};

const LICENSE_CATEGORIES = [
  { value: 'automobile', label: 'Automobile (Standard Car)' },
  { value: 'motorcycle', label: 'Motorcycle' },
  { value: 'commercial', label: 'Commercial Vehicle' },
  { value: 'minibus', label: 'Minibus' },
  { value: 'heavy', label: 'Heavy Vehicle' },
];

// ─── File drop zone ───────────────────────────────────────────────────────────

function FileDropZone({ label, accept, maxMb = 5, value, onChange, error }) {
  const [dragging, setDragging] = useState(false);
  const inputRef = useRef(null);
  const previewUrl = value ? URL.createObjectURL(value) : null;

  const handleFile = (file) => {
    if (!file) return;
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
    if (!validTypes.includes(file.type)) {
      return;
    }
    if (file.size > maxMb * 1024 * 1024) {
      return;
    }
    onChange(file);
  };

  return (
    <div>
      <p className="text-xs font-semibold text-theme-secondary mb-2">{label}</p>
      <div
        onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
        onDragLeave={() => setDragging(false)}
        onDrop={(e) => { e.preventDefault(); setDragging(false); handleFile(e.dataTransfer.files[0]); }}
        onClick={() => inputRef.current?.click()}
        className={`relative border-2 border-dashed rounded-2xl p-6 cursor-pointer transition-all text-center
          ${dragging ? 'border-blue-500 bg-blue-500/5' : 'border-theme hover:border-blue-400'}
          ${error ? 'border-red-500/50' : ''}
        `}
      >
        <input
          ref={inputRef}
          type="file"
          accept={accept}
          className="hidden"
          onChange={(e) => handleFile(e.target.files[0])}
        />

        {value ? (
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0">
              <CheckCircle2 className="w-5 h-5 text-emerald-400" />
            </div>
            <div className="text-left flex-1 min-w-0">
              <p className="text-sm font-medium text-theme-primary truncate">{value.name}</p>
              <p className="text-xs text-theme-muted">{(value.size / 1024).toFixed(0)} KB</p>
            </div>
            <button
              type="button"
              onClick={(e) => { e.stopPropagation(); onChange(null); }}
              className="p-1 rounded-full hover:bg-red-500/10"
              aria-label="Remove file"
            >
              <X className="w-4 h-4 text-red-400" />
            </button>
          </div>
        ) : (
          <div className="space-y-2">
            <div className="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center mx-auto">
              <Upload className="w-5 h-5 text-blue-400" />
            </div>
            <p className="text-sm text-theme-secondary">
              Drag & drop or <span className="text-blue-400 font-medium">browse</span>
            </p>
            <p className="text-xs text-theme-muted">JPEG, PNG, WEBP, PDF — max {maxMb} MB</p>
          </div>
        )}
      </div>
      {error && <p className="text-xs text-red-400 mt-1" role="alert">{error}</p>}
    </div>
  );
}

// ─── Status banner ────────────────────────────────────────────────────────────

function LicenseStatusBanner({ license, onResubmit }) {
  const cfg = STATUS_CONFIG[license.status] || STATUS_CONFIG.pending_review;
  const Icon = cfg.icon;

  return (
    <div className={`rounded-2xl border p-5 ${cfg.bg}`}>
      <div className="flex items-start gap-4">
        <div className={`w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center shrink-0`}>
          <Icon className={`w-6 h-6 ${cfg.color}`} aria-hidden="true" />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <h3 className="font-bold text-theme-primary text-lg">Driver's License</h3>
            <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold uppercase ${cfg.badge}`}>
              {cfg.label}
            </span>
          </div>

          {license.status === 'verified' && (
            <p className="text-sm text-theme-muted mt-1">
              Expires: <span className="text-theme-secondary font-medium">{formatDate(license.expiry_date)}</span>
              {license.days_until_expiry <= 30 && license.days_until_expiry > 0 && (
                <span className="ml-2 text-amber-400 font-semibold">
                  ⚠ {license.days_until_expiry} day{license.days_until_expiry !== 1 ? 's' : ''} remaining
                </span>
              )}
            </p>
          )}

          {license.status === 'pending_review' && (
            <p className="text-sm text-theme-muted mt-1">
              Your license is under review. You will be notified once it has been processed.
            </p>
          )}

          {license.status === 'rejected' && (
            <div className="mt-2">
              <p className="text-sm text-theme-secondary font-medium">Rejection Reason:</p>
              <p className="text-sm text-red-300 mt-0.5 bg-red-500/5 rounded-lg p-2 border border-red-500/20">
                {license.rejection_reason}
              </p>
            </div>
          )}

          {license.status === 'expired' && (
            <p className="text-sm text-theme-muted mt-1">
              Your license expired on {formatDate(license.expiry_date)}. Please upload your renewed license.
            </p>
          )}
        </div>
      </div>

      <div className="flex gap-2 mt-4 flex-wrap">
        {(license.status === 'rejected' || license.status === 'expired') && (
          <button
            onClick={onResubmit}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold rounded-xl transition-colors"
          >
            <RefreshCw className="w-4 h-4" />
            {license.status === 'rejected' ? 'Upload New License' : 'Update License'}
          </button>
        )}
      </div>
    </div>
  );
}

// ─── License details card ─────────────────────────────────────────────────────

function LicenseDetails({ license }) {
  const fields = [
    { label: 'License Number', value: license.license_number_masked },
    { label: 'Full Name', value: license.full_name },
    { label: 'Category', value: LICENSE_CATEGORIES.find(c => c.value === license.license_category)?.label || license.license_category },
    { label: 'Issue Date', value: formatDate(license.issue_date) },
    { label: 'Expiry Date', value: formatDate(license.expiry_date) },
    { label: 'Issuing Authority', value: license.issuing_authority || '—' },
  ];

  return (
    <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-5">
      <h4 className="font-bold text-theme-primary text-base flex items-center gap-2">
        <FileText className="w-4 h-4 text-blue-400" />
        License Details
      </h4>

      <dl className="grid grid-cols-2 gap-x-6 gap-y-4">
        {fields.map(({ label, value }) => (
          <div key={label}>
            <dt className="text-xs text-theme-muted font-medium uppercase tracking-wide">{label}</dt>
            <dd className="text-sm text-theme-primary font-semibold mt-0.5">{value}</dd>
          </div>
        ))}
      </dl>

      {(license.has_front_document || license.has_back_document) && (
        <div>
          <p className="text-xs text-theme-muted font-medium uppercase tracking-wide mb-3">Documents</p>
          <div className="flex gap-2 flex-wrap">
            {license.has_front_document && (
              <LicenseDocumentButton licenseId={license.id} side="front" label="Front" />
            )}
            {license.has_back_document && (
              <LicenseDocumentButton licenseId={license.id} side="back" label="Back" />
            )}
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Upload form ──────────────────────────────────────────────────────────────

function LicenseForm({ onSuccess, onCancel, isResubmit = false }) {
  const toast = useToast();
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});

  const [form, setForm] = useState({
    license_number: '',
    full_name: '',
    date_of_birth: '',
    license_category: 'automobile',
    issue_date: '',
    expiry_date: '',
    issuing_authority: '',
    issuing_country: '',
  });

  const [frontDoc, setFrontDoc] = useState(null);
  const [backDoc, setBackDoc] = useState(null);

  const set = (field) => (e) => {
    setForm((f) => ({ ...f, [field]: e.target.value }));
    setErrors((er) => ({ ...er, [field]: null }));
  };

  const validate = () => {
    const errs = {};
    if (!form.license_number.trim()) errs.license_number = 'License number is required.';
    if (!form.full_name.trim()) errs.full_name = 'Full name is required.';
    if (!form.license_category) errs.license_category = 'License category is required.';
    if (!form.issue_date) errs.issue_date = 'Issue date is required.';
    if (!form.expiry_date) errs.expiry_date = 'Expiry date is required.';
    if (form.expiry_date && form.expiry_date <= new Date().toISOString().slice(0, 10))
      errs.expiry_date = 'Expiry date must be in the future.';
    if (!frontDoc) errs.front_document = 'Front image is required.';
    if (!backDoc) errs.back_document = 'Back image is required.';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    const fd = new FormData();
    Object.entries(form).forEach(([k, v]) => { if (v) fd.append(k, v); });
    fd.append('front_document', frontDoc);
    fd.append('back_document', backDoc);

    setSubmitting(true);
    try {
      const res = await licenseApi.submit(fd);
      toast.success(isResubmit ? 'License resubmitted for verification.' : 'License submitted for verification.');
      onSuccess(res.data);
    } catch (err) {
      const apiErrors = err.errors || {};
      setErrors(apiErrors);
      toast.error(err.message || 'Submission failed. Please check the form.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate className="space-y-6">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Field label="License Number *" error={errors.license_number}>
          <input
            type="text"
            value={form.license_number}
            onChange={set('license_number')}
            placeholder="e.g. ETH-12345678"
            className={inputClass(errors.license_number)}
            aria-describedby={errors.license_number ? 'err-license_number' : undefined}
          />
        </Field>

        <Field label="Full Name (as on license) *" error={errors.full_name}>
          <input
            type="text"
            value={form.full_name}
            onChange={set('full_name')}
            className={inputClass(errors.full_name)}
          />
        </Field>

        <Field label="Date of Birth" error={errors.date_of_birth}>
          <input type="date" value={form.date_of_birth} onChange={set('date_of_birth')} className={inputClass()} />
        </Field>

        <Field label="License Category *" error={errors.license_category}>
          <select value={form.license_category} onChange={set('license_category')} className={inputClass(errors.license_category)}>
            {LICENSE_CATEGORIES.map((c) => (
              <option key={c.value} value={c.value}>{c.label}</option>
            ))}
          </select>
        </Field>

        <Field label="Issue Date *" error={errors.issue_date}>
          <input
            type="date"
            value={form.issue_date}
            max={new Date().toISOString().slice(0, 10)}
            onChange={set('issue_date')}
            className={inputClass(errors.issue_date)}
          />
        </Field>

        <Field label="Expiry Date *" error={errors.expiry_date}>
          <input
            type="date"
            value={form.expiry_date}
            min={new Date().toISOString().slice(0, 10)}
            onChange={set('expiry_date')}
            className={inputClass(errors.expiry_date)}
          />
        </Field>

        <Field label="Issuing Authority" error={errors.issuing_authority}>
          <input type="text" value={form.issuing_authority} onChange={set('issuing_authority')} placeholder="e.g. DRIVA Ethiopia" className={inputClass()} />
        </Field>

        <Field label="Country of Issue" error={errors.issuing_country}>
          <input type="text" value={form.issuing_country} onChange={set('issuing_country')} placeholder="e.g. Ethiopia" className={inputClass()} />
        </Field>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FileDropZone
          label="Front of License *"
          accept=".jpg,.jpeg,.png,.webp,.pdf"
          maxMb={5}
          value={frontDoc}
          onChange={(f) => { setFrontDoc(f); setErrors((e) => ({ ...e, front_document: null })); }}
          error={errors.front_document}
        />
        <FileDropZone
          label="Back of License *"
          accept=".jpg,.jpeg,.png,.webp,.pdf"
          maxMb={5}
          value={backDoc}
          onChange={(f) => { setBackDoc(f); setErrors((e) => ({ ...e, back_document: null })); }}
          error={errors.back_document}
        />
      </div>

      <div className="flex gap-3 pt-2 flex-wrap">
        <button
          type="submit"
          disabled={submitting}
          className="flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-60 text-white font-semibold rounded-xl text-sm transition-colors"
          aria-busy={submitting}
        >
          {submitting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Upload className="w-4 h-4" />}
          {submitting ? 'Submitting…' : 'Submit for Verification'}
        </button>
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="px-6 py-2.5 border border-theme hover:bg-white/5 text-theme-secondary font-semibold rounded-xl text-sm transition-colors"
          >
            Cancel
          </button>
        )}
      </div>
    </form>
  );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export const DriverLicensePage = () => {
  const toast = useToast();
  const [license, setLicense] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await licenseApi.getMyLicense();
      setLicense(res.data);
    } catch {
      toast.error('Failed to load license information.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  const handleFormSuccess = (newLicense) => {
    setLicense(newLicense);
    setShowForm(false);
  };

  if (loading) {
    return (
      <div className="max-w-3xl mx-auto space-y-4 animate-pulse">
        <div className="h-8 bg-white/5 rounded-xl w-1/3" />
        <div className="h-32 bg-white/5 rounded-2xl" />
        <div className="h-48 bg-white/5 rounded-2xl" />
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto space-y-8">
      {/* Header */}
      <div className="border-b border-theme pb-6">
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Driver's License</h1>
        <p className="text-sm text-theme-muted mt-1">
          A verified driver's license is required to book vehicles on Apex Rentals.
        </p>
      </div>

      {/* No license yet */}
      {!license && !showForm && (
        <div className="bg-theme-card border border-theme rounded-2xl p-8 text-center space-y-4">
          <div className="w-16 h-16 rounded-2xl bg-blue-500/10 flex items-center justify-center mx-auto">
            <ShieldCheck className="w-8 h-8 text-blue-400" />
          </div>
          <h3 className="text-lg font-bold text-theme-primary">No license on file</h3>
          <p className="text-sm text-theme-muted max-w-sm mx-auto">
            Upload your driver's license to verify your eligibility and start booking vehicles.
          </p>
          <button
            onClick={() => setShowForm(true)}
            className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-xl text-sm transition-colors"
          >
            <Upload className="w-4 h-4" />
            Upload License
          </button>
        </div>
      )}

      {/* Has license */}
      {license && !showForm && (
        <>
          <LicenseStatusBanner license={license} onResubmit={() => setShowForm(true)} />
          <LicenseDetails license={license} />
        </>
      )}

      {/* Upload / resubmit form */}
      {showForm && (
        <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-6">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-bold text-theme-primary">
              {license ? 'Upload Replacement License' : "Submit Driver's License"}
            </h3>
          </div>

          {license && (
            <div className="rounded-xl bg-amber-500/10 border border-amber-500/20 p-3 text-sm text-amber-300 flex items-start gap-2">
              <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
              <span>
                Your existing license will be marked as replaced. The new license will require
                verification before it becomes active.
              </span>
            </div>
          )}

          <LicenseForm
            isResubmit={!!license}
            onSuccess={handleFormSuccess}
            onCancel={() => setShowForm(false)}
          />
        </div>
      )}
    </div>
  );
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-US', {
    day: '2-digit', month: 'short', year: 'numeric',
  });
}

function inputClass(error) {
  return `w-full bg-theme-secondary border rounded-xl px-3.5 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors ${
    error ? 'border-red-500/50' : 'border-theme'
  }`;
}

function Field({ label, error, children }) {
  return (
    <div>
      <label className="block text-xs font-semibold text-theme-secondary mb-1.5">{label}</label>
      {children}
      {error && <p className="text-xs text-red-400 mt-1" role="alert">{error}</p>}
    </div>
  );
}

export default DriverLicensePage;
