import React, { useState, useEffect } from 'react';
import { Users, Search, Shield, UserCheck, Wrench, User as UserIcon, Edit } from 'lucide-react';
import adminApi from '../../api/adminApi';
import { formatDate, getRoleBadgeStyle, formatStatus } from '../../utils/formatters';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

export const UserManagement = () => {
  const toast = useToast();
  const [users, setUsers] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  // Edit user role modal state
  const [selectedUser, setSelectedUser] = useState(null);
  const [newRole, setNewRole] = useState('customer');
  const [roleModalOpen, setRoleModalOpen] = useState(false);
  const [updating, setUpdating] = useState(false);

  const fetchUsers = async () => {
    try {
      setLoading(true);
      const res = await adminApi.getUsers({ page, per_page: 10 });
      setUsers(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error('Failed to load platform users.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, [page]);

  const handleUpdateRole = async (e) => {
    e.preventDefault();
    if (!selectedUser) return;

    try {
      setUpdating(true);
      await adminApi.updateUser(selectedUser.id, { role: newRole });
      toast.success(`User role updated to ${newRole}!`);
      setRoleModalOpen(false);
      fetchUsers();
    } catch (err) {
      toast.error(err.message || 'Failed to update user role.');
    } finally {
      setUpdating(false);
    }
  };

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="User Account Management"
        description="View registered users, inspect details, and update role privileges."
      />

      <ManagementCard className="space-y-6">
        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading users list...</div>
        ) : users.length === 0 ? (
          <ManagementEmptyState icon={Users} title="No Users Found" />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase bg-[#F8FAFC] text-[#334155] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">User Profile</th>
                  <th className="py-3.5 px-4 font-semibold">Email</th>
                  <th className="py-3.5 px-4 font-semibold">Phone</th>
                  <th className="py-3.5 px-4 font-semibold">Role</th>
                  <th className="py-3.5 px-4 font-semibold">Joined Date</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {users.map((u) => (
                  <tr key={u.id} className="hover:bg-[#F8FAFC] transition-colors">
                    <td className="py-4 px-4 font-medium text-[#0F172A]">
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-blue-50 text-[#2563EB] font-bold text-xs flex items-center justify-center shrink-0 border border-blue-100">
                          {u.name?.[0]?.toUpperCase() || 'U'}
                        </div>
                        <span className="font-bold">{u.name}</span>
                      </div>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{u.email}</td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{u.phone || 'N/A'}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold uppercase rounded-full border ${getRoleBadgeStyle(u.role)}`}>
                        {formatStatus(u.role)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(u.created_at)}</td>
                    <td className="py-4 px-4 text-right">
                      <ManagementButton
                        variant="secondary"
                        onClick={() => {
                          setSelectedUser(u);
                          setNewRole(u.role || 'customer');
                          setRoleModalOpen(true);
                        }}
                        className="ml-auto"
                      >
                        <Edit className="w-3.5 h-3.5" />
                        <span>Edit Role</span>
                      </ManagementButton>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
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
      </ManagementCard>

      {/* Role Assignment Modal */}
      {selectedUser && (
        <Modal
          isOpen={roleModalOpen}
          onClose={() => setRoleModalOpen(false)}
          title={`Update User Role: ${selectedUser.name}`}
          maxWidth="max-w-md"
        >
          <form onSubmit={handleUpdateRole} className="space-y-4 text-xs">
            <div>
              <label className="block text-[#334155] font-semibold mb-1.5">Select Role Assignment</label>
              <select
                value={newRole}
                onChange={(e) => setNewRole(e.target.value)}
                className="w-full bg-white border border-[#CBD5E1] rounded-xl p-3 text-sm text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
              >
                <option value="customer">Customer (Standard Renter)</option>
                <option value="staff">Staff Member (Pickup/Return Desk)</option>
                <option value="fleet_manager">Fleet Manager (Vehicles & Maintenance)</option>
                <option value="admin">Administrator (Full System Access)</option>
              </select>
            </div>

            <ManagementButton type="submit" disabled={updating} className="w-full py-3.5">
              {updating ? 'Updating...' : 'Save User Role'}
            </ManagementButton>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default UserManagement;
