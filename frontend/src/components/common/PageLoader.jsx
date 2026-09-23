import React from 'react';

/** Shown while a lazily loaded page's code downloads. */
const PageLoader = ({ fullScreen = false }) => (
  <div
    role="status"
    aria-label="Loading"
    className={`flex items-center justify-center ${fullScreen ? 'min-h-screen' : 'min-h-[50vh]'}`}
  >
    <div className="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent" />
  </div>
);

export default PageLoader;
