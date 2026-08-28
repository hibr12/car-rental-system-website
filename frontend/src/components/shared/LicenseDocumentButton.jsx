import React, { useState } from 'react';
import { Eye, Loader2 } from 'lucide-react';
import { licenseApi } from '../../api/licenseApi';
import { useToast } from '../common/Toast';

/**
 * Opens a license document in a new tab using an authenticated API request.
 * Plain <a href> links cannot send the Bearer token, so direct URLs return 401.
 */
export function LicenseDocumentButton({
  licenseId,
  side,
  label,
  className = 'flex items-center gap-2 px-3 py-2 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-xl text-blue-400 text-sm font-medium transition-colors disabled:opacity-60',
}) {
  const toast = useToast();
  const [loading, setLoading] = useState(false);

  const handleView = async () => {
    setLoading(true);
    try {
      await licenseApi.openDocument(licenseId, side);
    } catch (err) {
      toast.error(err.message || 'Could not open the document.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <button
      type="button"
      onClick={handleView}
      disabled={loading}
      className={className}
      aria-label={`View ${label || side} of license`}
    >
      {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Eye className="w-4 h-4" />}
      {label || side}
    </button>
  );
}

export default LicenseDocumentButton;
