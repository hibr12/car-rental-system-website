import React, { useState } from 'react';

export const VehicleGallery = ({ images = [], vehicleName = 'Vehicle' }) => {
  const defaultImage = 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1200&q=80';
  
  const allImages = images.length > 0 ? images.map((img) => img.image_url) : [defaultImage];
  const [activeImage, setActiveImage] = useState(allImages[0]);

  return (
    <div className="space-y-4">
      {/* Main Preview Container */}
      <div className="relative aspect-[16/9] w-full rounded-2xl overflow-hidden bg-theme-card border border-theme shadow-2xl">
        <img
          src={activeImage}
          alt={vehicleName}
          className="w-full h-full object-cover object-center transition-all duration-300"
        />
        <div className="absolute inset-0 bg-slate-950/40 pointer-events-none" />
      </div>

      {/* Thumbnails list */}
      {allImages.length > 1 && (
        <div className="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-thin">
          {allImages.map((imgUrl, idx) => (
            <button
              key={idx}
              onClick={() => setActiveImage(imgUrl)}
              className={`relative w-24 h-16 rounded-xl overflow-hidden border-2 transition-all shrink-0 ${
                activeImage === imgUrl
                  ? 'border-blue-500 ring-2 ring-blue-500/30 scale-105'
                  : 'border-theme opacity-60 hover:opacity-100 hover:border-slate-700'
              }`}
            >
              <img src={imgUrl} alt={`${vehicleName} view ${idx + 1}`} className="w-full h-full object-cover" />
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default VehicleGallery;
