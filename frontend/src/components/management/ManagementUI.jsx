import React from 'react';

export const ManagementPageHeader = ({
  eyebrow,
  title,
  description,
  actions,
}) => (
  <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-[#E2E8F0] pb-6">
    <div>
      {eyebrow ? (
        <span className="text-xs uppercase font-semibold tracking-wider text-[#64748B]">
          {eyebrow}
        </span>
      ) : null}
      <h1 className="text-2xl sm:text-3xl font-extrabold text-[#0F172A] tracking-tight">
        {title}
      </h1>
      {description ? (
        <p className="text-sm text-[#64748B] mt-1 max-w-2xl">{description}</p>
      ) : null}
    </div>
    {actions ? <div className="flex flex-wrap items-center gap-2 shrink-0">{actions}</div> : null}
  </div>
);

export const ManagementCard = ({ children, className = '', padding = true }) => (
  <div className={`bg-white border border-[#E2E8F0] rounded-xl ${padding ? 'p-5 sm:p-6' : ''} ${className}`}>
    {children}
  </div>
);

export const ManagementEmptyState = ({ icon: Icon, title, description, action }) => (
  <div className="text-center py-14 space-y-3 bg-white">
    {Icon ? <Icon className="w-12 h-12 text-[#94A3B8] mx-auto" /> : null}
    <p className="text-sm font-semibold text-[#0F172A]">{title}</p>
    {description ? <p className="text-xs text-[#64748B] max-w-sm mx-auto">{description}</p> : null}
    {action || null}
  </div>
);

export const ManagementButton = ({
  children,
  variant = 'primary',
  className = '',
  type = 'button',
  ...props
}) => {
  const variants = {
    primary: 'bg-[#2563EB] hover:bg-blue-700 text-white',
    secondary: 'bg-white border border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC]',
    success: 'bg-[#16A34A] hover:bg-green-700 text-white',
    danger: 'bg-[#DC2626] hover:bg-red-700 text-white',
    dangerOutline: 'bg-white border border-red-200 text-[#DC2626] hover:bg-red-50',
    warning: 'bg-[#F59E0B] hover:bg-amber-600 text-white',
  };

  return (
    <button
      type={type}
      className={`inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors disabled:opacity-50 ${variants[variant] || variants.primary} ${className}`}
      {...props}
    >
      {children}
    </button>
  );
};

export default ManagementPageHeader;
