# Stripe Payment Setup Guide

## Quick Setup (5 minutes)

### Step 1: Get Your Stripe API Keys

1. Sign up for a free Stripe account at      
2. Go to https://dashboard.stripe.com/apikeys
3. You'll see two keys:
   - **Publishable key** (starts with `pk_test_`)
   - **Secret key** (starts with `sk_test_`)

### Step 2: Configure Your Keys

1. Open `stripe_helper.php`
2. Replace these lines with your actual keys:

```php
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY_HERE');
```

### Step 3: Test the Payment

Use these test card numbers in Stripe Checkout:

**Successful Payment:**
- Card Number: `4242 4242 4242 4242`
- Expiry: Any future date (e.g., `12/25`)
- CVC: Any 3 digits (e.g., `123`)
- ZIP: Any 5 digits (e.g., `12345`)

**Declined Payment (for testing failures):**
- Card Number: `4000 0000 0000 0002`

**3D Secure Authentication (for testing 3DS):**
- Card Number: `4000 0025 0000 3155`

## How It Works

1. User clicks "Proceed to Secure Payment" on `pay.php`
2. They're redirected to Stripe's secure checkout page
3. After payment, Stripe redirects back to `redirect.php`
4. The system verifies the payment and updates the order status
5. User sees the success page

## Important Notes

- **Test Mode**: The keys starting with `pk_test_` and `sk_test_` are for testing only
- **No Real Charges**: Test mode doesn't charge real money
- **Production**: When ready for real payments, switch to live keys (`pk_live_` and `sk_live_`)
- **Currency**: Currently set to RWF (Rwandan Franc). Change in `pay.php` if needed.

## Troubleshooting

**Error: "Invalid API Key"**
- Make sure you copied the full key (they're long!)
- Check for extra spaces before/after the key
- Ensure you're using test keys in test mode

**Payment not processing**
- Check that cURL is enabled in PHP: `php -m | grep curl`
- Verify your server can make HTTPS requests to `api.stripe.com`
- Check PHP error logs for details

## Support

- Stripe Documentation: https://stripe.com/docs
- Stripe Dashboard: https://dashboard.stripe.com
- Test Cards: https://stripe.com/docs/testing

