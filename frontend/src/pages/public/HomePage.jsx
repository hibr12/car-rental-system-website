import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { CheckCircle2, Car, Calendar, Star, ArrowRight, Search, MapPin, Phone, Mail, FileText, Key, Shield, Compass, Users } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import categoryApi from '../../api/categoryApi';
import branchApi from '../../api/branchesApi';
import reviewApi from '../../api/reviewApi';
import VehicleCard from '../../components/vehicles/VehicleCard';
import { VehicleCardSkeleton } from '../../components/common/Skeleton';
import { contactInfo, heroContent } from '../../config/contactInfo';

const HERO_IMAGE = 'https://images.unsplash.com/photo-1750715832285-ca20adc444b6?auto=format&fit=crop&w=1920&q=85';
const WHY_IMAGE = 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1200&q=80';
const CTA_IMAGE = 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=1920&q=80';

export const HomePage = () => {
  const navigate = useNavigate();
  const [featuredVehicles, setFeaturedVehicles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [branches, setBranches] = useState([]);
  const [loading, setLoading] = useState(true);

  const [selectedBranch, setSelectedBranch] = useState('');
  const [returnBranch, setReturnBranch] = useState('');
  const [pickupDate, setPickupDate] = useState('');
  const [returnDate, setReturnDate] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [reviews, setReviews] = useState([]);

  useEffect(() => {
    // Real published reviews only. There's no site-wide endpoint, so gather
    // them per branch; any branch that fails is skipped, and with none at all
    // the section is hidden rather than filled with placeholder quotes.
    const loadReviews = async (branchList) => {
      const results = await Promise.allSettled(
        branchList.map((b) => reviewApi.getByBranch(b.id, { per_page: 10 })),
      );
      const all = results
        .filter((r) => r.status === 'fulfilled')
        .flatMap((r) => r.value.data || [])
        .filter((r) => r.comment?.trim() && Number(r.overall_rating ?? r.rating) >= 4)
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      setReviews(all.slice(0, 4));
    };

    const fetchData = async () => {
      try {
        setLoading(true);
        const [vehRes, catRes, branchRes] = await Promise.all([
          vehicleApi.getAll({ featured: true, per_page: 6 }),
          categoryApi.getAll(),
          branchApi.getAll({ status: 'active' }),
        ]);
        setFeaturedVehicles(vehRes.data || []);
        setCategories(catRes.data || []);
        setBranches(branchRes.data || []);
        loadReviews(branchRes.data || []);
      } catch {
        // Sections below render their own empty states.
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  const handleHeroSearch = (e) => {
    e.preventDefault();
    const params = new URLSearchParams();
    if (selectedBranch) params.set('branch_id', selectedBranch);
    if (returnBranch) params.set('return_branch_id', returnBranch);
    if (pickupDate) params.set('pickup_date', pickupDate);
    if (returnDate) params.set('return_date', returnDate);
    if (selectedCategory) params.set('category', selectedCategory);
    navigate(`/vehicles?${params.toString()}`);
  };

  return (
    <div className="space-y-0 pb-0">

      {/* ============================================= */}
      {/* HERO SECTION                                  */}
      {/* ============================================= */}
      {/* Dark base colour: the white headline stays readable while the photo
          loads on slow connections (it was white-on-white until then). */}
      <section className="relative min-h-screen flex flex-col justify-between overflow-hidden bg-slate-900">
        {/* Background image via inline style for maximum reliability */}
        <div
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: `url(${HERO_IMAGE})` }}
        />
        {/* Full-height scrim — nav at the top, headline/subtitle in the middle */}
        <div
          className="absolute inset-0 pointer-events-none"
          style={{
            background: 'linear-gradient(to bottom, rgba(15,23,42,0.60) 0%, rgba(15,23,42,0.40) 45%, rgba(15,23,42,0.55) 100%)',
          }}
        />

        {/* Top spacer for fixed navbar */}
        <div className="h-20 shrink-0" />

        {/* Center content — title + subtitle */}
        <div className="relative z-10 flex-1 flex flex-col items-center justify-center text-center px-4 sm:px-6 py-12">
          {/* Tagline */}
          <span className="inline-block mb-4 text-xs sm:text-sm font-bold uppercase tracking-[0.2em] text-blue-400">
            {heroContent.tagline}
          </span>

          {/* Main Title */}
          <h1 className="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold tracking-tight leading-[1.08] text-white max-w-4xl mx-auto mb-5">
            {heroContent.title}
          </h1>

          {/* Subtitle */}
          <p className="text-base sm:text-lg text-blue-50 max-w-2xl mx-auto leading-relaxed drop-shadow-[0_2px_8px_rgba(0,0,0,0.6)] font-medium">
            {heroContent.subtitle}
          </p>
        </div>
      </section>


      {/* ============================================= */}
      {/* FEATURED FLEET COLLECTIONS                    */}
      {/* ============================================= */}
      <section className="py-20 bg-white relative">
        {/* Search / Filter Bar Card — floating at top of fleet section */}
        <div className="relative z-20 -mt-10 max-w-5xl mx-auto px-4 sm:px-6">
          <form
            onSubmit={handleHeroSearch}
            className="bg-white rounded-2xl shadow-2xl p-3 flex flex-col md:flex-row items-stretch gap-3"
          >
            {/* Pick-up Location */}
            <div className="flex-1 min-w-0">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-400 px-4 pt-3">
                Pick-up Location
              </label>
              <select
                value={selectedBranch}
                onChange={(e) => setSelectedBranch(e.target.value)}
                className="w-full bg-transparent border-0 px-4 pb-3 pt-1 text-sm font-semibold text-gray-900 focus:outline-none cursor-pointer"
              >
                <option value="">Bahir Dar, Amhara, Ethiopia</option>
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>{b.name}</option>
                ))}
              </select>
            </div>

            <div className="hidden md:block w-px bg-gray-200 self-stretch my-2" />

            {/* Drop-off Location */}
            <div className="flex-1 min-w-0">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-400 px-4 pt-3">
                Drop-off Location
              </label>
              <select
                value={returnBranch}
                onChange={(e) => setReturnBranch(e.target.value)}
                className="w-full bg-transparent border-0 px-4 pb-3 pt-1 text-sm font-semibold text-gray-900 focus:outline-none cursor-pointer"
              >
                <option value="">Bahir Dar, Amhara, Ethiopia</option>
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>{b.name}</option>
                ))}
              </select>
            </div>

            <div className="hidden md:block w-px bg-gray-200 self-stretch my-2" />

            {/* Pick-up Date */}
            <div className="flex-1 min-w-0">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-400 px-4 pt-3">
                Pick-up Date
              </label>
              <input
                type="date"
                value={pickupDate}
                onChange={(e) => setPickupDate(e.target.value)}
                min={new Date().toISOString().split('T')[0]}
                className="w-full bg-transparent border-0 px-4 pb-3 pt-1 text-sm font-semibold text-gray-900 focus:outline-none cursor-pointer"
              />
            </div>

            <div className="hidden md:block w-px bg-gray-200 self-stretch my-2" />

            {/* Drop-off Date */}
            <div className="flex-1 min-w-0">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-400 px-4 pt-3">
                Return Date
              </label>
              <input
                type="date"
                value={returnDate}
                onChange={(e) => setReturnDate(e.target.value)}
                min={pickupDate || new Date().toISOString().split('T')[0]}
                className="w-full bg-transparent border-0 px-4 pb-3 pt-1 text-sm font-semibold text-gray-900 focus:outline-none cursor-pointer"
              />
            </div>

            {/* CTA Button */}
            <div className="flex items-center">
              <button
                type="submit"
                className="w-full md:w-auto px-8 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-2 whitespace-nowrap"
              >
                <Search className="w-4 h-4" />
                Search Vehicles
              </button>
            </div>
          </form>
        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Section Header */}
          <div className="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-4 mb-10">
            <div>
              <span className="text-xs font-extrabold uppercase tracking-[0.15em] text-blue-500">
                Curated Fleet
              </span>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight mt-1">
                Featured Fleet Collections
              </h2>
            </div>
            <Link
              to="/vehicles"
              className="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors group"
            >
              Explore All Vehicles
              <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
            </Link>
          </div>

          {/* Vehicle Grid */}
          {loading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              <VehicleCardSkeleton />
              <VehicleCardSkeleton />
              <VehicleCardSkeleton />
            </div>
          ) : featuredVehicles.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {featuredVehicles.map((vehicle) => (
                <VehicleCard key={vehicle.id} vehicle={vehicle} />
              ))}
            </div>
          ) : (
            <div className="bg-gray-50 border border-gray-200 rounded-3xl p-12 text-center space-y-4">
              <Car className="w-12 h-12 text-gray-300 mx-auto" />
              <p className="text-gray-400 text-sm">No featured vehicles currently available.</p>
              <Link
                to="/vehicles"
                className="inline-block px-6 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-semibold"
              >
                Browse Full Catalog
              </Link>
            </div>
          )}
        </div>
      </section>


      {/* ============================================= */}
      {/* HOW RENTING WORKS — 4 EASY STEPS              */}
      {/* ============================================= */}
      <section className="py-20 bg-gradient-to-b from-blue-50 to-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            {/* Left — Title + Description + CTA */}
            <div className="lg:col-span-4 space-y-5 lg:sticky lg:top-28">
              <span className="text-xs font-extrabold uppercase tracking-[0.15em] text-blue-500">
                Simple & Fast
              </span>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
                How Renting Works In 4 Easy Steps
              </h2>
              <p className="text-gray-500 leading-relaxed">
                Our streamlined booking process gets you behind the wheel in minutes — no paperwork, no waiting, no hassle.
              </p>
              <Link
                to="/vehicles"
                className="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors group"
              >
                View All Steps
                <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
              </Link>
            </div>

            {/* Right — 4 Step Cards */}
            <div className="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-5">
              {[
                {
                  step: '01',
                  title: 'Choose Vehicle',
                  desc: 'Browse our diverse fleet and pick your ideal vehicle for any occasion.',
                  icon: Car,
                },
                {
                  step: '02',
                  title: 'Select Dates',
                  desc: 'Choose your pickup and drop-off dates along with preferred location hubs.',
                  icon: Calendar,
                },
                {
                  step: '03',
                  title: 'Confirm Booking',
                  desc: 'Review the price breakdown and submit your booking — the branch confirms it, then you pay online or at pickup.',
                  icon: FileText,
                },
                {
                  step: '04',
                  title: 'Enjoy Your Drive',
                  desc: 'Pick up your keys and experience comfort and performance on Bahir Dar roads.',
                  icon: Key,
                },
              ].map((item) => {
                const Icon = item.icon;
                return (
                  <div
                    key={item.step}
                    className="relative bg-white rounded-2xl p-7 border border-gray-100 shadow-sm hover:shadow-md transition-all group"
                  >
                    <span className="absolute top-5 right-5 text-4xl font-black text-gray-100 select-none">
                      {item.step}
                    </span>
                    <div className="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mb-5 group-hover:bg-blue-100 transition-colors">
                      <Icon className="w-7 h-7 text-blue-600" />
                    </div>
                    <h3 className="text-lg font-bold text-gray-900 mb-2">
                      {item.title}
                    </h3>
                    <p className="text-sm text-gray-500 leading-relaxed">
                      {item.desc}
                    </p>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      </section>


      {/* ============================================= */}
      {/* WHY CHOOSE US                                 */}
      {/* ============================================= */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            {/* Left — Content */}
            <div className="space-y-6">
              <span className="text-xs font-extrabold uppercase tracking-[0.15em] text-blue-500">
                Why Choose Us
              </span>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
                Reliable Car Rentals for Bahir Dar and Beyond
              </h2>
              <p className="text-gray-500 leading-relaxed">
                We eliminate rental complexities with transparent pricing in ETB, zero hidden fees, and well-maintained vehicles for your journeys across the Amhara region.
              </p>

              <div className="space-y-4 pt-2">
                {[
                  'Wide vehicle selection from compact sedans to spacious SUVs',
                  'Transparent pricing in ETB with comprehensive insurance options',
                  'Local support team based in Bahir Dar, Amhara Region',
                  'Rigorous safety inspections before every rental',
                ].map((feat, idx) => (
                  <div key={idx} className="flex items-start gap-3">
                    <div className="mt-0.5 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                      <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                    </div>
                    <span className="text-sm font-medium text-gray-700">{feat}</span>
                  </div>
                ))}
              </div>

              <div className="pt-4">
                <Link
                  to="/vehicles"
                  className="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-600/25 transition-all group"
                >
                  Explore Our Fleet
                  <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                </Link>
              </div>
            </div>

            {/* Right — Image Card */}
            <div className="relative">
              <div className="rounded-3xl overflow-hidden shadow-2xl">
                <img
                  src={WHY_IMAGE}
                  alt="Vehicle in scenic Bahir Dar setting"
                  className="w-full h-[420px] object-cover"
                />
              </div>
              {/* Floating pill bar */}
              <div className="absolute -bottom-5 left-1/2 -translate-x-1/2 bg-white rounded-full shadow-xl px-8 py-3.5 flex items-center gap-4 whitespace-nowrap border border-gray-100">
                <span className="flex items-center gap-2 text-sm font-semibold text-gray-800">
                  <Shield className="w-4 h-4 text-blue-500" /> Safety
                </span>
                <span className="w-1 h-1 rounded-full bg-gray-300" />
                <span className="flex items-center gap-2 text-sm font-semibold text-gray-800">
                  <Users className="w-4 h-4 text-blue-500" /> Comfort
                </span>
                <span className="w-1 h-1 rounded-full bg-gray-300" />
                <span className="flex items-center gap-2 text-sm font-semibold text-gray-800">
                  <Compass className="w-4 h-4 text-blue-500" /> Freedom
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>


      {/* ============================================= */}
      {/* TESTIMONIALS + CONTACT US (side by side)      */}
      {/* ============================================= */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className={`grid grid-cols-1 gap-12 items-start ${reviews.length ? 'lg:grid-cols-2' : 'max-w-2xl mx-auto'}`}>
            {/* LEFT: real customer reviews — hidden until there are some */}
            {reviews.length > 0 && (
            <div className="space-y-8">
              <div className="text-center lg:text-left">
                <span className="text-xs font-extrabold uppercase tracking-[0.15em] text-amber-500">
                  Customer Reviews
                </span>
                <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight mt-1">
                  What Our Drivers Say
                </h2>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {reviews.map((rev) => {
                  const name = rev.user?.name || rev.customer_name || 'Customer';
                  const initials = name.split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase();
                  const rating = Math.round(Number(rev.overall_rating ?? rev.rating) || 0);
                  const vehicle = rev.vehicle ? `${rev.vehicle.brand} ${rev.vehicle.model}` : null;
                  return (
                  <div
                    key={rev.id}
                    className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
                  >
                    {/* Stars */}
                    <div className="flex gap-1 mb-4" aria-label={`${rating} out of 5 stars`}>
                      {Array.from({ length: 5 }).map((_, i) => (
                        <Star
                          key={i}
                          className={`w-4 h-4 ${i < rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300'}`}
                        />
                      ))}
                    </div>

                    {/* Quote */}
                    <p className="text-sm text-gray-600 leading-relaxed mb-5">
                      "{rev.comment}"
                    </p>

                    {/* User */}
                    <div className="flex items-center gap-3 pt-4 border-t border-gray-100">
                      <div className="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">
                        {initials}
                      </div>
                      <div>
                        <h4 className="text-sm font-bold text-gray-900">{name}</h4>
                        {vehicle && <p className="text-xs text-gray-400">Rented a {vehicle}</p>}
                      </div>
                    </div>
                  </div>
                  );
                })}
              </div>
            </div>
            )}

            {/* RIGHT: Contact Us */}
            <div className="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 h-full">
              <div className="space-y-6">
                <div>
                  <span className="text-xs font-extrabold uppercase tracking-[0.15em] text-blue-500">
                    Get In Touch
                  </span>
                  <h3 className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mt-1">
                    Contact Us
                  </h3>
                  <p className="text-gray-500 leading-relaxed mt-2">
                    Have a question or need a vehicle? Get in touch with us.
                  </p>
                </div>

                <div className="space-y-5 pt-4 border-t border-gray-100">
                  {/* Location */}
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                      <MapPin className="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-gray-900">Location</h4>
                      <p className="text-sm text-gray-500 mt-1">{contactInfo.fullAddress}</p>
                    </div>
                  </div>

                  {/* Phone */}
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                      <Phone className="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-gray-900">Phone</h4>
                      <a
                        href={contactInfo.phoneLink}
                        className="text-sm text-blue-600 hover:text-blue-700 mt-1 inline-block font-medium"
                      >
                        {contactInfo.phone}
                      </a>
                    </div>
                  </div>

                  {/* Email */}
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                      <Mail className="w-5 h-5 text-indigo-600" />
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-gray-900">Email</h4>
                      <a
                        href={contactInfo.emailLink}
                        className="text-sm text-blue-600 hover:text-blue-700 mt-1 inline-block font-medium"
                      >
                        {contactInfo.email}
                      </a>
                    </div>
                  </div>

                  {/* LinkedIn */}
                  {contactInfo.linkedin && (
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-xl bg-[#0A66C2] flex items-center justify-center shrink-0">
                      <svg className="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-gray-900">LinkedIn</h4>
                      <a
                        href={contactInfo.linkedin}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-sm text-blue-600 hover:text-blue-700 mt-1 inline-block font-medium"
                      >
                        {contactInfo.linkedinLabel}
                      </a>
                    </div>
                  </div>
                  )}
                </div>

                <div className="pt-4 border-t border-gray-100">
                  <Link
                    to="/contact"
                    className="inline-flex items-center gap-2 w-full sm:w-auto px-7 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-600/25 transition-all group justify-center"
                  >
                    Send a Message
                    <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>


      {/* ============================================= */}
      {/* CTA BANNER                                    */}
      {/* ============================================= */}
      <section className="relative py-24 overflow-hidden">
        {/* Background image via inline style */}
        <div
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: `url(${CTA_IMAGE})` }}
        />
        {/* Dark blue overlay */}
        <div
          className="absolute inset-0"
          style={{
            background:
              'linear-gradient(135deg, rgba(11,19,43,0.92) 0%, rgba(30,58,138,0.85) 100%)',
          }}
        />

        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-2xl">
            <span className="inline-block text-xs font-bold uppercase tracking-[0.2em] text-blue-300 mb-4">
              Ready To Explore Bahir Dar?
            </span>
            <h2 className="text-4xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
              Your Next Journey Starts Here
            </h2>
            <p className="text-blue-100/80 text-base sm:text-lg leading-relaxed mb-8 max-w-lg">
              Choose from our available vehicles and start your rental reservation in seconds.
            </p>
            <div className="flex flex-wrap items-center gap-4">
              <Link
                to="/vehicles"
                className="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition-all group"
              >
                Browse All Vehicles
                <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
              </Link>
              <Link
                to="/contact"
                className="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl border border-white/25 text-white font-semibold text-sm hover:bg-white/10 transition-all"
              >
                Contact Us
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default HomePage;