import apiClient from './client';
import { getCachedReference, invalidateReference } from './referenceCache';

export const invalidateBranchDirectory = () => invalidateReference('branches:directory:');

export const branchApi = {
  getAll: (params = {}) => {
    const isActiveDirectory = params.status === 'active'
      && Object.keys(params).every((key) => key === 'status');

    if (!isActiveDirectory) return apiClient.get('/branches', { params });

    return getCachedReference(
      'branches:directory:active',
      () => apiClient.get('/branches', { params }),
      60_000,
    );
  },
  getById: (id) => apiClient.get(`/branches/${id}`),
};

export default branchApi;
