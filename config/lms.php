<?php

return [
    'max_pdf_kb' => 10240,
    'max_assignment_file_kb' => 20480,
    'max_assignment_files' => 5,
    'clamscan_binary' => env('LMS_CLAMSCAN_BINARY', ''),
    'pdfinfo_binary' => env('LMS_PDFINFO_BINARY', 'pdfinfo'),
    'auto_scan_on_upload' => env('LMS_AUTO_SCAN_ON_UPLOAD', true),
    'offline_max_devices' => 2,
    'offline_license_ttl_hours' => 168,
    'offline_max_event_age_seconds' => 86400,
    'offline_clock_skew_seconds' => 300,
    'offline_quota_bytes' => 104857600,
    'assignment_reminder_window_hours' => 24,
];
