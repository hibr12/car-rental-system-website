import React from 'react';

const AuthLoadingScreen = ({ message = 'Loading authentication...' }) => (
  <div className="flex flex-col items-center justify-center min-h-screen bg-theme-primary gap-3">
    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
    <p className="text-sm text-theme-muted">{message}</p>
  </div>
);

export default AuthLoadingScreen;
