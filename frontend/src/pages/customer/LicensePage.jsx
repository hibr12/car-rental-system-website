import React, { useState, useEffect } from "react";
import { Upload, CheckCircle, XCircle, Clock, FileText } from "lucide-react";
import licenseApi from "../../api/licenseApi";
import { useToast } from "../../components/common/Toast";

const LicensePage = () => {
  const [license, setLicense] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isUploading, setIsUploading] = useState(false);
  const [licenseNumber, setLicenseNumber] = useState("");
  const [licenseImage, setLicenseImage] = useState(null);
  const { success, error } = useToast();

  useEffect(() => {
    fetchLicense();
  }, []);

  const fetchLicense = async () => {
    try {
      setIsLoading(true);
      const response = await licenseApi.get();
      setLicense(response.data?.data || response.data);
    } catch (err) {
      console.error("Failed to fetch license:", err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!licenseNumber || !licenseImage) {
      error("Please fill in all fields.");
      return;
    }

    try {
      setIsUploading(true);
      const formData = new FormData();
      formData.append("license_number", licenseNumber);
      formData.append("license_image", licenseImage);

      await licenseApi.upload(formData);
      success("Driver's license submitted successfully. Pending verification.");
      fetchLicense();
      setLicenseNumber("");
      setLicenseImage(null);
    } catch (err) {
      error(err.message || "Failed to upload license.");
    } finally {
      setIsUploading(false);
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case "verified":
        return <CheckCircle className="w-5 h-5 text-emerald-400" />;
      case "rejected":
        return <XCircle className="w-5 h-5 text-rose-400" />;
      case "pending":
        return <Clock className="w-5 h-5 text-amber-400" />;
      default:
        return <FileText className="w-5 h-5 text-theme-muted" />;
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "verified":
        return "bg-emerald-500/20 text-emerald-400 border-emerald-500/30";
      case "rejected":
        return "bg-rose-500/20 text-rose-400 border-rose-500/30";
      case "pending":
        return "bg-amber-500/20 text-amber-400 border-amber-500/30";
      default:
        return "bg-theme-secondary text-theme-secondary border-theme";
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin w-8 h-8 border-2 border-theme border-t-transparent rounded-full" />
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-theme-primary">
          Driver's License
        </h1>
        <p className="text-theme-secondary text-sm mt-1">
          Upload and manage your driver's license for rental verification.
        </p>
      </div>

      {/* Current Status */}
      <div className="bg-theme-card border border-theme rounded-xl p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="font-semibold text-theme-primary">License Status</h2>
          <span
            className={`px-3 py-1 rounded-full text-xs font-medium border ${getStatusColor(
              license?.license_status
            )}`}
          >
            {license?.license_status?.replace("_", " ")?.toUpperCase() || "NOT UPLOADED"}
          </span>
        </div>

        {license?.license_status === "verified" && (
          <div className="flex items-center gap-2 text-emerald-400 text-sm">
            <CheckCircle className="w-4 h-4" />
            <span>Your driver's license has been verified successfully.</span>
          </div>
        )}

        {license?.license_status === "rejected" && (
          <div className="flex items-center gap-2 text-rose-400 text-sm">
            <XCircle className="w-4 h-4" />
            <span>Your license was rejected. Please upload a valid document.</span>
          </div>
        )}

        {license?.license_status === "pending" && (
          <div className="flex items-center gap-2 text-amber-400 text-sm">
            <Clock className="w-4 h-4" />
            <span>Your license is pending verification.</span>
          </div>
        )}

        {license?.license_number && (
          <div className="mt-4 p-3 bg-theme-secondary rounded-lg">
            <p className="text-theme-muted text-xs">License Number</p>
            <p className="text-theme-primary font-mono">{license.license_number}</p>
          </div>
        )}
      </div>

      {/* Upload Form */}
      {license?.license_status !== "verified" && (
        <div className="bg-theme-card border border-theme rounded-xl p-6">
          <h2 className="font-semibold text-theme-primary mb-4">
            {license?.license_status === "pending" || license?.license_status === "rejected"
              ? "Re-upload License"
              : "Upload License"}
          </h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-theme-secondary text-sm font-medium mb-2">
                License Number
              </label>
              <input
                type="text"
                value={licenseNumber}
                onChange={(e) => setLicenseNumber(e.target.value)}
                placeholder="Enter your license number"
                className="w-full px-4 py-2.5 bg-theme-input border border-theme rounded-lg text-theme-primary placeholder-theme-muted focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>

            <div>
              <label className="block text-theme-secondary text-sm font-medium mb-2">
                License Image
              </label>
              <div className="border-2 border-dashed border-theme rounded-lg p-6 text-center hover:border-blue-500/50 transition-colors">
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setLicenseImage(e.target.files[0])}
                  className="hidden"
                  id="license-upload"
                  required
                />
                <label
                  htmlFor="license-upload"
                  className="cursor-pointer flex flex-col items-center gap-2"
                >
                  <Upload className="w-8 h-8 text-theme-muted" />
                  <span className="text-theme-secondary text-sm">
                    {licenseImage ? licenseImage.name : "Click to upload license image"}
                  </span>
                  <span className="text-theme-muted text-xs">
                    JPG, PNG up to 5MB
                  </span>
                </label>
              </div>
            </div>

            <button
              type="submit"
              disabled={isUploading}
              className="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
            >
              {isUploading ? "Uploading..." : "Submit License"}
            </button>
          </form>
        </div>
      )}
    </div>
  );
};

export default LicensePage;
