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
        \App\Share\Enums\Plan::Basic => [
            'price' => env('PLAN_BASIC_PRICE'),
            'google_item_id' => env('PLAN_BASIC_GOOGLE_ITEM_ID'),
            'apple_product_id' => env('PLAN_BASIC_APPLE_PRODUCT_ID'),
        ],
        \App\Share\Enums\Plan::Plus => [
            'price' => env('PLAN_PLUS_PRICE'),
            'google_item_id' => env('PLAN_PLUS_GOOGLE_ITEM_ID'),
            'apple_product_id' => env('PLAN_PLUS_APPLE_PRODUCT_ID'),
        ],
        \App\Share\Enums\Plan::All => [
            'price' => env('PLAN_ALL_PRICE'),
            'google_item_id' => env('PLAN_ALL_GOOGLE_ITEM_ID'),
            'apple_product_id' => env('PLAN_ALL_APPLE_PRODUCT_ID'),
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
