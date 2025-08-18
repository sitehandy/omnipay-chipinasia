# Omnipay: Chip-in Asia

**Chip-in Asia payment gateway for Omnipay payment processing library**

[![Latest Stable Version](https://img.shields.io/packagist/v/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)
[![Total Downloads](https://img.shields.io/packagist/dt/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)
[![License](https://img.shields.io/packagist/l/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment processing library for PHP. This package implements Chip-in Asia payment gateway support for Omnipay.

## Requirements

- PHP 7.4 or higher
- PHP 8.0, 8.1, 8.2, 8.3, 8.4 supported
- cURL extension
- JSON extension
- Omnipay 3.x

## Installation

Omnipay is installed via Composer. To install, simply require league/omnipay and sitehandy/omnipay-chipinasia with Composer:

```bash
composer require league/omnipay sitehandy/omnipay-chipinasia
```

**Note:** Composer will automatically select the latest stable version. If you need a specific version, you can specify it, but using version constraints like ^1.0 may cause warnings.

### Version Information
- **v1.0.0**: First stable release with PHP 7.4-8.4 support, modern HTTP client, and comprehensive testing
- **Requirements**: PHP 7.4+ with cURL and JSON extensions
- **Compatibility**: Omnipay 3.x framework

## Table of Contents

- [Basic Usage](#basic-usage)
- [Quick Start](#quick-start)
- [Advanced Configuration](#advanced-configuration)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Security Best Practices](#security-best-practices)
- [Troubleshooting](#troubleshooting)
- [API Reference](#api-reference)
- [Laravel Integration](#laravel-integration)
- [Webhooks](#webhooks)
- [Contributing](#contributing)
- [Support](#support)

## Basic Usage

For general usage instructions, please see the main [Omnipay repository](https://github.com/thephpleague/omnipay).

### Chip-in Asia Account Setup

To begin, you need to:
1. Open an account at [Chip-in Asia](https://visit.my/chipinasia)
2. Obtain your API credentials (API Key and Brand ID)
3. Configure your webhook endpoints
4. Set up your return URLs

## Quick Start

```php
use Omnipay\Omnipay;

// Initialize the gateway
$gateway = Omnipay::create('ChipInAsia');
$gateway->setApiKey('your_api_key_here');
$gateway->setBrandId('your_brand_id_here');
$gateway->setTestMode(true); // Use false for production

// Create a simple payment
$response = $gateway->purchase([
    'amount' => '50.00',
    'currency' => 'MYR',
    'transactionId' => 'ORDER-' . time(),
    'description' => 'Product Purchase',
    'returnUrl' => 'https://yoursite.com/payment/return',
    'cancelUrl' => 'https://yoursite.com/payment/cancel',
    'failureUrl' => 'https://yoursite.com/payment/failure',
    'webhookUrl' => 'https://yoursite.com/payment/webhook',
    'card' => [
        'email' => 'customer@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'phone' => '+60123456789',
    ]
])->send();

if ($response->isRedirect()) {
    $response->redirect();
} else {
    echo 'Error: ' . $response->getMessage();
}
```

## Advanced Configuration

### Complete Payment with All Options

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('ChipInAsia');
$gateway->setApiKey('your_api_key_here');
$gateway->setBrandId('your_brand_id_here');
$gateway->setTestMode(true);

$response = $gateway->purchase([
    // Required parameters
    'amount' => '299.99',
    'currency' => 'MYR',
    'transactionId' => 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999),
    'description' => 'Premium Product Package - Complete package with premium features',
    'returnUrl' => 'https://yoursite.com/payment/return',
    'cancelUrl' => 'https://yoursite.com/payment/cancel',
    'failureUrl' => 'https://yoursite.com/payment/failure',
    'webhookUrl' => 'https://yoursite.com/payment/webhook',
    
    // Customer information
    'card' => [
        'email' => 'jane.smith@example.com',
        'firstName' => 'Jane',
        'lastName' => 'Smith',
        'phone' => '+60123456789',
    ],
    
    // Additional metadata
    'metadata' => [
        'product_id' => 'PROD-12345',
        'customer_tier' => 'premium',
        'promotion_code' => 'SAVE20',
        'notes' => 'Express delivery requested'
    ]
])->send();

if ($response->isRedirect()) {
    // Store transaction details before redirect
    $_SESSION['transaction_id'] = $response->getTransactionReference();
    $_SESSION['order_id'] = $options['transactionId'];
    
    $response->redirect();
} else {
    throw new Exception('Payment creation failed: ' . $response->getMessage());
}
```

### Multi-Currency Support

```php
// Malaysian Ringgit
$response = $gateway->purchase([
    'amount' => '50.00',
    'currency' => 'MYR',
    // ... other parameters
]);

// Singapore Dollar
$response = $gateway->purchase([
    'amount' => '35.00',
    'currency' => 'SGD',
    // ... other parameters
]);

// US Dollar
$response = $gateway->purchase([
    'amount' => '12.00',
    'currency' => 'USD',
    // ... other parameters
]);
```

### Completing a Purchase

After the customer completes payment and returns to your site, verify the payment:

```php
// Complete the purchase
$response = $gateway->completePurchase([
    'transactionReference' => $_GET['purchase_id'], // From Chip-in Asia callback
])->send();

if ($response->isSuccessful()) {
    // Payment successful
    echo "Payment successful!";
    echo "Transaction Reference: " . $response->getTransactionReference();
    echo "Amount: " . $response->getAmount();
    echo "Currency: " . $response->getCurrency();
    echo "Payment Method: " . $response->getPaymentMethod();
    echo "Payment Date: " . $response->getPaymentDate();
} elseif ($response->isPending()) {
    // Payment pending
    echo "Payment is pending";
} else {
    // Payment failed
    echo "Payment failed: " . $response->getMessage();
}
```

## Error Handling

### Common Error Scenarios

```php
try {
    $response = $gateway->purchase($options)->send();
    
    if ($response->isRedirect()) {
        $response->redirect();
    } else {
        // Handle payment creation failure
        $errorMessage = $response->getMessage();
        $errorCode = $response->getCode();
        
        switch ($errorCode) {
            case 'INVALID_API_KEY':
                throw new Exception('Invalid API credentials provided');
            case 'INSUFFICIENT_FUNDS':
                throw new Exception('Insufficient funds in account');
            case 'INVALID_AMOUNT':
                throw new Exception('Invalid payment amount');
            default:
                throw new Exception('Payment failed: ' . $errorMessage);
        }
    }
} catch (\Omnipay\Common\Exception\InvalidRequestException $e) {
    // Handle invalid request parameters
    echo 'Invalid request: ' . $e->getMessage();
} catch (\Exception $e) {
    // Handle general errors
    echo 'Error: ' . $e->getMessage();
}
```

### Validation Best Practices

```php
// Validate required parameters before making request
$requiredParams = ['amount', 'currency', 'transactionId', 'returnUrl'];
foreach ($requiredParams as $param) {
    if (empty($options[$param])) {
        throw new InvalidArgumentException("Missing required parameter: {$param}");
    }
}

// Validate amount format
if (!is_numeric($options['amount']) || $options['amount'] <= 0) {
    throw new InvalidArgumentException('Amount must be a positive number');
}

// Validate currency
$supportedCurrencies = ['MYR', 'SGD', 'USD'];
if (!in_array($options['currency'], $supportedCurrencies)) {
    throw new InvalidArgumentException('Unsupported currency');
}
```

### Laravel Integration

For Laravel applications, you can create a service provider or use in a controller:

```php
// In your controller
public function createPayment(Request $request)
{
    $gateway = Omnipay::create('ChipInAsia');
    $gateway->setApiKey(config('payment.chip.api_key'));
    $gateway->setBrandId(config('payment.chip.brand_id'));
    $gateway->setTestMode(config('payment.chip.test_mode'));

    $response = $gateway->purchase([
        'amount' => $request->amount,
        'currency' => 'MYR',
        'transactionId' => $request->order_id,
        'description' => $request->description,
        'returnUrl' => route('payment.success'),
        'cancelUrl' => route('payment.cancel'),
        'failureUrl' => route('payment.failure'),
        'webhookUrl' => route('payment.webhook'),
        'card' => [
            'email' => $request->email,
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'phone' => $request->phone,
        ]
    ])->send();

    if ($response->isRedirect()) {
        return redirect($response->getRedirectUrl());
    }

    return back()->withErrors(['payment' => $response->getMessage()]);
}

public function handleSuccess(Request $request)
{
    $gateway = Omnipay::create('ChipInAsia');
    $gateway->setApiKey(config('payment.chip.api_key'));

    $response = $gateway->completePurchase([
        'transactionReference' => $request->purchase_id,
    ])->send();

    if ($response->isSuccessful()) {
        // Update order status in database
        return view('payment.success', [
            'transactionId' => $response->getTransactionReference(),
            'amount' => $response->getAmount(),
        ]);
    }

    return view('payment.failed', [
        'message' => $response->getMessage()
    ]);
}
```

## Security Best Practices

### API Key Management

```php
// Store API keys securely in environment variables
$gateway->setApiKey(getenv('CHIP_API_KEY'));
$gateway->setBrandId(getenv('CHIP_BRAND_ID'));

// Never hardcode credentials in your source code
// ❌ Bad
$gateway->setApiKey('sk_live_1234567890abcdef');

// ✅ Good
$gateway->setApiKey($_ENV['CHIP_API_KEY']);
```

### Webhook Security

```php
// Verify webhook signatures to ensure authenticity
public function handleWebhook(Request $request)
{
    $signature = $request->header('X-Signature');
    $payload = $request->getContent();
    
    // Verify signature using your webhook secret
    $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return response('Unauthorized', 401);
    }
    
    // Process webhook...
}
```

### Data Validation

```php
// Sanitize and validate all input data
$amount = filter_var($request->amount, FILTER_VALIDATE_FLOAT);
if ($amount === false || $amount <= 0) {
    throw new InvalidArgumentException('Invalid amount');
}

$email = filter_var($request->email, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    throw new InvalidArgumentException('Invalid email address');
}
```

## Troubleshooting

### Common Issues

#### 1. "Invalid API Key" Error
```php
// Check if API key is correctly set
if (empty($gateway->getApiKey())) {
    throw new Exception('API key not configured');
}

// Verify API key format
if (!preg_match('/^sk_(test|live)_[a-zA-Z0-9]{32}$/', $gateway->getApiKey())) {
    throw new Exception('Invalid API key format');
}
```

#### 2. Webhook Not Receiving Data
```php
// Ensure webhook URL is publicly accessible
// Test webhook endpoint manually
curl -X POST https://yoursite.com/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'

// Check webhook URL configuration
if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
    throw new Exception('Invalid webhook URL');
}
```

#### 3. Payment Redirect Issues
```php
// Ensure return URLs are properly configured
$returnUrls = ['returnUrl', 'cancelUrl', 'failureUrl'];
foreach ($returnUrls as $urlKey) {
    if (!empty($options[$urlKey]) && !filter_var($options[$urlKey], FILTER_VALIDATE_URL)) {
        throw new Exception("Invalid {$urlKey}");
    }
}
```

### Debug Mode

```php
// Enable debug mode for detailed error information
$gateway->setTestMode(true);

// Log all API requests and responses
$gateway->setLogger(new \Monolog\Logger('chip-in-asia'));
```

### Configuration Options

| Parameter | Description | Required | Type |
|-----------|-------------|----------|------|
| `apiKey` | Your Chip-in Asia API key | Yes | string |
| `brandId` | Your Chip-in Asia Brand ID | Yes | string |
| `testMode` | Set to true for testing environment | No | boolean |
| `amount` | Payment amount | Yes | string/float |
| `currency` | Payment currency (MYR, SGD, USD) | Yes | string |
| `transactionId` | Your unique transaction identifier | Yes | string |
| `description` | Payment description | No | string |
| `returnUrl` | Success redirect URL | No | string |
| `cancelUrl` | Cancel redirect URL | No | string |
| `failureUrl` | Failure redirect URL | No | string |
| `webhookUrl` | Webhook notification URL | No | string |
| `metadata` | Additional custom data | No | array |

## Webhooks

Chip-in Asia sends webhook notifications for payment status updates. Handle webhooks in your application:

### Basic Webhook Handler

```php
public function handleWebhook(Request $request)
{
    $gateway = Omnipay::create('ChipInAsia');
    $gateway->setApiKey(config('payment.chip.api_key'));

    // Verify webhook authenticity
    $signature = $request->header('X-Signature');
    $payload = $request->getContent();
    $webhookSecret = config('payment.chip.webhook_secret');
    
    $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return response('Unauthorized', 401);
    }

    $purchaseId = $request->input('id');
    
    $response = $gateway->completePurchase([
        'transactionReference' => $purchaseId,
    ])->send();

    if ($response->isSuccessful()) {
        // Update order status to paid
        Order::where('purchase_id', $purchaseId)->update([
            'status' => 'paid',
            'payment_method' => $response->getPaymentMethod(),
            'paid_at' => $response->getPaymentDate()
        ]);
        
        // Send confirmation email
        Mail::to($order->customer_email)->send(new PaymentConfirmation($order));
    } elseif ($response->isCancelled()) {
        // Handle cancelled payment
        Order::where('purchase_id', $purchaseId)->update(['status' => 'cancelled']);
    }

    return response('OK', 200);
}
```

### Webhook Event Types

```php
// Handle different webhook events
$eventType = $request->input('event_type');

switch ($eventType) {
    case 'payment.successful':
        $this->handleSuccessfulPayment($request);
        break;
    case 'payment.failed':
        $this->handleFailedPayment($request);
        break;
    case 'payment.cancelled':
        $this->handleCancelledPayment($request);
        break;
    case 'payment.pending':
        $this->handlePendingPayment($request);
        break;
    default:
        Log::warning('Unknown webhook event type: ' . $eventType);
}
```

## API Reference

### Gateway Methods

#### `purchase(array $options)`
Create a purchase request and redirect customer to payment page.

**Parameters:**
- `amount` (string|float) - Payment amount
- `currency` (string) - Payment currency (MYR, SGD, USD)
- `transactionId` (string) - Your unique transaction identifier
- `description` (string) - Payment description
- `returnUrl` (string) - Success redirect URL
- `cancelUrl` (string) - Cancel redirect URL
- `failureUrl` (string) - Failure redirect URL
- `webhookUrl` (string) - Webhook notification URL
- `card` (array) - Customer information
- `metadata` (array) - Additional custom data

**Returns:** `PurchaseResponse`

#### `completePurchase(array $options)`
Complete/verify a purchase after customer returns from payment page.

**Parameters:**
- `transactionReference` (string) - Purchase ID from Chip-in Asia

**Returns:** `CompletePurchaseResponse`

### Response Methods

#### PurchaseResponse
- `isSuccessful()` - Always returns false (redirect required)
- `isRedirect()` - Returns true if redirect URL is available
- `getRedirectUrl()` - Get the payment page URL
- `getTransactionReference()` - Get the purchase ID
- `getMessage()` - Get error message if failed
- `getCode()` - Get error code if failed

#### CompletePurchaseResponse
- `isSuccessful()` - Returns true if payment completed successfully
- `isPending()` - Returns true if payment is pending
- `isCancelled()` - Returns true if payment was cancelled
- `getTransactionReference()` - Get the purchase ID
- `getTransactionId()` - Get your original transaction ID
- `getAmount()` - Get the payment amount
- `getCurrency()` - Get the payment currency
- `getPaymentMethod()` - Get the payment method used (fpx_bank, credit_card, etc.)
- `getPaymentDate()` - Get the payment date (ISO 8601 format)
- `getMessage()` - Get status message
- `getCode()` - Get status code
- `getData()` - Get raw response data

### Exception Classes

#### `ChipInAsiaException`
Base exception class for all Chip-in Asia related errors.

#### `InvalidRequestException`
Thrown when request parameters are invalid.

#### `ApiException`
Thrown when API returns an error response.

#### `WebhookException`
Thrown when webhook verification fails.

## Testing

### Running Tests

Run the complete test suite:

```bash
composer test
```

Run specific test categories:

```bash
# Unit tests only
./vendor/bin/phpunit --testsuite=unit

# Integration tests only
./vendor/bin/phpunit --testsuite=integration

# Test with coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Test Mode

Use test mode for development and testing:

```php
$gateway = Omnipay::create('ChipInAsia');
$gateway->setTestMode(true);
$gateway->setApiKey('sk_test_your_test_api_key');
$gateway->setBrandId('your_test_brand_id');
```

### Test Cards

Use these test card numbers in test mode:

| Card Number | Description | Expected Result |
|-------------|-------------|----------------|
| 4111111111111111 | Visa | Success |
| 4000000000000002 | Visa | Declined |
| 5555555555554444 | Mastercard | Success |
| 5200000000000007 | Mastercard | Declined |

### Mock Responses

The package includes mock responses for testing:

```php
// Test successful purchase
$mockResponse = file_get_contents(__DIR__ . '/Mock/PurchaseSuccess.txt');
$this->setMockHttpResponse($mockResponse);

// Test failed purchase
$mockResponse = file_get_contents(__DIR__ . '/Mock/PurchaseFailure.txt');
$this->setMockHttpResponse($mockResponse);
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email contact@sitehandy.com instead of using the issue tracker.

## Credits

- **Author**: [Sitehandy Solutions](https://www.sitehandy.com)
- **Website**: [www.sitehandy.com](https://www.sitehandy.com)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

For support, please contact:
- Email: contact@sitehandy.com
- GitHub Issues: [Create an issue](https://github.com/sitehandy/omnipay-chipinasia/issues)

## Changelog

### [1.0.0] - 2024-01-15
#### Added
- Initial release of Omnipay Chip-in Asia gateway
- Support for purchase and completePurchase operations
- Full integration with Chip-in Asia API
- Comprehensive test suite
- Laravel integration examples
- Webhook handling support
- Exception handling classes
- Webhook signature verification

#### Features
- Multi-currency support (MYR, SGD, USD)
- Customer information handling
- Redirect-based payment flow
- Real-time payment verification
- Test mode support
- Metadata support for custom fields
- Comprehensive error handling
- Security best practices

### Future Releases
- Enhanced error handling
- Additional payment methods
- Improved webhook security
- Recurring payment support
- Refund functionality

## Chip-in Asia Documentation

For more information about Chip-in Asia API, visit:
- [Official Documentation](https://docs.chip-in.asia/)
- [API Reference](https://docs.chip-in.asia/chip-collect/server-api/)
- [Webhook Guide](https://docs.chip-in.asia/chip-collect/webhooks/)
- [Testing Guide](https://docs.chip-in.asia/chip-collect/testing/)