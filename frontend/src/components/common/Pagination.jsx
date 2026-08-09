import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export const Pagination = ({ currentPage = 1, lastPage = 1, onPageChange, total = 0 }) => {
  if (lastPage <= 1) return null;

  const pages = [];
  const maxVisiblePages = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
  let endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);

  if (endPage - startPage + 1 < maxVisiblePages) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1);
  }

  for (let i = startPage; i <= endPage; i++) {
    pages.push(i);
  }

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 py-4 px-2">
      <div className="text-sm text-slate-400">
        Page <span className="font-semibold text-slate-200">{currentPage}</span> of{' '}
        <span className="font-semibold text-slate-200">{lastPage}</span>
        {total > 0 && <span className="ml-2">({total} total items)</span>}
      </div>

      <div className="flex items-center gap-1.5">
        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="p-2 rounded-lg border border-slate-800 bg-slate-900/60 text-slate-300 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
          aria-label="Previous Page"
        >
          <ChevronLeft className="w-4 h-4" />
        </button>

        {startPage > 1 && (
          <>
            <button
              onClick={() => onPageChange(1)}
              className="px-3 py-1.5 rounded-lg border border-slate-800 bg-slate-900/60 text-sm font-medium text-slate-300 hover:bg-slate-800 transition-colors"
            >
              1
            </button>
            {startPage > 2 && <span className="px-1 text-slate-600">...</span>}
          </>
        )}

        {pages.map((page) => (
          <button
            key={page}
            onClick={() => onPageChange(page)}
            className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
              currentPage === page
                ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/20'
                : 'border border-slate-800 bg-slate-900/60 text-slate-300 hover:bg-slate-800 hover:text-white'
            }`}
          >
            {page}
          </button>
        ))}

        {endPage < lastPage && (
          <>
            {endPage < lastPage - 1 && <span className="px-1 text-slate-600">...</span>}
            <button
              onClick={() => onPageChange(lastPage)}
              className="px-3 py-1.5 rounded-lg border border-slate-800 bg-slate-900/60 text-sm font-medium text-slate-300 hover:bg-slate-800 transition-colors"
            >
              {lastPage}
            </button>
          </>
        )}

        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage === lastPage}
          className="p-2 rounded-lg border border-slate-800 bg-slate-900/60 text-slate-300 hover:bg-slate-800 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
          aria-label="Next Page"
        >
          <ChevronRight className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
};

export default Pagination;
