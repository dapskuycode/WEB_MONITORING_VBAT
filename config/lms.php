<?php

return [
    'max_pdf_kb' => 10240,
    'clamscan_binary' => env('LMS_CLAMSCAN_BINARY', 'clamscan'),
    'pdfinfo_binary' => env('LMS_PDFINFO_BINARY', 'pdfinfo'),
];
