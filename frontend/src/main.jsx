import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import { ToastProvider } from './components/common/Toast';
import { ConfirmProvider } from './components/common/ConfirmDialog';
import './styles/globals.css';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <ToastProvider>
      <ConfirmProvider>
        <App />
      </ConfirmProvider>
    </ToastProvider>
  </React.StrictMode>
);
