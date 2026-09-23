import React, { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react';
import { AlertTriangle } from 'lucide-react';
import Modal from './Modal';

const ConfirmContext = createContext(null);

/**
 * In-app replacement for window.confirm / window.prompt.
 *
 *   const confirm = useConfirm();
 *   if (!(await confirm({ title: 'Delete vehicle?', message: '…', danger: true }))) return;
 *
 *   // With a text field — resolves to the entered text, or null if cancelled:
 *   const reason = await confirm({ title: 'Reject transfer', input: { label: 'Reason', required: true } });
 */
export const ConfirmProvider = ({ children }) => {
  const [dialog, setDialog] = useState(null);
  const [text, setText] = useState('');
  const resolverRef = useRef(null);

  const confirm = useCallback((options) => new Promise((resolve) => {
    resolverRef.current = resolve;
    setText(options.input?.defaultValue ?? '');
    setDialog(options);
  }), []);

  const close = (result) => {
    resolverRef.current?.(result);
    resolverRef.current = null;
    setDialog(null);
  };

  const cancelResult = dialog?.input ? null : false;
  const inputMissing = dialog?.input?.required && !text.trim();

  const handleSubmit = (e) => {
    e.preventDefault();
    if (inputMissing) return;
    close(dialog.input ? text.trim() : true);
  };

  const value = useMemo(() => confirm, [confirm]);

  return (
    <ConfirmContext.Provider value={value}>
      {children}
      <Modal
        isOpen={!!dialog}
        onClose={() => close(cancelResult)}
        title={dialog?.title || 'Please confirm'}
        maxWidth="max-w-md"
      >
        {dialog && (
          <form onSubmit={handleSubmit} className="space-y-5">
            <div className="flex gap-3">
              {dialog.danger && (
                <div className="w-10 h-10 shrink-0 rounded-full bg-red-50 text-red-600 flex items-center justify-center">
                  <AlertTriangle className="w-5 h-5" />
                </div>
              )}
              {dialog.message && (
                <p className="text-sm text-[#334155] leading-relaxed pt-2">{dialog.message}</p>
              )}
            </div>

            {dialog.input && (
              <label className="block space-y-1.5">
                <span className="text-sm font-medium text-[#0F172A]">
                  {dialog.input.label}
                  {!dialog.input.required && <span className="text-[#94A3B8] font-normal"> (optional)</span>}
                </span>
                <textarea
                  autoFocus
                  rows={3}
                  value={text}
                  onChange={(e) => setText(e.target.value)}
                  placeholder={dialog.input.placeholder}
                  className="w-full rounded-xl border border-[#E2E8F0] px-3 py-2 text-sm text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                />
              </label>
            )}

            <div className="flex justify-end gap-3">
              <button
                type="button"
                onClick={() => close(cancelResult)}
                className="px-4 py-2 rounded-xl text-sm font-semibold text-[#334155] bg-[#F1F5F9] hover:bg-[#E2E8F0] transition-colors"
              >
                {dialog.cancelLabel || 'Cancel'}
              </button>
              <button
                type="submit"
                autoFocus={!dialog.input}
                disabled={inputMissing}
                className={`px-4 py-2 rounded-xl text-sm font-semibold text-white transition-colors disabled:opacity-50 ${
                  dialog.danger ? 'bg-red-600 hover:bg-red-700' : 'bg-[#2563EB] hover:bg-blue-700'
                }`}
              >
                {dialog.confirmLabel || 'Confirm'}
              </button>
            </div>
          </form>
        )}
      </Modal>
    </ConfirmContext.Provider>
  );
};

export const useConfirm = () => {
  const context = useContext(ConfirmContext);
  if (!context) throw new Error('useConfirm must be used within a ConfirmProvider');
  return context;
};

export default ConfirmProvider;
