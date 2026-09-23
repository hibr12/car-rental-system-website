import { contactInfo } from '../../../config/contactInfo';

// Kept in step with the mobile app's legal screens (lib/screens/legal/*) so
// both clients state the same terms.
const contactLine = `${contactInfo.email} or ${contactInfo.phoneFormatted}`;

export const legalDocuments = {
  privacy: {
    title: 'Privacy Policy',
    updated: 'Last updated: September 2026',
    sections: [
      {
        heading: '1. Information We Collect',
        intro: `${contactInfo.companyName} collects the personal information needed to provide our car rental services:`,
        items: [
          'Account information: your full name, email address, and phone number when you register.',
          "Identification & verification: driver's license details, license category, expiry dates, and document photos, used to verify your identity and driving eligibility.",
          'Rental & booking data: pickup and return locations, rental dates, vehicle preferences, and transaction records.',
        ],
      },
      {
        heading: '2. How We Use Your Information',
        intro: 'We use your data only for legitimate business and regulatory purposes:',
        items: [
          'Handling vehicle reservations, pickups, inspections, and returns.',
          'Verifying driving eligibility in line with national transport regulations.',
          'Processing payments through licensed payment processors.',
          'Sending booking confirmations, status updates, and important rental alerts.',
          'Preventing fraudulent bookings and keeping our vehicles safe.',
        ],
      },
      {
        heading: '3. Data Security & Storage',
        intro: 'We protect your data with industry-standard safeguards:',
        items: [
          'All traffic between your browser and our servers is encrypted (HTTPS).',
          "Driver's license documents are stored in restricted storage and can only be viewed by you and authorized branch and fleet personnel.",
          'We never store your card or bank credentials.',
        ],
      },
      {
        heading: '4. Third-Party Services',
        intro: 'We rely on trusted third parties to operate the service:',
        items: [
          'Payment gateway (Chapa): processes online payments securely. Your payment credentials are handled by Chapa, not by us.',
          'Map services (OpenStreetMap): used to show branch locations.',
        ],
      },
      {
        heading: '5. Your Rights & Account Deletion',
        intro: 'You can access, update, and delete your personal information at any time.',
        items: [
          'You can update your details from your profile page.',
          `To delete your account, use Settings → Delete Account in the ${contactInfo.companyName} mobile app, or contact us at ${contactLine}.`,
          'Accounts with active or in-progress rentals can only be deleted after the vehicle has been returned and inspected.',
          'Once deleted, your profile, credentials, and verification documents are permanently removed.',
        ],
      },
      {
        heading: '6. Contact Us',
        body: `Questions or privacy concerns? Contact us at ${contactLine}, or visit any of our branches.`,
      },
    ],
  },

  terms: {
    title: 'Terms & Conditions',
    updated: 'Effective: September 2026',
    sections: [
      {
        heading: '1. Acceptance of Terms',
        body: `By creating an account or using ${contactInfo.companyName}, you agree to these Terms & Conditions and our rental guidelines. If you do not agree, please do not use the service.`,
      },
      {
        heading: '2. Eligibility & Account Responsibilities',
        items: [
          "You must be at least 21 years old and hold a valid, government-issued driver's license.",
          'You must keep your account information accurate and up to date.',
          'You are responsible for keeping your login credentials secure.',
          'You may not sub-lease or hand over a rented vehicle to anyone not approved by us.',
        ],
      },
      {
        heading: '3. Reservations, Rates & Payments',
        items: [
          'A vehicle is only guaranteed once your reservation has been approved and confirmed.',
          'Daily rates and charges are shown before you confirm a booking.',
          'Payments are made online through Chapa or in cash at the branch.',
          'Fuel, tolls, traffic fines, and parking penalties during the rental are the renter\'s responsibility.',
        ],
      },
      {
        heading: '4. Pickup, Inspection & Returns',
        items: [
          'The vehicle is inspected and documented at both pickup and return.',
          'Vehicles must be returned to the agreed branch at the scheduled date and time.',
          'Late returns without an approved extension may incur additional charges.',
        ],
      },
      {
        heading: '5. Cancellation',
        items: [
          'You can cancel a booking while it is awaiting payment or approval.',
          'Refunds of completed payments are reviewed and processed by our team — contact us after cancelling.',
        ],
      },
      {
        heading: '6. Account Deletion & Termination',
        items: [
          'You may request deletion of your account at any time, provided there are no pending or active rentals.',
          `${contactInfo.companyName} may suspend or terminate accounts that violate safety rules or payment obligations.`,
        ],
      },
      {
        heading: '7. Limitation of Liability',
        body: `${contactInfo.companyName} is not liable for indirect, incidental, or consequential damages resulting from service downtime, vehicle breakdowns, or events beyond our control, subject to applicable law.`,
      },
    ],
  },

  rentalAgreement: {
    title: 'Rental Agreement',
    updated: 'Summary of the key rental terms',
    sections: [
      {
        heading: 'Key terms',
        items: [
          'The renter agrees to return the vehicle in the same condition as received.',
          'The renter is responsible for all traffic fines, tolls, and parking tickets incurred during the rental period.',
          'In case of an accident, the renter must immediately notify us and the local authorities.',
          'The vehicle must not be used for racing, towing, or any illegal activity.',
          "The renter must hold a valid driver's license covering the rented vehicle's category for the entire rental.",
          'The full rental price and any additional charges are confirmed by the branch before pickup; payment is completed via Chapa or in cash at the branch.',
        ],
      },
      {
        heading: 'Signing',
        body: 'Your binding rental agreement is signed at the branch when you collect the vehicle.',
      },
    ],
  },
};
