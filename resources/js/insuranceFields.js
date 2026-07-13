export const INSURANCE_FIELDS = [
    { key: 'insurance_company', label: 'Insurance Company', type: 'text', required: true, section: 'General Information', placeholder: 'e.g. Forte Insurance' },
    { key: 'policy_no', label: 'Policy Number', type: 'text', required: true, section: 'General Information', placeholder: 'e.g. POL-2026-0001' },
    { key: 'policy_type', label: 'Policy Type', type: 'text', required: true, section: 'General Information', placeholder: 'e.g. Motor, Health, Property' },
    { key: 'insured_name', label: 'Insured Name', type: 'text', required: true, section: 'General Information', placeholder: 'Full name of the insured' },

    { key: 'contact_method', label: 'Contact Method', type: 'select', required: true, section: 'Contact Information' },
    { key: 'contact_value', label: 'Contact Details', type: 'text', required: true, section: 'Contact Information', placeholder: 'Phone number, email, or username' },
    { key: 'contact_person', label: 'Contact Person', type: 'text', required: false, section: 'Contact Information', placeholder: 'Who to reach out to' },

    { key: 'sum_insured', label: 'Sum Insured', type: 'number', required: true, section: 'Financial Details', placeholder: '0.00' },
    { key: 'premium', label: 'Premium', type: 'number', required: true, section: 'Financial Details', placeholder: '0.00' },
    { key: 'revised_sum_insured', label: 'Revised Sum Insured', type: 'number', required: false, section: 'Financial Details', placeholder: '0.00', advanced: true },
    { key: 'revised_premium', label: 'Revised Premium', type: 'number', required: false, section: 'Financial Details', placeholder: '0.00', advanced: true },
    { key: 'revised_premium_rate', label: 'Revised Premium Rate', type: 'number', required: false, section: 'Financial Details', placeholder: '0.00', advanced: true },

    { key: 'expiry_date', label: 'Expiry Date', type: 'date', required: true, section: 'Dates & Status' },
    { key: 'confirmed_date', label: 'Confirmed Date', type: 'date', required: false, section: 'Dates & Status', advanced: true },
    { key: 'status', label: 'Status', type: 'text', required: false, section: 'Dates & Status', placeholder: 'Pending', advanced: true, hideOnCreate: true },
    { key: 'request_policy_date', label: 'Policy Request Date', type: 'date', required: false, section: 'Dates & Status', advanced: true },
    { key: 'policy_received_date', label: 'Policy Received Date', type: 'date', required: false, section: 'Dates & Status', advanced: true },

    { key: 'remarks', label: 'Remarks', type: 'textarea', required: false, section: 'Notes', placeholder: 'Any additional notes (optional)' },
];
