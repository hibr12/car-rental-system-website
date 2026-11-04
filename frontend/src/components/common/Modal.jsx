import React, { useEffect } from 'react';
import { X } from 'lucide-react';

export const Modal = ({ isOpen, onClose, title, children, maxWidth = 'max-w-2xl' }) => {
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && isOpen) onClose();
    };
    if (isOpen) {
      document.body.style.overflow = 'hidden';
      window.addEventListener('keydown', handleKeyDown);
    }
    return () => {
      document.body.style.overflow = 'unset';
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen, onClose]);

  return (
    isOpen && (
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
          <div
            onClick={onClose}
            className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm apex-fade-enter"
          />

          <div
            className={`relative w-full ${maxWidth} bg-white border border-[#E2E8F0] rounded-2xl shadow-xl overflow-hidden z-10 my-8 apex-modal-enter`}
          >
            <div className="flex items-center justify-between px-6 py-4 border-b border-[#E2E8F0] bg-white">
              <h3 className="text-lg font-semibold text-[#0F172A]">{title}</h3>
              <button
                onClick={onClose}
                className="text-[#64748B] hover:text-[#0F172A] transition-colors p-1.5 rounded-lg hover:bg-[#F8FAFC]"
                aria-label="Close modal"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="p-6 max-h-[80vh] overflow-y-auto bg-white text-[#0F172A]">
              {children}
            </div>
          </div>
      </div>
    )
  );
};

export default Modal;
