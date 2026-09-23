import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { contactInfo, seoConfig } from '../config/contactInfo';

const PUBLIC_TITLES = {
  '/vehicles': 'Vehicles',
  '/contact': 'Contact Us',
  '/login': 'Sign In',
  '/register': 'Create Account',
  '/forgot-password': 'Forgot Password',
  '/reset-password': 'Reset Password',
  '/privacy': 'Privacy Policy',
  '/terms': 'Terms & Conditions',
  '/rental-agreement': 'Rental Agreement',
  '/checkout': 'Checkout',
  '/payments/status': 'Payment Status',
  '/dashboard': 'My Dashboard',
};

const PORTAL_LABELS = {
  admin: 'Admin', manager: 'Branch Manager', branch: 'Branch', fleet: 'Fleet', staff: 'Staff',
};

const humanize = (segment) => segment
  .split('-')
  .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
  .join(' ');

function titleFor(pathname) {
  if (pathname === '/') return null;
  if (PUBLIC_TITLES[pathname]) return PUBLIC_TITLES[pathname];

  const segments = pathname.split('/').filter(Boolean);
  const [first, second] = segments;

  if (first === 'vehicles') return 'Vehicle Details';
  if (first === 'booking') return 'Booking Confirmed';
  if (first === 'dashboard') return `My ${humanize(second)}`;

  const portal = PORTAL_LABELS[first];
  if (portal) {
    if (!second) return `${portal} Dashboard`;
    if (second === 'login') return `${portal} Sign In`;
    // Ignore numeric ids, e.g. /admin/bookings/42 → "Bookings".
    const section = segments.slice(1).filter((s) => !/^\d+$/.test(s)).pop();
    return `${humanize(section)} · ${portal}`;
  }
  return 'Page Not Found';
}

/** Keeps the browser tab title in sync with the current route. */
export default function useRouteTitle() {
  const { pathname } = useLocation();

  useEffect(() => {
    const title = titleFor(pathname);
    document.title = title ? `${title} | ${contactInfo.companyName}` : seoConfig.title;
  }, [pathname]);
}
