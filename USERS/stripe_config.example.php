<?php
/**
 * Stripe Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to stripe_config.php
 * 2. Replace the placeholder values with your actual Stripe API keys
 * 3. NEVER commit stripe_config.php to git!
 * 
 * You can get your API keys from: https://dashboard.stripe.com/apikeys
 * 
 * For testing, use test keys (starting with sk_test_ and pk_test_)
 * For production, use live keys (starting with sk_live_ and pk_live_)
 */

define('STRIPE_SECRET_KEY_VALUE', 'sk_test_your_secret_key_here');
define('STRIPE_PUBLISHABLE_KEY_VALUE', 'pk_test_your_publishable_key_here');

/**
 * TEST CARD NUMBERS FOR STRIPE (use in test mode):
 * 
 * Successful payment:      4242 4242 4242 4242
 * Requires authentication: 4000 0025 0000 3155
 * Declined card:           4000 0000 0000 9995
 * 
 * For all test cards, use:
 * - Any future expiry date (e.g., 12/34)
 * - Any 3-digit CVC (e.g., 123)
 * - Any 5-digit ZIP code (e.g., 12345)
 */
