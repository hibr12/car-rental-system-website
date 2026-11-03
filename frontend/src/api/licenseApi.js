import apiClient from './client';

export const licenseApi = {
  // Customer endpoints
  getMyLicense: () => apiClient.get('/customer/license'),
  submit: (data) => apiClient.post('/customer/license', data),
  updateDocuments: (data) => apiClient.post('/customer/license/documents', data),
  eligibility: (vehicleId) => apiClient.get('/customer/license/eligibility', { params: { vehicle_id: vehicleId } }),

  // Admin/Staff endpoints
  adminList: (params = {}) => apiClient.get('/admin/licenses', { params }),
  adminShow: (id) => apiClient.get(`/admin/licenses/${id}`),
  approve: (id) => apiClient.post(`/admin/licenses/${id}/approve`),
  reject: (id, reason) => apiClient.post(`/admin/licenses/${id}/reject`, { reason }),

  // Document viewing
  openDocument: (licenseId, side) => {
    // Open synchronously from the click handler so browser popup protection does
    // not block the document after the authenticated download finishes.
    const previewWindow = window.open('', '_blank');
    if (previewWindow) previewWindow.opener = null;

    return apiClient.get(`/licenses/${licenseId}/document/${side}`, {
      responseType: 'blob',
    }).then((blob) => {
      const url = window.URL.createObjectURL(blob);

      if (previewWindow) {
        previewWindow.location.replace(url);
      } else {
        const download = document.createElement('a');
        download.href = url;
        download.download = `${side}-license-document`;
        document.body.appendChild(download);
        download.click();
        download.remove();
      }

      // Keep the URL alive long enough for large PDFs to finish loading.
      setTimeout(() => window.URL.revokeObjectURL(url), 60000);
    }).catch((error) => {
      previewWindow?.close();
      throw error;
    });
  },

  // Legacy/alias endpoints (for backward compatibility)
  get: () => apiClient.get('/customer/license'),
  upload: (data) => apiClient.post('/customer/license', data),
};

export default licenseApi;
