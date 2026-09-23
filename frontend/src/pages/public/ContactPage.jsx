import React, { useState } from 'react';
import { Mail, Phone, MapPin, Send, CheckCircle2, Loader2, MessageSquare, Clock } from 'lucide-react';
import contactApi from '../../api/contactApi';
import { useToast } from '../../components/common/Toast';
import { contactInfo } from '../../config/contactInfo';

const LinkedinIcon = () => (
  <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
  </svg>
);

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
          Local Support Team
        </span>
        <h1 className="text-3xl sm:text-5xl font-extrabold text-theme-primary tracking-tight">
          Get In Touch With {contactInfo.companyName}
        </h1>
        <p className="text-theme-muted text-sm sm:text-base leading-relaxed">
          Have questions about our fleet, booking policies, or need assistance? Fill out the form below or reach our team directly.
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
              <p className="text-xs text-theme-muted mt-1">Available during business hours</p>
            </div>
            <a href={contactInfo.phoneLink} className="text-sm font-semibold text-blue-400 hover:text-blue-500 transition-colors">
              {contactInfo.phone}
            </a>
          </div>

          <div className="bg-theme-card border border-theme p-6 rounded-3xl space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-indigo-600/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
              <Mail className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-base font-bold text-theme-primary">Email Inquiries</h3>
              <p className="text-xs text-theme-muted mt-1">We typically reply within 24 hours</p>
            </div>
            <a href={contactInfo.emailLink} className="text-sm font-semibold text-indigo-400 hover:text-indigo-500 transition-colors">
              {contactInfo.email}
            </a>
          </div>

          <div className="bg-theme-card border border-theme p-6 rounded-3xl space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-purple-600/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
              <MapPin className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-base font-bold text-theme-primary">Our Location</h3>
              <p className="text-xs text-theme-muted mt-1">Bahir Dar, Amhara Region</p>
            </div>
            <p className="text-xs text-theme-muted">{contactInfo.fullAddress}</p>
          </div>

          {contactInfo.linkedin && (
          <div className="bg-theme-card border border-theme p-6 rounded-3xl space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-[#0A66C2]/10 text-[#0A66C2] flex items-center justify-center border border-[#0A66C2]/20">
              <LinkedinIcon className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-base font-bold text-theme-primary">LinkedIn</h3>
              <p className="text-xs text-theme-muted mt-1">Connect with our founder</p>
            </div>
            <a
              href={contactInfo.linkedin}
              target="_blank"
              rel="noopener noreferrer"
              className="text-sm font-semibold text-[#0A66C2] hover:text-[#004182] transition-colors"
            >
              {contactInfo.linkedinLabel}
            </a>
          </div>
          )}
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
                  placeholder="+251 92 667 3294"
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
                  placeholder="Booking inquiry, vehicle availability, support..."
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