import React, { useState, useEffect } from 'react';
import { MessageSquare, Mail, Phone, CheckCircle2, Trash2 } from 'lucide-react';
import contactApi from '../../api/contactApi';
import { formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const MessagesPage = () => {
  const toast = useToast();
  const [messages, setMessages] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  const fetchMessages = async () => {
    try {
      setLoading(true);
      const res = await contactApi.getAll({ page, per_page: 10 });
      setMessages(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error('Failed to load contact messages.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMessages();
  }, [page]);

  const handleMarkReplied = async (id) => {
    try {
      await contactApi.update(id, { status: 'replied' });
      toast.success('Message marked as replied.');
      fetchMessages();
    } catch (err) {
      toast.error('Failed to update message status.');
    }
  };

  const handleDeleteMessage = async (id) => {
    if (!window.confirm('Delete this message?')) return;
    try {
      await contactApi.delete(id);
      toast.success('Message deleted.');
      fetchMessages();
    } catch (err) {
      toast.error('Failed to delete message.');
    }
  };

  return (
    <div className="space-y-8">
      <div className="border-b border-slate-800 pb-6">
        <h1 className="text-3xl font-extrabold text-white tracking-tight">Contact Messages Inbox</h1>
        <p className="text-sm text-slate-400">Review inquiries submitted by website visitors.</p>
      </div>

      <div className="bg-theme-card border border-theme rounded-xl p-6 sm:p-8 space-y-6 shadow-xl">
        {loading ? (
          <div className="py-12 text-center text-slate-400 text-sm">Loading messages...</div>
        ) : messages.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <MessageSquare className="w-12 h-12 text-slate-700 mx-auto" />
            <p className="text-sm font-semibold text-slate-300">No Messages In Inbox</p>
          </div>
        ) : (
          <div className="space-y-4">
            {messages.map((msg) => (
              <div key={msg.id} className="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-3">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
                  <div>
                    <span className="text-xs font-bold text-blue-400 uppercase tracking-wider">{msg.subject}</span>
                    <h3 className="text-base font-bold text-white">{msg.name}</h3>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(msg.status)}`}>
                      {formatStatus(msg.status)}
                    </span>
                    <span className="text-xs text-slate-500">{formatDate(msg.created_at, true)}</span>
                  </div>
                </div>

                <div className="flex flex-wrap gap-4 text-xs text-slate-400">
                  <span className="flex items-center gap-1.5">
                    <Mail className="w-3.5 h-3.5 text-blue-400" /> {msg.email}
                  </span>
                  {msg.phone && (
                    <span className="flex items-center gap-1.5">
                      <Phone className="w-3.5 h-3.5 text-indigo-400" /> {msg.phone}
                    </span>
                  )}
                </div>

                <p className="text-xs text-slate-200 leading-relaxed bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                  "{msg.message}"
                </p>

                <div className="pt-2 flex justify-end gap-2">
                  {msg.status !== 'replied' && (
                    <button
                      onClick={() => handleMarkReplied(msg.id)}
                      className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold flex items-center gap-1.5"
                    >
                      <CheckCircle2 className="w-3.5 h-3.5" />
                      <span>Mark as Replied</span>
                    </button>
                  )}
                  <button
                    onClick={() => handleDeleteMessage(msg.id)}
                    className="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

        {meta.last_page > 1 && (
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(p) => setPage(p)}
          />
        )}
      </div>
    </div>
  );
};

export default MessagesPage;
