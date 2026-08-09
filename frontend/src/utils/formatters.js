export const formatCurrency = (amount) => {
  const numeric = typeof amount === 'number' ? amount : parseFloat(amount || 0);
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
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
      return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
    default:
      return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
  }
};

export const getRoleBadgeStyle = (role) => {
  switch (role?.toLowerCase()) {
    case 'admin':
      return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
    case 'fleet_manager':
      return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
    case 'staff':
      return 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20';
    case 'customer':
      return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
    default:
      return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
  }
};
