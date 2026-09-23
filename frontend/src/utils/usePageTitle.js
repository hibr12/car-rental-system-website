import { useEffect } from 'react';
import { contactInfo } from '../config/contactInfo';

/** Sets the browser tab title, e.g. "Vehicles | Abay Car Rentals". */
export default function usePageTitle(title) {
  useEffect(() => {
    document.title = title ? `${title} | ${contactInfo.companyName}` : contactInfo.companyName;
  }, [title]);
}
