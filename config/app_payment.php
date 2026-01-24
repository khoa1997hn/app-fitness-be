<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Cấu hình các gói subscription với price và item_id/product_id cho từng provider
    |
    */

    'plans' => [
        'BASIC' => [
            'price' => env('PLAN_BASIC_PRICE'),
            'google_item_id' => env('PLAN_BASIC_GOOGLE_ITEM_ID'),
            'apple_product_id' => env('PLAN_BASIC_APPLE_PRODUCT_ID'),
        ],
        'COMBO' => [
            'price' => env('PLAN_COMBO_PRICE'),
            'google_item_id' => env('PLAN_COMBO_GOOGLE_ITEM_ID'),
            'apple_product_id' => env('PLAN_COMBO_APPLE_PRODUCT_ID'),
        ],
        'PREMIUM' => [
            'price' => env('PLAN_PREMIUM_PRICE'),
            'google_item_id' => env('PLAN_PREMIUM_GOOGLE_ITEM_ID'),
            'apple_product_id' => env('PLAN_PREMIUM_APPLE_PRODUCT_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Currency
    |--------------------------------------------------------------------------
    |
    | Cấu hình currency mặc định cho payment
    |
    */

    'currency' => env('PAYMENT_CURRENCY', 'USD'),

];
