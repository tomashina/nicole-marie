<?php
// Heading
$_['heading_title'] = 'Nicole Marie – Anchor prices';

// Text
$_['text_extension'] = 'Extensions';
$_['text_list'] = 'Anchor price register';
$_['text_edit'] = 'Edit anchor price';
$_['text_filter'] = 'Filters';
$_['text_publications'] = 'Daily price lists';
$_['text_settings'] = 'Settings and automation';
$_['text_no_results'] = 'No records found.';
$_['text_all_statuses'] = 'All statuses';
$_['text_status_confirmed'] = 'Confirmed';
$_['text_status_pending'] = 'Pending review';
$_['text_status_disabled'] = 'Disabled';
$_['text_system'] = 'System';
$_['text_missing_count'] = 'Active products without an anchor price: %s';
$_['text_success_edit'] = 'Success: The anchor price and audit trail have been updated.';
$_['text_success_sync'] = 'Success: %s missing anchor price(s) were created.';
$_['text_success_publish'] = 'Success: The price list was published: %s';
$_['text_success_settings'] = 'Success: The anchor price settings were saved.';
$_['text_success_import'] = 'CSV import completed: %s updated and %s unchanged records.';
$_['text_reference_rule'] = 'Products present on or before 10 September 2026 use 2026-09-10. Later products use their first publication date.';
$_['text_cron_help'] = 'Call this URL every day before 08:00 Europe/Zagreb and send the key in the X-Anchor-Price-Key HTTP header. It publishes one daily web-shop price list.';
$_['text_audit'] = 'Audit trail';
$_['text_csv_exchange'] = 'Bulk CSV entry';
$_['text_csv_exchange_help'] = 'Export the current records, edit prices, the reference date or status, then upload the CSV. Product ID is required and must be unique. The complete import is rejected when any row is invalid.';

// Columns
$_['column_product'] = 'Product';
$_['column_model'] = 'Model / SKU';
$_['column_net_price'] = 'Net anchor price';
$_['column_gross_price'] = 'Gross anchor price';
$_['column_reference_date'] = 'Reference date';
$_['column_status'] = 'Status';
$_['column_action'] = 'Action';
$_['column_location'] = 'Location';
$_['column_sequence'] = 'Sequence';
$_['column_filename'] = 'File';
$_['column_products'] = 'Products';
$_['column_published'] = 'Published';
$_['column_user'] = 'User';
$_['column_reason'] = 'Reason';
$_['column_before'] = 'Before';
$_['column_after'] = 'After';
$_['column_date_added'] = 'Date';

// Entries
$_['entry_filter_name'] = 'Product name';
$_['entry_filter_model'] = 'Model / SKU';
$_['entry_filter_status'] = 'Verification status';
$_['entry_date_from'] = 'Reference date from';
$_['entry_date_to'] = 'Reference date to';
$_['entry_price'] = 'Net amount';
$_['entry_gross_price'] = 'Gross amount';
$_['entry_reference_date'] = 'Reference date';
$_['entry_verification_status'] = 'Verification status';
$_['entry_reason'] = 'Reason for change';
$_['entry_default_unit'] = 'Default sales unit';
$_['entry_cron_url'] = 'Daily cron URL';
$_['entry_cron_key'] = 'Cron key';
$_['entry_import_file'] = 'CSV file';
$_['entry_import_reason'] = 'Bulk-change reason';

// Buttons
$_['button_filter'] = 'Filter';
$_['button_clear'] = 'Clear';
$_['button_sync'] = 'Create missing anchors';
$_['button_publish'] = 'Publish CSV price list';
$_['button_settings'] = 'Save settings';
$_['button_download'] = 'Download';
$_['button_export_csv'] = 'Export editable CSV';
$_['button_import_csv'] = 'Import and validate CSV';

// Help
$_['help_reason'] = 'Required. The reason is retained permanently in the audit trail.';
$_['help_gross_price'] = 'Gross snapshot including tax. Change it only when the retained snapshot itself needs correction.';
$_['help_default_unit'] = 'Used for all products because this shop has no structured sales-unit field. Default: kom.';
$_['help_import_reason'] = 'Required. The same reason is stored with every changed record in the audit trail.';

// Warnings and errors
$_['warning_publication_due'] = 'The daily price list has not been published by 08:00.';
$_['error_permission'] = 'Warning: You do not have permission to modify the Anchor prices module.';
$_['error_not_installed'] = 'The module tables do not exist. Install the module from Extensions first.';
$_['error_not_found'] = 'The requested anchor price was not found.';
$_['error_price'] = 'Enter a valid non-negative net amount.';
$_['error_gross_price'] = 'Enter a valid non-negative gross amount.';
$_['error_reference_date'] = 'Enter a valid date in YYYY-MM-DD format.';
$_['error_status'] = 'Select a valid verification status.';
$_['error_reason'] = 'The reason must contain between 3 and 255 characters.';
$_['error_default_unit'] = 'The default unit must contain between 1 and 16 characters.';
$_['error_file_missing'] = 'The publication file is unavailable or has passed its 30-day retention period.';
$_['error_export'] = 'The anchor-price CSV export could not be created.';
$_['error_import_file'] = 'Select a valid CSV file to import.';
$_['error_import_size'] = 'The CSV file must be smaller than 5 MB.';
$_['error_import_type'] = 'Only files with a .csv extension are accepted.';
$_['error_import_reason'] = 'The import reason must contain between 3 and 255 characters.';
