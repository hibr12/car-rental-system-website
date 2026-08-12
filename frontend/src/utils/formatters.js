export const formatCurrency = (amount) => {
  const numeric = typeof amount === 'number' ? amount : parseFloat(amount || 0);
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'ETB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(numeric);
};

export const formatDate = (dateString, includeTime = false) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return 'Invalid date';

  const options = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    ...(includeTime ? { hour: '2-digit', minute: '2-digit' } : {}),
  };

  return new Intl.DateTimeFormat('en-US', options).format(date);
};

export const formatStatus = (status) => {
  if (!status) return '';
  return status
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
};

export const getStatusBadgeStyle = (status) => {
  switch (status?.toLowerCase()) {
    case 'available':
    case 'completed':
    case 'paid':
    case 'verified':
    case 'manually_confirmed':
    case 'approved':
    case 'active':
    case 'replied':
      return 'bg-green-50 text-[#16A34A] border-[#16A34A]/30';
    case 'confirmed':
    case 'ready_for_pickup':
    case 'processing':
    case 'rented':
    case 'in_progress':
    case 'gateway_pending':
    case 'verifying':
    case 'refund_pending':
      return 'bg-blue-50 text-[#2563EB] border-[#2563EB]/30';
    case 'pending':
    case 'pending_payment':
    case 'payment_required':
    case 'payment_processing':
    case 'payment_verified':
    case 'pending_branch_approval':
    case 'pending_admin_approval':
    case 'return_pending':
    case 'cash_pending':
    case 'reserved':
    case 'scheduled':
    case 'unpaid':
    case 'unverified':
    case 'branch_review':
    case 'not_required':
    case 'amount_mismatch':
    case 'currency_mismatch':
    case 'reference_mismatch':
    case 'verification_error':
    case 'disputed':
      return 'bg-amber-50 text-[#F59E0B] border-[#F59E0B]/30';
    case 'maintenance':
    case 'unavailable':
    case 'cancelled':
    case 'rejected':
    case 'failed':
    case 'invalid':
    case 'gateway_failed':
    case 'expired':
      return 'bg-red-50 text-[#DC2626] border-[#DC2626]/30';
    case 'refunded':
    case 'partially_refunded':
      return 'bg-slate-50 text-[#64748B] border-[#64748B]/30';
    default:
      return 'bg-slate-50 text-[#64748B] border-[#E2E8F0]';
  }
};

export const getRoleBadgeStyle = (role) => {
  switch (role?.toLowerCase()) {
    case 'admin':
    case 'super_admin':
      return 'bg-blue-50 text-[#2563EB] border-[#2563EB]/30';
    case 'branch_manager':
      return 'bg-blue-50 text-[#2563EB] border-[#2563EB]/30';
    case 'fleet_manager':
      return 'bg-amber-50 text-[#F59E0B] border-[#F59E0B]/30';
    case 'staff':
      return 'bg-slate-50 text-[#64748B] border-[#E2E8F0]';
    case 'customer':
      return 'bg-slate-50 text-[#64748B] border-[#E2E8F0]';
    default:
      return 'bg-slate-50 text-[#64748B] border-[#E2E8F0]';
  }
};

export const getTransferStatusStyle = (status) => {
  switch (status?.toLowerCase()) {
    case 'requested':   return 'bg-amber-50 text-[#F59E0B]';
    case 'approved':    return 'bg-blue-50 text-[#2563EB]';
    case 'in_transit':  return 'bg-blue-50 text-[#0EA5E9]';
    case 'completed':   return 'bg-green-50 text-[#16A34A]';
    case 'cancelled':   return 'bg-red-50 text-[#DC2626]';
    default:            return 'bg-slate-50 text-[#64748B]';
  }
};
