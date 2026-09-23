import React from 'react';
import { Link } from 'react-router-dom';
import { Car, MapPin, Phone, Mail } from 'lucide-react';
import { contactInfo } from '../../config/contactInfo';

const LinkedinIcon = () => (
  <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
  </svg>
);

export const Footer = () => {
  return (
    <footer className="bg-[#F8FAFC] text-[#0F172A] border-t border-[#E2E8F0]">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
          {/* 1. Brand Column */}
          <div className="space-y-4">
            <Link to="/" className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                <Car className="w-5 h-5 text-white" />
              </div>
              <span className="text-xl font-bold tracking-tight text-[#0F172A]">
                Abay<span className="text-[#2563EB]">Car Rentals</span>
              </span>
            </Link>
            <p className="text-sm text-[#64748B] leading-relaxed">
              {contactInfo.description}
            </p>
            <div className="space-y-2 pt-2 text-xs text-[#94A3B8]">
              <div className="flex items-center gap-2">
                <MapPin className="w-3.5 h-3.5 text-blue-500" />
                <span>{contactInfo.fullAddress}</span>
              </div>
              <div className="flex items-center gap-2">
                <Phone className="w-3.5 h-3.5 text-blue-500" />
                <a href={contactInfo.phoneLink} className="hover:text-[#2563EB] transition-colors">
                  {contactInfo.phone}
                </a>
              </div>
              <div className="flex items-center gap-2">
                <Mail className="w-3.5 h-3.5 text-blue-500" />
                <a href={contactInfo.emailLink} className="hover:text-[#2563EB] transition-colors">
                  {contactInfo.email}
                </a>
              </div>
            </div>
            <p className="text-xs text-[#94A3B8] pt-4">
              {contactInfo.copyright}
            </p>
          </div>

          {/* 2. Quick Links */}
          <div>
            <h4 className="text-sm font-bold uppercase tracking-wider text-[#0F172A] mb-5">
              Quick Links
            </h4>
            <ul className="space-y-3 text-sm text-[#64748B]">
              <li>
                <Link to="/" className="hover:text-[#2563EB] transition-colors">
                  Home
                </Link>
              </li>
              <li>
                <Link to="/vehicles" className="hover:text-[#2563EB] transition-colors">
                  Vehicles
                </Link>
              </li>
              <li>
                <Link to="/contact" className="hover:text-[#2563EB] transition-colors">
                  Contact Us
                </Link>
              </li>
            </ul>
          </div>

          {/* 3. Our Services */}
          <div>
            <h4 className="text-sm font-bold uppercase tracking-wider text-[#0F172A] mb-5">
              Our Services
            </h4>
            <ul className="space-y-3 text-sm text-[#64748B]">
              <li>
                <Link to="/vehicles" className="hover:text-[#2563EB] transition-colors">
                  Car Rental
                </Link>
              </li>
              <li>
                <Link to="/vehicles" className="hover:text-[#2563EB] transition-colors">
                  Long Term Rental
                </Link>
              </li>
              <li>
                <Link to="/contact" className="hover:text-[#2563EB] transition-colors">
                  Airport Pickup
                </Link>
              </li>
              <li>
                <Link to="/contact" className="hover:text-[#2563EB] transition-colors">
                  24/7 Support
                </Link>
              </li>
            </ul>
          </div>

          {/* 4. Connect */}
          {contactInfo.linkedin && (
          <div>
            <h4 className="text-sm font-bold uppercase tracking-wider text-[#0F172A] mb-5">
              Connect With Us
            </h4>
            <div className="flex gap-3">
              <a
                href={contactInfo.linkedin}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="LinkedIn"
                className="w-10 h-10 rounded-full bg-[#E2E8F0] hover:bg-[#0A66C2] flex items-center justify-center transition-colors"
              >
                <LinkedinIcon className="w-4 h-4 text-[#334155] hover:text-white" />
              </a>
            </div>
            <p className="text-xs text-[#94A3B8] pt-4">
              Follow us on LinkedIn for updates
            </p>
          </div>
          )}
        </div>

        {/* Footer Bottom Bar */}
        <div className="mt-14 pt-8 border-t border-[#E2E8F0] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#94A3B8]">
          <div />
          <div className="flex items-center gap-6">
            <Link to="/privacy" className="hover:text-[#2563EB] transition-colors">
              Privacy Policy
            </Link>
            <Link to="/terms" className="hover:text-[#2563EB] transition-colors">
              Terms & Conditions
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;