import React from 'react';
import { Link } from 'react-router-dom';
import { legalDocuments } from './legalContent';

const OTHER_LINKS = [
  { doc: 'privacy', to: '/privacy', label: 'Privacy Policy' },
  { doc: 'terms', to: '/terms', label: 'Terms & Conditions' },
  { doc: 'rentalAgreement', to: '/rental-agreement', label: 'Rental Agreement' },
];

const LegalPage = ({ doc }) => {
  const content = legalDocuments[doc];

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-10">
      <header className="space-y-2">
        <h1 className="text-3xl sm:text-4xl font-extrabold text-theme-primary tracking-tight">
          {content.title}
        </h1>
        <p className="text-sm text-theme-muted">{content.updated}</p>
      </header>

      <div className="space-y-8">
        {content.sections.map((section) => (
          <section key={section.heading} className="space-y-3">
            <h2 className="text-lg font-bold text-theme-primary">{section.heading}</h2>
            {section.intro && (
              <p className="text-theme-secondary leading-relaxed">{section.intro}</p>
            )}
            {section.body && (
              <p className="text-theme-secondary leading-relaxed">{section.body}</p>
            )}
            {section.items && (
              <ul className="list-disc pl-5 space-y-2 text-theme-secondary leading-relaxed">
                {section.items.map((item) => <li key={item}>{item}</li>)}
              </ul>
            )}
          </section>
        ))}
      </div>

      <nav className="pt-6 border-t border-theme flex flex-wrap gap-x-6 gap-y-2 text-sm">
        {OTHER_LINKS.filter((l) => l.doc !== doc).map((l) => (
          <Link key={l.to} to={l.to} className="font-semibold text-blue-500 hover:text-blue-400">
            {l.label}
          </Link>
        ))}
      </nav>
    </div>
  );
};

export default LegalPage;
