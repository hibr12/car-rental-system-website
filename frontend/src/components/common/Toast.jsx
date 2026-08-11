import React, { createContext, useContext, useState, useCallback } from 'react';
import { CheckCircle2, AlertTriangle, XCircle, Info, X } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

const ToastContext = createContext(null);

export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);

  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  const showToast = useCallback((message, type = 'info', duration = 4000) => {
    const id = Date.now() + Math.random();
    setToasts((prev) => [...prev, { id, message, type }]);

    if (duration > 0) {
      setTimeout(() => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
      }, duration);
    }
  }, []);

  const success = (msg, dur) => showToast(msg, 'success', dur);
  const error = (msg, dur) => showToast(msg, 'error', dur);
  const warning = (msg, dur) => showToast(msg, 'warning', dur);
  const info = (msg, dur) => showToast(msg, 'info', dur);

  return (
    <ToastContext.Provider value={{ showToast, success, error, warning, info }}>
      {children}
      <div className="fixed bottom-6 right-6 z-50 flex flex-col gap-3 max-w-md w-full pointer-events-none px-4 sm:px-0">
        <AnimatePresence>
          {toasts.map((toast) => (
            <ToastItem key={toast.id} toast={toast} onClose={() => removeToast(toast.id)} />
          ))}
        </AnimatePresence>
      </div>
    </ToastContext.Provider>
  );
};

export const useToast = () => {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
};

const ToastItem = ({ toast, onClose }) => {
  const styles = {
    success: {
      icon: <CheckCircle2 className="w-5 h-5 text-[#16A34A] shrink-0" />,
      border: 'border-[#16A34A]/30',
      text: 'text-[#0F172A]',
      accent: 'border-l-[#16A34A]',
    },
    error: {
      icon: <XCircle className="w-5 h-5 text-[#DC2626] shrink-0" />,
      border: 'border-[#DC2626]/30',
      text: 'text-[#0F172A]',
      accent: 'border-l-[#DC2626]',
    },
    warning: {
      icon: <AlertTriangle className="w-5 h-5 text-[#F59E0B] shrink-0" />,
      border: 'border-[#F59E0B]/30',
      text: 'text-[#0F172A]',
      accent: 'border-l-[#F59E0B]',
    },
    info: {
      icon: <Info className="w-5 h-5 text-[#2563EB] shrink-0" />,
      border: 'border-[#2563EB]/30',
      text: 'text-[#0F172A]',
      accent: 'border-l-[#2563EB]',
    },
  };

  const style = styles[toast.type] || styles.info;

  return (
    <motion.div
      initial={{ opacity: 0, y: 20, scale: 0.95 }}
      animate={{ opacity: 1, y: 0, scale: 1 }}
      exit={{ opacity: 0, y: 10, scale: 0.95 }}
      className={`pointer-events-auto flex items-start gap-3 p-4 rounded-xl border border-l-4 bg-white shadow-lg ${style.border} ${style.accent} ${style.text}`}
    >
      {style.icon}
      <p className="flex-1 text-sm font-medium text-[#334155]">{toast.message}</p>
      <button
        onClick={onClose}
        className="text-[#94A3B8] hover:text-[#0F172A] transition-colors"
        aria-label="Dismiss notification"
      >
        <X className="w-4 h-4" />
      </button>
    </motion.div>
  );
};

export default ToastProvider;
