<?php
declare(strict_types=1);

// SERVER-SIDE SOURCE OF TRUTH
// The browser NEVER supplies a price. It only supplies the product ID and quantity.
$secure_products = [
    'prod_1' => [
        'name'           => 'Paracetamol 500mg - 10 tablets',
        'price'          => 107.48,
        'original_price' => 161.39,
        'discount'       => 33,
    ],
    'prod_2' => [
        'name'           => 'Vitamin C 1000mg Effervescent Tablets',
        'price'          => 247.05,
        'original_price' => 400.23,
        'discount'       => 38,
    ],
    'prod_3' => [
        'name'           => 'Hand Sanitizer Gel 500ml',
        'price'          => 414.79,
        'original_price' => 561.80,
        'discount'       => 26,
    ],
];

// Prevent bulk-order abuse
if (!defined('MAX_QUANTITY')) {
    define('MAX_QUANTITY', 10);
}
?>