import React, { useState } from 'react';
import { Mail, Phone, MapPin, Send, CheckCircle2, Loader2, MessageSquare, Clock } from 'lucide-react';
import contactApi from '../../api/contactApi';
import { useToast } from '../../components/common/Toast';

export const ContactPage = () => {
  const toast = useToast();
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
  });
  const [loading, setLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setLoading(true);
      await contactApi.submit(formData);
      setSuccessMessage('Thank you! Your message has been received. Our support team will get back to you shortly.');
      toast.success('Contact message submitted!');
      setFormData({ name: '', email: '', phone: '', subject: '', message: '' });
    } catch (err) {
      toast.error(err.message || 'Failed to submit contact message.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
      {/* Header */}
      <div className="text-center space-y-3 max-w-3xl mx-auto">
        <span className="text-xs font-extrabold uppercase tracking-wider text-blue-400">
          24/7 Concierge Support
        </span>
        <h1 className="text-3xl sm:text-5xl font-extrabold text-theme-primary tracking-tight">
          Get In Touch With ApexRentals
        </h1>
        <p className="text-theme-muted text-sm sm:text-base leading-relaxed">
          Have questions about our fleet, booking policies, or corporate rentals? Fill out the form below or reach our team directly.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start">
        {/* Contact Info Cards */}
        <div className="space-y-6 lg:col-span-1">
          <div className="bg-theme-card border border-theme p-6 rounded-3xl space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
              <Phone className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-base font-bold text-theme-primary">Call Us Directly</h3>
              <p className="text-xs text-theme-muted mt-1">Available 24 hours a day, 7 days a week</p>
            </div>
            <p className="text-sm font-semibold text-blue-400">+1 (800) 555-APEX</p>
          </div>

          <div className="bg-theme-card border border-theme p-6 rounded-3xl space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-indigo-600/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
              <Mail className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-base font-bold text-theme-primary">Email Inquiries</h3>
              <p className="text-xs text-theme-muted mt-1">We typically reply within 2 hours</p>
            </div>
            <p className="text-sm font-semibold text-indigo-400">support@apexrentals.com</p>
          </div>

          <div className="bg-theme-card border border-theme p-6 rounded-3xl space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-purple-600/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
              <MapPin className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-base font-bold text-theme-primary">Headquarters Terminal</h3>
              <p className="text-xs text-theme-muted mt-1">100 Premium Boulevard, Grand City</p>
            </div>
            <p className="text-xs text-theme-muted">Open for key pickups & returns daily: 06:00 AM - 11:00 PM</p>
          </div>
        </div>

        {/* Contact Form */}
        <div className="lg:col-span-2 bg-theme-card border border-theme p-8 sm:p-10 rounded-3xl space-y-6 shadow-2xl">
          <div>
            <h2 className="text-2xl font-bold text-theme-primary">Send Us A Message</h2>
            <p className="text-xs text-theme-muted mt-1">
              Please complete all required fields below.
            </p>
          </div>

          {successMessage && (
            <div className="bg-emerald-500/10 border border-emerald-500/30 p-4 rounded-2xl flex items-center gap-3 text-emerald-300 text-sm">
              <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
              <span>{successMessage}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Full Name *</label>
                <input
                  type="text"
                  required
                  placeholder="John Doe"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-theme-secondary border border-theme rounded-xl px-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Email Address *</label>
                <input
                  type="email"
                  required
                  placeholder="john@example.com"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full bg-theme-secondary border border-theme rounded-xl px-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Phone Number</label>
                <input
                  type="tel"
                  placeholder="+1 (555) 000-0000"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  className="w-full bg-theme-secondary border border-theme rounded-xl px-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Subject *</label>
                <input
                  type="text"
                  required
                  placeholder="Booking inquiry, corporate rate, support..."
                  value={formData.subject}
                  onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                  className="w-full bg-theme-secondary border border-theme rounded-xl px-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Message *</label>
              <textarea
                rows="5"
                required
                placeholder="Write your detailed message or inquiry here..."
                value={formData.message}
                onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                className="w-full bg-theme-secondary border border-theme rounded-xl px-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-theme-primary font-bold text-sm shadow-xl disabled:opacity-50 flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  <span>Sending Message...</span>
                </>
              ) : (
                <>
                  <Send className="w-4 h-4" />
                  <span>Submit Message</span>
                </>
              )}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};

export default ContactPage;