<?php

return [
    'default' => [
        'methods' => [
            'transfer' => [
                'enabled' => true,
                'label' => 'Transfer',
                'subtitle' => 'Bank Danamon',
            ],
            'qris' => [
                'enabled' => true,
                'label' => 'QRIS',
                'subtitle' => 'Scan QR untuk bayar',
            ],
            'midtrans' => [
                'enabled' => true,
                'label' => 'Midtrans',
                'subtitle' => 'Virtual Account',
            ],
        ],
        'transfer' => [
            'bank' => 'Danamon',
            'account_number' => '003612077192',
            'account_name' => 'Toko Shoreline',
            'owner_name' => 'Ni Luh Yaniati',
        ],
    ],
    'province_rules' => [
        'bali' => [
            'methods' => [
                'transfer' => [
                    'enabled' => true,
                    'label' => 'Transfer',
                    'subtitle' => 'Bank Danamon Bali',
                ],
                'qris' => [
                    'enabled' => true,
                    'label' => 'QRIS',
                    'subtitle' => 'Scan QRIS untuk wilayah Bali',
                ],
                'midtrans' => [
                    'enabled' => true,
                    'label' => 'Midtrans',
                    'subtitle' => 'Virtual Account dan e-payment',
                ],
            ],
            'transfer' => [
                'bank' => 'Danamon',
                'account_number' => '003612077192',
                'account_name' => 'Toko Shoreline Bali',
                'owner_name' => 'Ni Luh Yaniati',
            ],
        ],
    ],
];
