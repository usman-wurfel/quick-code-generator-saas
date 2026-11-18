<?php

return [
    'storage_path' => env('QRCODE_STORAGE_PATH', 'qrcode_files'),
    'preview_size' => 400,
    // 'export_size' => 1200,

    'market_place' => env('MARKET_PLACE', env('MARKETPLACE', 'custom')),

    'skip_install_step_1' => env('MARKET_PLACE') === 'gumroad',
];
