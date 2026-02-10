<?php
// IMPORTANT: Load Stripe keys from environment variables or a config file not tracked by git
// Secret key starts with sk_test_ (used for server-side API calls)
// Publishable key starts with pk_test_ (used for client-side)

// Try to load from environment variables first, then fall back to config file
$stripe_secret = getenv('STRIPE_SECRET_KEY');
$stripe_publishable = getenv('STRIPE_PUBLISHABLE_KEY');

// If not in environment, try loading from config file (create this file locally and add to .gitignore)
if (!$stripe_secret && file_exists(__DIR__ . '/stripe_config.php')) {
    include __DIR__ . '/stripe_config.php';
    $stripe_secret = defined('STRIPE_SECRET_KEY_VALUE') ? STRIPE_SECRET_KEY_VALUE : '';
    $stripe_publishable = defined('STRIPE_PUBLISHABLE_KEY_VALUE') ? STRIPE_PUBLISHABLE_KEY_VALUE : '';
}

define('STRIPE_SECRET_KEY', $stripe_secret ?: 'YOUR_STRIPE_SECRET_KEY_HERE');
define('STRIPE_PUBLISHABLE_KEY', $stripe_publishable ?: 'YOUR_STRIPE_PUBLISHABLE_KEY_HERE');

function stripeRequest($endpoint, $method = 'POST', $data = []) {
    $url = 'https://api.stripe.com/v1/' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    
    if ($method === 'POST' && !empty($data)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    } else {
        $error = json_decode($response, true);
        return ['error' => $error['error']['message'] ?? 'Unknown error'];
    }
}

/**
 * Create a Stripe Checkout Session
 */
function createStripeCheckoutSession($amount, $currency, $order_id, $customer_email, $customer_name, $item_name, $success_url, $cancel_url) {
    // Convert amount to cents (Stripe uses smallest currency unit)
    $amount_cents = (int)($amount * 100);
    
    $data = [
        'payment_method_types[]' => 'card',
        'line_items[0][price_data][currency]' => strtolower($currency),
        'line_items[0][price_data][product_data][name]' => $item_name,
        'line_items[0][price_data][unit_amount]' => $amount_cents,
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'customer_email' => $customer_email,
        'metadata[order_id]' => $order_id,
        'metadata[customer_name]' => $customer_name,
    ];
    
    return stripeRequest('checkout/sessions', 'POST', $data);
}

/**
 * Retrieve a Stripe Checkout Session
 */
function getStripeCheckoutSession($session_id) {
    return stripeRequest('checkout/sessions/' . $session_id, 'GET');
}
?>

