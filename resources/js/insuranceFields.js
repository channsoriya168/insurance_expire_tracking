export const INSURANCE_FIELDS = [
    { key: 'insurance_company', label: 'ក្រុមហ៊ុនធានារ៉ាប់រង', type: 'text', required: true, section: 'ព័ត៌មានទូទៅ' },
    { key: 'policy_no', label: 'លេខបណ្ណសន្យា', type: 'text', required: true, section: 'ព័ត៌មានទូទៅ' },
    { key: 'policy_type', label: 'ប្រភេទបណ្ណសន្យា', type: 'text', required: true, section: 'ព័ត៌មានទូទៅ' },
    { key: 'insured_name', label: 'ឈ្មោះអ្នកត្រូវបានធានា', type: 'text', required: true, section: 'ព័ត៌មានទូទៅ' },

    { key: 'contact_method', label: 'មធ្យោបាយទំនាក់ទំនង', type: 'select', required: true, section: 'ព័ត៌មានទំនាក់ទំនង' },
    { key: 'contact_value', label: 'ព័ត៌មានទំនាក់ទំនង', type: 'text', required: true, section: 'ព័ត៌មានទំនាក់ទំនង' },
    { key: 'contact_person', label: 'អ្នកទំនាក់ទំនង', type: 'text', required: true, section: 'ព័ត៌មានទំនាក់ទំនង' },

    { key: 'sum_insured', label: 'ចំនួនទឹកប្រាក់ធានារ៉ាប់រង', type: 'number', required: true, section: 'ហិរញ្ញវត្ថុ' },
    { key: 'premium', label: 'បុព្វលាភធានារ៉ាប់រង', type: 'number', required: true, section: 'ហិរញ្ញវត្ថុ' },
    { key: 'revised_sum_insured', label: 'ចំនួនទឹកប្រាក់ធានារ៉ាប់រង (កែប្រែ)', type: 'number', required: false, section: 'ហិរញ្ញវត្ថុ' },
    { key: 'revised_premium', label: 'បុព្វលាភធានារ៉ាប់រង (កែប្រែ)', type: 'number', required: false, section: 'ហិរញ្ញវត្ថុ' },
    { key: 'revised_premium_rate', label: 'អត្រាបុព្វលាភ (កែប្រែ)', type: 'number', required: false, section: 'ហិរញ្ញវត្ថុ' },

    { key: 'expiry_date', label: 'កាលបរិច្ឆេទផុតកំណត់', type: 'date', required: true, section: 'កាលបរិច្ឆេទ និងស្ថានភាព' },
    { key: 'confirmed_date', label: 'កាលបរិច្ឆេទបញ្ជាក់', type: 'date', required: false, section: 'កាលបរិច្ឆេទ និងស្ថានភាព' },
    { key: 'status', label: 'ស្ថានភាព', type: 'text', required: false, section: 'កាលបរិច្ឆេទ និងស្ថានភាព' },
    { key: 'request_policy_date', label: 'កាលបរិច្ឆេទស្នើសុំបណ្ណសន្យា', type: 'date', required: false, section: 'កាលបរិច្ឆេទ និងស្ថានភាព' },
    { key: 'policy_received_date', label: 'កាលបរិច្ឆេទទទួលបណ្ណសន្យា', type: 'date', required: false, section: 'កាលបរិច្ឆេទ និងស្ថានភាព' },

    { key: 'remarks', label: 'សម្គាល់', type: 'textarea', required: false, section: 'សម្គាល់' },
];
