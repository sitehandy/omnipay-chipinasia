# Omnipay: CHIP

**CHIP payment gateway for Omnipay payment processing library with advanced webhook verification**

[![Latest Stable Version](https://img.shields.io/packagist/v/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)
[![Total Downloads](https://img.shields.io/packagist/dt/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)
[![License](https://img.shields.io/packagist/l/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment processing library for PHP. This package implements CHIP payment gateway support for Omnipay with comprehensive webhook verification including both HMAC and RSA signature validation.

## Features

- ✅ **Dual Signature Verification**: Support for both HMAC (webhook secret) and RSA (public key) signature verification
- ✅ **Multi-Currency Support**: MYR, SGD, USD, THB, VND, IDR
- ✅ **Advanced Purchase Options**: Due dates, timezones, creator agents, and custom metadata
- ✅ **Comprehensive Error Handling**: Detailed error messages and proper exception handling
- ✅ **Modern PHP Support**: PHP 8.0-8.4 with strict typing and modern practices
- ✅ **Extensive Testing**: 56 tests with 111 assertions covering all functionality
- ✅ **Laravel Integration**: Ready-to-use examples for Laravel applications

## Requirements

- PHP 8.0 or higher (8.0, 8.1, 8.2, 8.3, 8.4 supported)
- cURL extension
- JSON extension
- OpenSSL extension (for RSA signature verification)
- Omnipay 3.x

## Installation

Omnipay is installed via Composer. To install, simply require league/omnipay and sitehandy/omnipay-chipinasia with Composer:

```bash
composer require league/omnipay sitehandy/omnipay-chipinasia
```

**Note:** Composer will automatically select the latest stable version. If you need a specific version, you can specify it, but using version constraints like ^1.0 may cause warnings.

### Version Information
- **v2.0.0**: Enhanced release with dual signature verification (HMAC + RSA), expanded currency support, and modern PHP 8.0+ features
- **Requirements**: PHP 8.0+ with cURL, JSON, and OpenSSL extensions
- **Compatibility**: Omnipay 3.x framework with comprehensive webhook verification

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

### CHIP Account Setup

To begin, you need to:
1. Open an account at [CHIP](https://www.chip-in.asia)
2. Obtain your API credentials (API Key and Brand ID)
3. Configure your webhook endpoints with either:
   - **Webhook Secret** for HMAC-SHA256 verification (recommended for most use cases)
   - **Public Key** for RSA-SHA256 verification (enhanced security)
4. Set up your return URLs for different payment outcomes

## Quick Start

```php
use Omnipay\Omnipay;

// Initialize the gateway
$gateway = Omnipay::create('ChipInAsia');
$gateway->setApiKey('your_api_key_here');
$gateway->setBrandId('your_brand_id_here');
$gateway->setTestMode(true); // Use false for production

// Configure webhook verification (choose one method)
$gateway->setWebhookSecret('your_webhook_secret'); // For HMAC verification
// OR
// $gateway->setWebhookPublicKey($publicKey); // For RSA verification

// Create a payment with enhanced options
$response = $gateway->purchase([
    'amount' => '50.00',
    'currency' => 'MYR',
    'transactionId' => 'ORDER-' . time(),
    'description' => 'Product Purchase',
    'returnUrl' => 'https://yoursite.com/payment/return',
    'cancelUrl' => 'https://yoursite.com/payment/cancel',
    'failureUrl' => 'https://yoursite.com/payment/failure',
    'webhookUrl' => 'https://yoursite.com/payment/webhook',
    
    // Customer details
    'clientDetails' => [
        'email' => 'customer@example.com',
        'phone' => '+60123456789',
        'fullName' => 'John Doe',
    ],
    
    // Product information
    'purchaseDetails' => [
        'products' => [
            [
                'name' => 'Premium Product',
                'price' => 5000, // Amount in cents
                'quantity' => 1,
                'description' => 'High-quality premium product'
            ]
        ]
    ],
    
    // Advanced options
    'dueDate' => date('Y-m-d H:i:s', strtotime('+1 hour')),
    'timezone' => 'Asia/Kuala_Lumpur',
    'creatorAgent' => 'MyApp/1.0'
])->send();

if ($response->isRedirect()) {
    // Store transaction reference for later verification
    $_SESSION['purchase_id'] = $response->getTransactionReference();
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

// Thai Baht
$response = $gateway->purchase([
    'amount' => '400.00',
    'currency' => 'THB',
    // ... other parameters
]);

// Vietnamese Dong
$response = $gateway->purchase([
    'amount' => '280000.00',
    'currency' => 'VND',
    // ... other parameters
]);

// Indonesian Rupiah
$response = $gateway->purchase([
    'amount' => '180000.00',
    'currency' => 'IDR',
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

#### Gateway Configuration
| Parameter | Description | Required | Type |
|-----------|-------------|----------|------|
| `apiKey` | Your CHIP API key | Yes | string |
| `brandId` | Your CHIP Brand ID | Yes | string |
| `testMode` | Set to true for testing environment | No | boolean |
| `webhookSecret` | Webhook secret for HMAC verification | No* | string |
| `webhookPublicKey` | Public key for RSA verification | No* | string |
| `creatorAgent` | User agent identifier for API calls | No | string |

*Either `webhookSecret` or `webhookPublicKey` is required for webhook verification.

#### Purchase Parameters
| Parameter | Description | Required | Type |
|-----------|-------------|----------|------|
| `amount` | Payment amount | Yes | string/float |
| `currency` | Payment currency (MYR, SGD, USD, THB, VND, IDR) | Yes | string |
| `transactionId` | Your unique transaction identifier | Yes | string |
| `description` | Payment description | No | string |
| `returnUrl` | Success redirect URL | No | string |
| `cancelUrl` | Cancel redirect URL | No | string |
| `failureUrl` | Failure redirect URL | No | string |
| `webhookUrl` | Webhook notification URL | No | string |
| `dueDate` | Payment due date (ISO 8601 format) | No | string |
| `timezone` | Timezone for due date | No | string |
| `clientDetails` | Customer information object | No | array |
| `purchaseDetails` | Product/purchase information object | No | array |
| `metadata` | Additional custom data | No | array |

#### Client Details Structure
| Field | Description | Type |
|-------|-------------|------|
| `email` | Customer email address | string |
| `phone` | Customer phone number | string |
| `fullName` | Customer full name | string |

#### Purchase Details Structure
| Field | Description | Type |
|-------|-------------|------|
| `products` | Array of product objects | array |
| `products[].name` | Product name | string |
| `products[].price` | Product price in cents | integer |
| `products[].quantity` | Product quantity | integer |
| `products[].description` | Product description | string |

## Webhooks

CHIP sends webhook notifications for payment status updates. This package supports both HMAC and RSA signature verification methods for enhanced security.

### Webhook Handler with HMAC Verification

```php
use Omnipay\ChipInAsia\Message\WebhookRequest;
use Omnipay\ChipInAsia\Exception\WebhookException;

public function handleWebhook(Request $request)
{
    try {
        // Create webhook request with HMAC verification
        $webhookRequest = new WebhookRequest(
            $this->getHttpClient(),
            $request
        );
        
        // Set webhook secret for HMAC verification
        $webhookRequest->setWebhookSecret(config('payment.chip.webhook_secret'));
        
        // Verify and parse webhook data
        $data = $webhookRequest->getData();
        
        // Process the verified webhook data
        $this->processWebhookData($data);
        
        return response('OK', 200);
        
    } catch (WebhookException $e) {
        Log::error('Webhook verification failed: ' . $e->getMessage());
        return response('Unauthorized', 401);
    } catch (\Exception $e) {
        Log::error('Webhook processing error: ' . $e->getMessage());
        return response('Internal Server Error', 500);
    }
}
```

### Webhook Handler with RSA Verification

```php
public function handleWebhookRSA(Request $request)
{
    try {
        // Create webhook request with RSA verification
        $webhookRequest = new WebhookRequest(
            $this->getHttpClient(),
            $request
        );
        
        // Set public key for RSA verification
        $publicKey = config('payment.chip.webhook_public_key');
        $webhookRequest->setWebhookPublicKey($publicKey);
        
        // Verify and parse webhook data
        $data = $webhookRequest->getData();
        
        // Process the verified webhook data
        $this->processWebhookData($data);
        
        return response('OK', 200);
        
    } catch (WebhookException $e) {
        Log::error('RSA webhook verification failed: ' . $e->getMessage());
        return response('Unauthorized', 401);
    }
}

private function processWebhookData(array $data)
{
    $purchaseId = $data['id'];
    $status = $data['status'];
    
    // Get additional webhook response details
    $webhookResponse = new \Omnipay\ChipInAsia\Message\WebhookResponse(null, $data);
    
    if ($webhookResponse->isPaymentSuccessful()) {
        // Update order status to paid
        Order::where('purchase_id', $purchaseId)->update([
            'status' => 'paid',
            'payment_method' => $webhookResponse->getPaymentMethod(),
            'payment_date' => $webhookResponse->getPaymentDate(),
            'amount' => $webhookResponse->getAmount(),
            'currency' => $webhookResponse->getCurrency()
        ]);
        
        // Send confirmation email
        $order = Order::where('purchase_id', $purchaseId)->first();
        Mail::to($order->customer_email)->send(new PaymentConfirmation($order));
        
    } elseif ($webhookResponse->isPaymentCancelled()) {
        // Handle cancelled payment
        Order::where('purchase_id', $purchaseId)->update(['status' => 'cancelled']);
        
    } elseif ($webhookResponse->isPaymentExpired()) {
        // Handle expired payment
        Order::where('purchase_id', $purchaseId)->update(['status' => 'expired']);
        
    } elseif ($webhookResponse->isPaymentPending()) {
        // Handle pending payment
        Order::where('purchase_id', $purchaseId)->update(['status' => 'pending']);
    }
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

### [2.0.0] - 2024-01-20
#### Added
- **Dual Signature Verification**: Support for both HMAC (webhook secret) and RSA (public key) signature verification
- **Enhanced Currency Support**: Added THB, VND, IDR to existing MYR, SGD, USD support
- **Advanced Purchase Options**: Due dates, timezones, creator agents, and enhanced metadata
- **Modern PHP Support**: Updated to require PHP 8.0+ with strict typing
- **Comprehensive Test Suite**: 56 tests with 111 assertions covering all functionality
- **Enhanced Client/Purchase Details**: Structured data models for better API integration
- **WebhookRequest/WebhookResponse Classes**: Dedicated classes for webhook handling
- **WebhookVerifier Class**: Advanced signature verification with logging support
- **Exception Hierarchy**: Specific exception classes for different error types

#### Enhanced Features
- **Multi-Currency Support**: MYR, SGD, USD, THB, VND, IDR
- **Customer Information Handling**: Enhanced client details structure
- **Redirect-based Payment Flow**: Improved with better error handling
- **Real-time Payment Verification**: Enhanced webhook processing
- **Test Mode Support**: Comprehensive testing capabilities
- **Metadata Support**: Enhanced custom fields and product information
- **Security Best Practices**: RSA signature verification and enhanced validation
- **Laravel Integration**: Ready-to-use examples and service providers

#### Breaking Changes
- Minimum PHP version increased to 8.0
- `card` parameter replaced with `clientDetails` for customer information
- Enhanced webhook verification requires either `webhookSecret` or `webhookPublicKey`
- Updated response methods for better type safety

### [1.0.0] - 2024-01-15
#### Added
- Initial release of Omnipay CHIP gateway
- Support for purchase and completePurchase operations
- Full integration with CHIP API
- Basic test suite
- Laravel integration examples
- Webhook handling support
- Exception handling classes
- Basic webhook signature verification

### Future Releases
- Additional payment methods (QR codes, bank transfers)
- Enhanced reporting and analytics
- Subscription and recurring payment support
- Improved webhook security
- Recurring payment support
- Refund functionality

## Chip-in Asia Documentation

For more information about Chip-in Asia API, visit:
- [Official Documentation](https://docs.chip-in.asia/)
- [API Reference](https://docs.chip-in.asia/chip-collect/server-api/)
- [Webhook Guide](https://docs.chip-in.asia/chip-collect/webhooks/)
- [Testing Guide](https://docs.chip-in.asia/chip-collect/testing/)