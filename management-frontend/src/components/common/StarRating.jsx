import React from 'react';
import { Star } from 'lucide-react';

export const StarRating = ({ rating = 5, maxRating = 5, size = 'sm', interactive = false, onChange }) => {
  const [hoverRating, setHoverRating] = React.useState(0);

  const getSizeClass = () => {
    switch (size) {
      case 'lg':
        return 'w-6 h-6';
      case 'md':
        return 'w-5 h-5';
      default:
        return 'w-4 h-4';
    }
  };

  const currentRating = hoverRating || rating;

  return (
    <div className="flex items-center gap-1">
      {Array.from({ length: maxRating }).map((_, index) => {
        const starValue = index + 1;
        const isFilled = starValue <= currentRating;

        return (
          <Star
            key={index}
            className={`${getSizeClass()} transition-colors ${
              interactive ? 'cursor-pointer' : ''
            } ${
              isFilled
                ? 'fill-amber-400 text-amber-400'
                : 'fill-slate-700 text-slate-600'
            }`}
            onClick={() => interactive && onChange && onChange(starValue)}
            onMouseEnter={() => interactive && setHoverRating(starValue)}
            onMouseLeave={() => interactive && setHoverRating(0)}
          />
        );
      })}
    </div>
  );
};

export default StarRating;
