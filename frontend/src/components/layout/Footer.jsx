import React from 'react';
import { Link } from 'react-router-dom';
import { Car, Phone, Mail, MapPin, ShieldCheck, Clock, Award, CreditCard } from 'lucide-react';

export const Footer = () => {
  return (
    <footer className="bg-slate-950 border-t border-slate-800/80 text-slate-400 pt-16 pb-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-slate-800/80">
          {/* Brand Info */}
          <div className="lg:col-span-2 space-y-4">
            <Link to="/" className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                <Car className="w-5 h-5 text-white" />
              </div>
              <span className="text-xl font-bold tracking-tight text-white">
                Apex<span className="text-blue-400">Rentals</span>
              </span>
            </Link>
            <p className="text-sm text-slate-400 leading-relaxed max-w-sm">
              Experience unparalleled luxury and performance. Book your dream vehicle with our premium fleet management and zero-hassle online rental process.
            </p>
            <div className="flex items-center gap-6 pt-2 text-xs font-semibold text-slate-300">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-emerald-400" />
                <span>Fully Insured</span>
              </div>
              <div className="flex items-center gap-2">
                <Clock className="w-4 h-4 text-blue-400" />
                <span>24/7 Support</span>
              </div>
              <div className="flex items-center gap-2">
                <Award className="w-4 h-4 text-amber-400" />
                <span>Best Rate</span>
              </div>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h4 className="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">
              Quick Links
            </h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link to="/" className="hover:text-blue-400 transition-colors">
                  Home
                </Link>
              </li>
              <li>
                <Link to="/vehicles" className="hover:text-blue-400 transition-colors">
                  Browse Vehicles
                </Link>
              </li>
              <li>
                <Link to="/contact" className="hover:text-blue-400 transition-colors">
                  Contact Us
                </Link>
              </li>
              <li>
                <Link to="/login" className="hover:text-blue-400 transition-colors">
                  Customer Sign In
                </Link>
              </li>
            </ul>
          </div>

          {/* Categories */}
          <div>
            <h4 className="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">
              Vehicle Categories
            </h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link to="/vehicles?category=suv" className="hover:text-blue-400 transition-colors">
                  SUVs & Crossovers
                </Link>
              </li>
              <li>
                <Link to="/vehicles?category=sedan" className="hover:text-blue-400 transition-colors">
                  Luxury Sedans
                </Link>
              </li>
              <li>
                <Link to="/vehicles?category=sports" className="hover:text-blue-400 transition-colors">
                  Sports & Performance
                </Link>
              </li>
              <li>
                <Link to="/vehicles?category=electric" className="hover:text-blue-400 transition-colors">
                  Electric & Hybrid
                </Link>
              </li>
            </ul>
          </div>

          {/* Contact Details */}
          <div>
            <h4 className="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">
              Get In Touch
            </h4>
            <ul className="space-y-3 text-sm">
              <li className="flex items-start gap-3">
                <MapPin className="w-4 h-4 text-blue-400 shrink-0 mt-1" />
                <span>100 Premium Boulevard, Grand City</span>
              </li>
              <li className="flex items-center gap-3">
                <Phone className="w-4 h-4 text-blue-400 shrink-0" />
                <span>+1 (800) 555-APEX</span>
              </li>
              <li className="flex items-center gap-3">
                <Mail className="w-4 h-4 text-blue-400 shrink-0" />
                <span>support@apexrentals.com</span>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom copyright & payment methods */}
        <div className="pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
          <p>© {new Date().getFullYear()} ApexRentals Inc. All rights reserved.</p>
          <div className="flex items-center gap-4 text-slate-500">
            <span className="flex items-center gap-1.5">
              <CreditCard className="w-4 h-4" /> Secure Payment Encryption
            </span>
            <span>•</span>
            <span>Terms of Service</span>
            <span>•</span>
            <span>Privacy Policy</span>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
