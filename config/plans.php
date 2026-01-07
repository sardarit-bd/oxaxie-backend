<?php

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,
        'messages_limit' => 10,
        'documents_limit' => 1,
        'cases_limit' => 0,
        'features' => [
            'basic_ai_model' => true,
            'case_history' => false,
            'priority_support' => false,
        ],
    ],
    'pro' => [
        'name' => 'Pro',
        'price' => 9,
        'messages_limit' => -1,
        'documents_limit' => 3,
        'cases_limit' => 10,
        'features' => [
            'standard_ai_model' => true,
            'case_history' => true,
            'email_support' => true,
            'pdf_word_download' => true,
        ],
    ],
    'pro_plus' => [
        'name' => 'Pro Plus',
        'price' => 29,
        'messages_limit' => -1,
        'documents_limit' => -1,
        'cases_limit' => -1,
        'features' => [
            'advanced_ai_model' => true,
            'case_history' => true,
            'priority_support' => true,
            'all_formats_download' => true,
            'latex_export' => true,
        ],
    ],
];