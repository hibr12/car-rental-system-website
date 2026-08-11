import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Search, Car, Calendar, ShieldCheck, Clock, Award, Star, ArrowRight, CheckCircle2, ChevronRight, Zap } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import categoryApi from '../../api/categoryApi';
import VehicleCard from '../../components/vehicles/VehicleCard';
import { VehicleCardSkeleton } from '../../components/common/Skeleton';
import StarRating from '../../components/common/StarRating';

export const HomePage = () => {
  const navigate = useNavigate();
  const [featuredVehicles, setFeaturedVehicles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);

  // Quick Hero Search Form State
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const [vehRes, catRes] = await Promise.all([
          vehicleApi.getAll({ featured: true, per_page: 6 }),
          categoryApi.getAll(),
        ]);

        const vehList = vehRes.data || [];
        setFeaturedVehicles(vehList);

        const catList = catRes.data || [];
        setCategories(catList);
      } catch (err) {
        console.error('Failed to load homepage data:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const handleHeroSearch = (e) => {
    e.preventDefault();
    const params = new URLSearchParams();
    if (searchQuery) params.set('search', searchQuery);
    if (selectedCategory) params.set('category', selectedCategory);
    navigate(`/vehicles?${params.toString()}`);
  };

  return (
    <div className="space-y-24 pb-20">
      {/* Hero Section */}
      <section className="relative min-h-[85vh] flex items-center justify-center pt-12 pb-20 overflow-hidden">
        {/* Background Gradients & Glow */}
        <div className="absolute inset-0 bg-blue-900/5 via-theme-primary to-theme-primary pointer-events-none transition-colors duration-200" />
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-blue-600/15 blur-3xl pointer-events-none rounded-full" />

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full text-center space-y-10">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold uppercase tracking-wider backdrop-blur-md">
            <Zap className="w-3.5 h-3.5" />
            <span>Next-Generation Luxury & Performance Rentals</span>
          </div>

          <div className="space-y-4 max-w-4xl mx-auto">
            <h1 className="text-4xl sm:text-6xl lg:text-7xl font-extrabold text-theme-primary tracking-tight leading-tight">
              Drive Your Journey <br />
              <span className="gradient-text">With Absolute Confidence</span>
            </h1>
            <p className="text-base sm:text-xl text-theme-muted max-w-2xl mx-auto font-normal leading-relaxed">
              Unlock extraordinary driving experiences with our handpicked fleet of premium sedans, high-performance SUVs, and eco-friendly electric vehicles.
            </p>
          </div>

          {/* Quick Discovery Widget */}
          <div className="max-w-4xl mx-auto bg-theme-card/90 border border-theme p-4 sm:p-6 rounded-3xl shadow-2xl backdrop-blur-xl transition-colors duration-200">
            <form onSubmit={handleHeroSearch} className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div className="relative">
                <Search className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  placeholder="Brand, model, keyword..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full bg-theme-input border border-theme rounded-2xl pl-10 pr-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
                />
              </div>

              <div>
                <select
                  value={selectedCategory}
                  onChange={(e) => setSelectedCategory(e.target.value)}
                  className="w-full bg-theme-input border border-theme rounded-2xl px-4 py-3 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
                >
                  <option value="">All Categories</option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.slug}>
                      {cat.name}
                    </option>
                  ))}
                </select>
              </div>

              <button
                type="submit"
                className="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition-all shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2"
              >
                <Search className="w-4 h-4" />
                <span>Search Vehicles</span>
              </button>
            </form>
          </div>
        </div>
      </section>

      {/* Featured Vehicles Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        <div className="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-4 border-b border-theme pb-6">
          <div>
            <span className="text-xs font-extrabold uppercase tracking-wider text-blue-400">
              Curated Fleet
            </span>
            <h2 className="text-3xl font-extrabold text-theme-primary tracking-tight mt-1">
              Featured Fleet Collections
            </h2>
          </div>
          <Link
            to="/vehicles"
            className="inline-flex items-center gap-2 text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors"
          >
            <span>Explore All Vehicles</span>
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>

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
          <div className="bg-theme-card border border-theme rounded-3xl p-12 text-center space-y-4 transition-colors duration-200">
            <Car className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-theme-muted text-sm">No featured vehicles currently available.</p>
            <Link
              to="/vehicles"
              className="inline-block px-6 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-semibold"
            >
              Browse Full Catalog
            </Link>
          </div>
        )}
      </section>

      {/* How It Works Section */}
      <section className="bg-theme-secondary/60 border-y border-theme py-20 transition-colors duration-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">
          <div className="text-center space-y-3 max-w-2xl mx-auto">
            <span className="text-xs font-extrabold uppercase tracking-wider text-indigo-400">
              Seamless Process
            </span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-theme-primary tracking-tight">
              How Renting Works In 4 Easy Steps
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            {[
              {
                step: '01',
                title: 'Choose Vehicle',
                desc: 'Browse our diverse luxury fleet and pick your ideal vehicle for any occasion.',
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
                desc: 'Review pricing breakdown, submit your booking, and receive instant confirmation.',
                icon: ShieldCheck,
              },
              {
                step: '04',
                title: 'Enjoy Your Drive',
                desc: 'Pick up your keys and experience unparalleled comfort and performance.',
                icon: Award,
              },
            ].map((item, index) => {
              const Icon = item.icon;
              return (
                <div
                  key={index}
                  className="bg-theme-card border border-theme p-6 rounded-2xl relative group hover:border-blue-500/50 transition-colors duration-200"
                >
                  <span className="text-4xl font-black text-theme-muted/20 group-hover:text-blue-500/20 transition-colors absolute top-4 right-4">
                    {item.step}
                  </span>
                  <div className="w-12 h-12 rounded-xl bg-blue-600/10 text-blue-400 flex items-center justify-center mb-4 border border-blue-500/20">
                    <Icon className="w-6 h-6" />
                  </div>
                  <h3 className="text-lg font-bold text-theme-primary mb-2">{item.title}</h3>
                  <p className="text-xs text-theme-muted leading-relaxed">{item.desc}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Why Choose Us Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <div className="space-y-6">
            <span className="text-xs font-extrabold uppercase tracking-wider text-blue-400">
              The Apex Advantage
            </span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-theme-primary tracking-tight leading-tight">
              Designed For Drivers Who Expect Exceptional Quality
            </h2>
            <p className="text-theme-muted text-sm leading-relaxed">
              We eliminate rental complexities with transparent pricing, zero hidden fees, and pristine vehicles maintained to strict manufacturer standards.
            </p>

            <div className="space-y-4 pt-2">
              {[
                'Wide vehicle selection ranging from compact sedans to luxury SUVs',
                'Transparent pricing with comprehensive insurance coverage included',
                '24/7 roadside assistance & dedicated concierge support',
                'Rigorous multi-point safety inspections before every rental',
              ].map((feat, idx) => (
                <div key={idx} className="flex items-start gap-3">
                  <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                  <span className="text-sm font-medium text-theme-secondary">{feat}</span>
                </div>
              ))}
            </div>

            <div className="pt-4">
              <Link
                to="/vehicles"
                className="inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl bg-blue-600 text-white font-bold text-sm shadow-lg shadow-blue-600/25 hover:scale-105 transition-all"
              >
                <span>Find Your Ideal Vehicle</span>
                <ChevronRight className="w-4 h-4" />
              </Link>
            </div>
          </div>

          <div className="relative">
            <div className="aspect-[4/3] rounded-3xl overflow-hidden border border-theme shadow-2xl bg-theme-card transition-colors duration-200">
              <img
                src="https://images.unsplash.com/photo-1617814076367-b759c7d7e738?auto=format&fit=crop&w=1200&q=80"
                alt="Luxury Car Fleet"
                className="w-full h-full object-cover"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Testimonials / Customer Reviews */}
      <section className="bg-theme-secondary/40 border-y border-theme py-16 transition-colors duration-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          <div className="text-center space-y-2">
            <span className="text-xs font-extrabold uppercase tracking-wider text-amber-400">
              Verified Reviews
            </span>
            <h2 className="text-3xl font-extrabold text-theme-primary">What Our Drivers Say</h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {[
              {
                name: 'Alexander Wright',
                role: 'Executive Traveler',
                comment:
                  'Exceptional vehicle condition! The booking process took less than two minutes and pickup at the terminal was flawless.',
                rating: 5,
              },
              {
                name: 'Sophia Martinez',
                role: 'Family Vacationer',
                comment:
                  'Rented a 7-seater SUV for a weekend road trip. Super clean, comfortable, and the daily rates were unbeatable.',
                rating: 5,
              },
              {
                name: 'Marcus Vance',
                role: 'Business Owner',
                comment:
                  'ApexRentals has been my go-to car rental service for over a year now. Truly professional staff and great customer service.',
                rating: 5,
              },
            ].map((rev, idx) => (
              <div
                key={idx}
                className="bg-theme-card border border-theme p-6 rounded-2xl space-y-4 relative transition-colors duration-200"
              >
                <StarRating rating={rev.rating} size="sm" />
                <p className="text-xs text-theme-secondary italic leading-relaxed">"{rev.comment}"</p>
                <div className="pt-2 border-t border-theme flex items-center justify-between">
                  <div>
                    <h4 className="text-xs font-bold text-theme-primary">{rev.name}</h4>
                    <p className="text-[11px] text-theme-muted">{rev.role}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Call To Action */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="bg-blue-600 rounded-3xl p-8 sm:p-12 text-center text-white space-y-6 shadow-2xl relative overflow-hidden">
          <div className="relative z-10 max-w-2xl mx-auto space-y-4">
            <h2 className="text-3xl sm:text-5xl font-extrabold tracking-tight">Ready To Hit The Open Road?</h2>
            <p className="text-blue-100 text-sm sm:text-base">
              Choose from hundreds of available vehicles and start your rental reservation in seconds.
            </p>
            <div className="pt-4 flex flex-wrap items-center justify-center gap-4">
              <Link
                to="/vehicles"
                className="px-8 py-4 rounded-2xl bg-white text-slate-950 font-bold text-sm shadow-xl hover:bg-slate-100 transition-transform hover:scale-105"
              >
                Browse All Vehicles
              </Link>
              <Link
                to="/contact"
                className="px-8 py-4 rounded-2xl bg-slate-950/40 border border-white/20 text-white font-semibold text-sm hover:bg-slate-950/60 transition-colors"
              >
                Contact Customer Support
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default HomePage;
