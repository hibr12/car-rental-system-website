import React, { useState, useEffect } from 'react';
import { MessageSquare, Mail, Phone, CheckCircle2, Trash2 } from 'lucide-react';
import contactApi from '../../api/contactApi';
import { formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

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
    } catch {
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
    } catch {
      toast.error('Failed to update message status.');
    }
  };

  const handleDeleteMessage = async (id) => {
    if (!window.confirm('Delete this message?')) return;
    try {
      await contactApi.delete(id);
      toast.success('Message deleted.');
      fetchMessages();
    } catch {
      toast.error('Failed to delete message.');
    }
  };

  return (
    <div className="mgmt-page">
      <ManagementPageHeader
        title="Contact Messages"
        description="Review inquiries submitted by website visitors."
      />

      <ManagementCard>
        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading messages...</div>
        ) : messages.length === 0 ? (
          <ManagementEmptyState
            icon={MessageSquare}
            title="No Messages In Inbox"
            description="New contact form submissions will appear here."
          />
        ) : (
          <div className="space-y-4">
            {messages.map((msg) => (
              <div
                key={msg.id}
                className="bg-white p-5 sm:p-6 rounded-xl border border-[#E2E8F0] space-y-3"
              >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#E2E8F0] pb-3">
                  <div>
                    <span className="text-xs font-bold text-[#2563EB] uppercase tracking-wider">
                      {msg.subject}
                    </span>
                    <h3 className="text-base font-bold text-[#0F172A]">{msg.name}</h3>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(msg.status)}`}>
                      {formatStatus(msg.status)}
                    </span>
                    <span className="text-xs text-[#64748B]">{formatDate(msg.created_at, true)}</span>
                  </div>
                </div>

                <div className="flex flex-wrap gap-4 text-xs text-[#64748B]">
                  <span className="flex items-center gap-1.5">
                    <Mail className="w-3.5 h-3.5 text-[#2563EB]" /> {msg.email}
                  </span>
                  {msg.phone && (
                    <span className="flex items-center gap-1.5">
                      <Phone className="w-3.5 h-3.5 text-[#0EA5E9]" /> {msg.phone}
                    </span>
                  )}
                </div>

                <p className="text-sm text-[#334155] leading-relaxed bg-[#F8FAFC] p-4 rounded-xl border border-[#E2E8F0]">
                  &ldquo;{msg.message}&rdquo;
                </p>

                <div className="pt-1 flex justify-end gap-2">
                  {msg.status !== 'replied' && (
                    <ManagementButton variant="success" onClick={() => handleMarkReplied(msg.id)}>
                      <CheckCircle2 className="w-3.5 h-3.5" />
                      Mark as Replied
                    </ManagementButton>
                  )}
                  <ManagementButton variant="dangerOutline" onClick={() => handleDeleteMessage(msg.id)}>
                    <Trash2 className="w-3.5 h-3.5" />
                    Delete
                  </ManagementButton>
                </div>
              </div>
            ))}
          </div>
        )}

        {meta.last_page > 1 && (
          <div className="pt-4 border-t border-[#E2E8F0] mt-4">
            <Pagination
              currentPage={meta.current_page}
              lastPage={meta.last_page}
              total={meta.total}
              onPageChange={setPage}
            />
          </div>
        )}
      </ManagementCard>
    </div>
  );
};

export default MessagesPage;
