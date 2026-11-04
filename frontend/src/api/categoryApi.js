import apiClient from './client';
import { getCachedReference, invalidateReference } from './referenceCache';

const invalidateCategories = () => invalidateReference('categories:');

const invalidateAfter = (request) => request.then((response) => {
  invalidateCategories();
  return response;
});

export const categoryApi = {
  getAll: () => getCachedReference(
    'categories:all',
    () => apiClient.get('/categories'),
    60_000,
  ),
  getById: (id) => apiClient.get(`/categories/${id}`),
  create: (payload) => invalidateAfter(apiClient.post('/categories', payload)),
  update: (id, payload) => invalidateAfter(apiClient.put(`/categories/${id}`, payload)),
  delete: (id) => invalidateAfter(apiClient.delete(`/categories/${id}`)),
};

export { invalidateCategories };

export default categoryApi;
