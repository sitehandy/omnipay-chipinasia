# Omnipay: Chip-in Asia

**Chip-in Asia payment gateway for Omnipay payment processing library**

[![Latest Stable Version](https://img.shields.io/packagist/v/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)
[![Total Downloads](https://img.shields.io/packagist/dt/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)
[![License](https://img.shields.io/packagist/l/sitehandy/omnipay-chipinasia.svg?style=flat-square)](https://packagist.org/packages/sitehandy/omnipay-chipinasia)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment processing library for PHP. This package implements Chip-in Asia payment gateway support for Omnipay.

## Requirements

- PHP 7.4 or higher
- PHP 8.0, 8.1, 8.2, 8.3, 8.4 support

## Installation

Install the package via Composer:

```bash
composer require sitehandy/omnipay-chipinasia
```

## Usage

### Basic Configuration

```php
use Omnipay\Omnipay;

// Create a gateway for the Chip-in Asia Gateway
$gateway = Omnipay::create('ChipInAsia');

// Set your API credentials
$gateway->setApiKey('your_api_key_here');
$gateway->setBrandId('your_brand_id_here');
$gateway->setTestMode(false); // Set to true for testing
```

### Making a Purchase

```php
// Create a purchase request
$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'MYR',
    'transactionId' => 'ORDER123456',
    'description' => 'Order Payment',
    'returnUrl' => 'https://www.yoursite.com/payment/success',
    'cancelUrl' => 'https://www.yoursite.com/payment/cancel',
    'failureUrl' => 'https://www.yoursite.com/payment/failure',
    'webhookUrl' => 'https://www.yoursite.com/payment/webhook',
    'card' => [
        'email' => 'customer@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'phone' => '+60123456789',
    ]
])->send();

if ($response->isRedirect()) {
    // Redirect customer to payment page
    $response->redirect();
} else {
    // Payment failed
    echo $response->getMessage();
}
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
} elseif ($response->isPending()) {
    // Payment pending
    echo "Payment is pending";
} else {
    // Payment failed
    echo "Payment failed: " . $response->getMessage();
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

### Configuration Options

| Parameter | Description | Required |
|-----------|-------------|----------|
| `apiKey` | Your Chip-in Asia API key | Yes |
| `brandId` | Your Chip-in Asia Brand ID | Yes |
| `testMode` | Set to true for testing environment | No |
| `amount` | Payment amount | Yes |
| `currency` | Payment currency (e.g., MYR, SGD) | Yes |
| `transactionId` | Your unique transaction identifier | Yes |
| `description` | Payment description | No |
| `returnUrl` | Success redirect URL | No |
| `cancelUrl` | Cancel redirect URL | No |
| `failureUrl` | Failure redirect URL | No |
| `webhookUrl` | Webhook notification URL | No |

### Webhooks

Chip-in Asia sends webhook notifications for payment status updates. Handle webhooks in your application:

```php
public function handleWebhook(Request $request)
{
    $gateway = Omnipay::create('ChipInAsia');
    $gateway->setApiKey(config('payment.chip.api_key'));

    // Verify webhook authenticity (implement signature verification)
    $purchaseId = $request->input('id');
    
    $response = $gateway->completePurchase([
        'transactionReference' => $purchaseId,
    ])->send();

    if ($response->isSuccessful()) {
        // Update order status to paid
        Order::where('purchase_id', $purchaseId)->update(['status' => 'paid']);
    }

    return response('OK', 200);
}
```

## API Reference

### Gateway Methods

- `purchase(array $options)` - Create a purchase request
- `completePurchase(array $options)` - Complete/verify a purchase

### Response Methods

#### PurchaseResponse
- `isSuccessful()` - Always returns false (redirect required)
- `isRedirect()` - Returns true if redirect URL is available
- `getRedirectUrl()` - Get the payment page URL
- `getTransactionReference()` - Get the purchase ID
- `getMessage()` - Get error message if failed

#### CompletePurchaseResponse
- `isSuccessful()` - Returns true if payment completed successfully
- `isPending()` - Returns true if payment is pending
- `isCancelled()` - Returns true if payment was cancelled
- `getTransactionReference()` - Get the purchase ID
- `getTransactionId()` - Get your original transaction ID
- `getAmount()` - Get the payment amount
- `getCurrency()` - Get the payment currency
- `getPaymentMethod()` - Get the payment method used
- `getPaymentDate()` - Get the payment date
- `getMessage()` - Get status message

## Testing

Run the test suite:

```bash
composer test
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

#### Features
- Multi-currency support (MYR, SGD, etc.)
- Customer information handling
- Redirect-based payment flow
- Real-time payment verification
- Test mode support

### Future Releases
- Enhanced error handling
- Additional payment methods
- Improved webhook security

## Chip-in Asia Documentation

For more information about Chip-in Asia API, visit:
- [Official Documentation](https://docs.chip-in.asia/)
- [API Reference](https://docs.chip-in.asia/chip-collect/server-api/)