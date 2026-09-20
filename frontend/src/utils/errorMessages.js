/**
 * Frontend Error Message Utility
 * Provides user-friendly error messages for common scenarios
 */

// Map field names to human-readable labels
export const FIELD_LABELS = {
  name: 'Name',
  email: 'Email address',
  password: 'Password',
  password_confirmation: 'Password confirmation',
  phone: 'Phone number',
  countryCode: 'Country code',
  role: 'Role',
  branch_id: 'Branch',
  document_type: 'Document type',
  document_number: 'Document number',
  full_name: 'Full name',
  date_of_birth: 'Date of birth',
  license_category: 'License category',
  issue_date: 'Issue date',
  expiry_date: 'Expiry date',
  issuing_authority: 'Issuing authority',
  issuing_country: 'Issuing country',
  university_name: 'University name',
  department: 'Department',
  front_document: 'Front document',
  back_document: 'Back document',
  vehicle_id: 'Vehicle',
  start_date: 'Start date',
  end_date: 'End date',
  pickup_date: 'Pickup date',
  return_date: 'Return date',
  pickup_location: 'Pickup location',
  dropoff_location: 'Drop-off location',
  return_location: 'Return location',
  category_id: 'Category',
  brand: 'Brand',
  model: 'Model',
  year: 'Year',
  license_plate: 'License plate',
  registration_number: 'Registration number',
  vin: 'VIN',
  vin_number: 'VIN number',
  color: 'Color',
  mileage: 'Mileage',
  fuel_type: 'Fuel type',
  transmission: 'Transmission',
  seats: 'Seats',
  daily_rate: 'Daily rate',
  rental_price_per_day: 'Rental price per day',
  status: 'Status',
  code: 'Code',
  address: 'Address',
  city: 'City',
  latitude: 'Latitude',
  longitude: 'Longitude',
  opening_time: 'Opening time',
  closing_time: 'Closing time',
  manager_id: 'Manager',
  amount: 'Amount',
  payment_method: 'Payment method',
  currency: 'Currency',
  booking_id: 'Booking',
  review: 'Review',
  rating: 'Rating',
  overall_rating: 'Overall rating',
  vehicle_rating: 'Vehicle rating',
  cleanliness_rating: 'Cleanliness rating',
  staff_rating: 'Staff rating',
  value_rating: 'Value rating',
  comment: 'Comment',
  title: 'Title',
  description: 'Description',
  message: 'Message',
  subject: 'Subject',
  token: 'Reset token',
  notes: 'Notes',
  reason: 'Reason',
  admin_response: 'Admin response',
  maintenance_type: 'Maintenance type',
  cost: 'Cost',
  start_date: 'Start date',
  end_date: 'End date',
  additional_charges: 'Additional charges',
  discount: 'Discount',
};

/**
 * Get human-readable field label
 */
