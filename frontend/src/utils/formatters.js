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
    case 'confirmed':
    case 'active':
    case 'replied':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
    case 'pending':
    case 'reserved':
    case 'scheduled':
    case 'unpaid':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
    case 'rented':
    case 'in_progress':
      return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
    case 'maintenance':
    case 'unavailable':
    case 'cancelled':
    case 'rejected':
    case 'failed':
      return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
    case 'refunded':
      return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
    default:
      return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
  }
};

export const getRoleBadgeStyle = (role) => {
  switch (role?.toLowerCase()) {
    case 'admin':
    case 'super_admin':
      return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
    case 'branch_manager':
      return 'bg-green-500/10 text-green-400 border-green-500/20';
    case 'fleet_manager':
      return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
    case 'staff':
      return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20';
    case 'customer':
      return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
    default:
      return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
  }
};

export const getTransferStatusStyle = (status) => {
  switch (status?.toLowerCase()) {
    case 'requested':   return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
    case 'approved':    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    case 'in_transit':  return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
    case 'completed':   return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    case 'cancelled':   return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    default:            return 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400';
  }
};
