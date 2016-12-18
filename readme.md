# Why?

This library was created to easily validate Microsoft Store purchase receipts on the server side.

# Installation

`composer require calibr/ms-receipt-validator`

# Usage

Assume that client passes receipt to the server side and we receive it in the variable `$_POST["receiptXML"]`, validation of the receipt will look like:

```php
<?php

use Calibr\MSReceiptValidator\Validator;

$validator = new Validator();
// validation(omit exception handling)
$receipt = $validator->load($_POST["receiptXML"]);

// if we are here receipt has been successfully validated and we have all receipt data in the $receipt variable
```

# Validator#load method

Validation is run by calling `load` method on the `Validator` class instance. This method takes only one argument - receipt XML string and returns [Receipt](#receipt).

# Validator#setPublicKey method

If you want to set public key directly you need to pass the public key string to this method.

<a id="receipt"></a>
## Receipt object format

| Field          |
|----------------|
| date           |
| deviceId       |
| [productReceipt](#product-receipt) |
| [appReceipt](#app-receipt)     |
| publicKey     |
| xmlDoc ([DOMDocument](http://php.net/manual/class.domdocument.php) created from XML string) |

<a id="product-receipt"></a>
## Product receipt object format

| Field          |
|----------------|
| id           |
| appId       |
| productId |
| purchaseDate  |
| productType         |
| purchasePrice         |
| expirationDate         |

## App receipt object format
<a id="app-receipt"></a>

| Field          |
|----------------|
| id           |
| appId       |
| purchaseDate |
| licenseType  |

# Error handling

`Validator#load` method throws an exception if an error occurs. Possible exceptions:

- `Calibr\MSReceiptValidator\FailFetchPublicKeyException` - Microsoft server didn't respond or respond with an error, see details in the exception message
- `Calibr\MSReceiptValidator\MalformedReceiptException` - Receipt is invalid, see details in the exception message
- `Calibr\MSReceiptValidator\ValidationFailedException` - Receipt didn't pass signature verification process