export function getFieldLabel(field) {
  return FIELD_LABELS[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

/**
 * Translate a raw Laravel validation message to user-friendly
 */
export function translateValidationMessage(field, message) {
  const fieldLabel = getFieldLabel(field);
  
  // Common patterns from Laravel's default messages
  const patterns = [
    // Required
    [/^The (.+) field is required\.$/, (match) => `Please enter ${fieldLabel.toLowerCase()}.`],
    [/^(.+) is required\.$/, (match) => `Please enter ${fieldLabel.toLowerCase()}.`],
    
    // String
    [/^The (.+) must be a string\.$/, (match) => `${fieldLabel} must be text.`],
    
    // Email
    [/^The (.+) must be a valid email address\.$/, () => 'Please enter a valid email address.'],
    [/^(.+) must be a valid email address\.$/, () => 'Please enter a valid email address.'],
    
    // Max length
    [/^The (.+) may not be greater than (\d+) characters\.$/, (match) => `${fieldLabel} must not exceed ${match[2]} characters.`],
    [/^(.+) must not exceed (\d+) characters\.$/, (match) => `${fieldLabel} must not exceed ${match[2]} characters.`],
    
    // Min length
    [/^The (.+) must be at least (\d+) characters\.$/, (match) => `${fieldLabel} must be at least ${match[2]} characters.`],
    [/^(.+) must be at least (\d+) characters\.$/, (match) => `${fieldLabel} must be at least ${match[2]} characters.`],
    
    // Confirmed
    [/^The (.+) confirmation does not match\.$/, (match) => `${fieldLabel} confirmation does not match.`],
    [/^(.+) confirmation does not match\.$/, (match) => `${fieldLabel} confirmation does not match.`],
    
    // Unique
    [/^The (.+) has already been taken\.$/, (match) => `An account with this ${fieldLabel.toLowerCase()} already exists. Please use a different one.`],
    [/^(.+) has already been taken\.$/, (match) => `An account with this ${fieldLabel.toLowerCase()} already exists. Please use a different one.`],
    [/^The (.+) has already been taken\./, (match) => `A ${fieldLabel.toLowerCase()} with this value already exists. Please use a different one.`],
    
    // Exists
    [/^The selected (.+) is invalid\.$/, (match) => `Please select a valid ${fieldLabel.toLowerCase()}.`],
    [/^(.+) is invalid\.$/, (match) => `Please select a valid ${fieldLabel.toLowerCase()}.`],
    
    // Date
    [/^The (.+) must be a valid date\.$/, (match) => `${fieldLabel} must be a valid date.`],
    [/^(.+) must be a valid date\.$/, (match) => `${fieldLabel} must be a valid date.`],
    [/^The (.+) must be a date before (.+)\.$/, (match) => `${fieldLabel} must be before ${match[2]}.`],
    [/^(.+) must be a date before (.+)\.$/, (match) => `${fieldLabel} must be before ${match[2]}.`],
    [/^The (.+) must be a date after (.+)\.$/, (match) => `${fieldLabel} must be after ${match[2]}.`],
    [/^(.+) must be a date after (.+)\.$/, (match) => `${fieldLabel} must be after ${match[2]}.`],
    [/^The (.+) must be a date on or before (.+)\.$/, (match) => `${fieldLabel} must be on or before ${match[2]}.`],
    [/^(.+) must be a date on or before (.+)\.$/, (match) => `${fieldLabel} must be on or before ${match[2]}.`],
    [/^The (.+) must be a date on or after (.+)\.$/, (match) => `${fieldLabel} must be on or after ${match[2]}.`],
    [/^(.+) must be a date on or after (.+)\.$/, (match) => `${fieldLabel} must be on or after ${match[2]}.`],
    
    // Numeric
    [/^The (.+) must be a number\.$/, (match) => `${fieldLabel} must be a number.`],
    [/^(.+) must be a number\.$/, (match) => `${fieldLabel} must be a number.`],
    [/^The (.+) must be an integer\.$/, (match) => `${fieldLabel} must be a whole number.`],
    [/^(.+) must be an integer\.$/, (match) => `${fieldLabel} must be a whole number.`],
    
    // File
    [/^The (.+) must be a file\.$/, (match) => `Please upload a valid file for ${fieldLabel.toLowerCase()}.`],
    [/^(.+) must be a file\.$/, (match) => `Please upload a valid file for ${fieldLabel.toLowerCase()}.`],
    [/^The (.+) must have a valid file type\.$/, (match) => `${fieldLabel} must be a valid file type.`],
    [/^(.+) must have a valid file type\.$/, (match) => `${fieldLabel} must be a valid file type.`],
    
    // Max file size
    [/^The (.+) may not be greater than (\d+) kilobytes\.$/, (match) => `${fieldLabel} is too large. Maximum size is ${match[2]} KB.`],
    [/^(.+) may not be greater than (\d+) kilobytes\.$/, (match) => `${fieldLabel} is too large. Maximum size is ${match[2]} KB.`],
    
    // In
    [/^The selected (.+) is invalid\.$/, (match) => `Please select a valid ${fieldLabel.toLowerCase()}.`],
    [/^(.+) must be one of: (.+)\.$/, (match) => `${fieldLabel} must be one of: ${match[2]}.`],
    
    // Min/Max for numbers
    [/^The (.+) must be at least (\d+)\.$/, (match) => `${fieldLabel} must be at least ${match[2]}.`],
    [/^The (.+) may not be greater than (\d+)\.$/, (match) => `${fieldLabel} must not exceed ${match[2]}.`],
    [/^(.+) must be at least (\d+)\.$/, (match) => `${fieldLabel} must be at least ${match[2]}.`],
    [/^(.+) may not be greater than (\d+)\.$/, (match) => `${fieldLabel} must not exceed ${match[2]}.`],
  ];

  for (const [pattern, formatter] of patterns) {
    const match = message.match(pattern);
    if (match) {
      return formatter(match);
    }
  }

  // If no pattern matches, try to replace field name with label
  return message.replace(new RegExp(`\\b${field}\\b`, 'g'), fieldLabel);
}

/**
 * Translate all validation errors from backend
 */
export function translateValidationErrors(errors) {
  if (!errors || typeof errors !== 'object') {
    return [];
  }

  const translated = [];
  
  for (const [field, messages] of Object.entries(errors)) {
    if (Array.isArray(messages)) {
      for (const message of messages) {
        translated.push(translateValidationMessage(field, message));
      }
    } else if (typeof messages === 'string') {
      translated.push(translateValidationMessage(field, messages));
    }
  }
  
  return translated;
}

/**
 * Get the first validation error for a specific field
 */
export function getFieldError(errors, field) {
  if (!errors || !errors[field]) {
    return null;
  }
  
  const messages = errors[field];
  if (Array.isArray(messages) && messages.length > 0) {
    return translateValidationMessage(field, messages[0]);
  }
  
  return null;
}

/**
 * Check if there are any validation errors
 */
export function hasValidationErrors(errors) {
  return errors && Object.keys(errors).length > 0;
}

/**
 * Common error messages for specific scenarios
 */
export const COMMON_ERRORS = {
  // Auth
  emailExists: 'An account with this email already exists. Please sign in instead.',
  invalidCredentials: 'Incorrect email or password. Please check your credentials and try again.',
  emailNotFound: 'No account was found with this email. Please check your email or create an account.',
  sessionExpired: 'Your session has expired. Please sign in again.',
  invalidToken: 'This link has expired or is invalid. Please request a new one.',
  
  // General
  networkError: 'We couldn\'t complete your request right now. Please check your connection and try again.',
  serverError: 'Something went wrong on our end. Please try again later.',
  unauthorized: 'You don\'t have permission to perform this action.',
  forbidden: 'You don\'t have permission to perform this action.',
  notFound: 'The requested resource was not found.',
  tooManyRequests: 'Too many requests. Please wait a minute before trying again.',
  
  // Documents
  fileRequired: 'Please upload the required document before submitting.',
  fileTypeInvalid: 'Please upload a valid image or document file (JPEG, PNG, PDF).',
  fileTooLarge: 'The selected file is too large. Please upload a smaller file.',
  
  // Booking
  vehicleUnavailable: 'This vehicle is not available for the selected dates. Please choose another vehicle or different dates.',
  bookingConflict: 'This vehicle has already been booked for part of the selected period. Please choose different dates or another vehicle.',
  
  // Branch
  branchRequired: 'Please select a branch before continuing.',
  branchExists: 'A branch with this name already exists. Please use a different name.',
  
  // Phone
  phoneInvalid: 'Please enter a valid phone number.',
  
  // Dates
  dateInvalid: 'Please enter a valid date.',
  datePast: 'Date must be in the future.',
  dateFuture: 'Date must be in the past.',
};

/**
 * Get error message by key
 */
export function getErrorMessage(key) {
  return COMMON_ERRORS[key] || 'An error occurred. Please try again.';
}