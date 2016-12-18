<?php

require "./vendor/autoload.php";
require "./src/Calibr/MSReceiptValidator/Validator.php";

use Calibr\MSReceiptValidator\Validator;
use Calibr\MSReceiptValidator\ValidationFailedException;
use Calibr\MSReceiptValidator\MalformedReceiptException;

class ValidatorTest extends PHPUnit_Framework_TestCase {
  public function testValidReceipt() {
    $receiptString = file_get_contents(__DIR__."/valid-receipt.xml");
    $validator = new Validator();
    $receipt = $validator->load($receiptString);

    $this->assertEquals($receipt->date, "2012-08-30T23:10:05Z");
    $this->assertEquals($receipt->deviceId, "4e362949-acc3-fe3a-e71b-89893eb4f528");

    $this->assertEquals($receipt->productReceipt->id, "6bbf4366-6fb2-8be8-7947-92fd5f683530");
    $this->assertEquals($receipt->productReceipt->appId, "55428GreenlakeApps.CurrentAppSimulatorEventTest_z7q3q7z11crfr");
    $this->assertEquals($receipt->productReceipt->productId, "Product1");
    $this->assertEquals($receipt->productReceipt->purchaseDate, "2012-08-30T23:08:52Z");
    $this->assertEquals($receipt->productReceipt->productType, "Durable");
    $this->assertEquals($receipt->productReceipt->expirationDate, "2012-09-02T23:08:49Z");

    $this->assertEquals($receipt->appReceipt->id, "8ffa256d-eca8-712a-7cf8-cbf5522df24b");
    $this->assertEquals($receipt->appReceipt->appId, "55428GreenlakeApps.CurrentAppSimulatorEventTest_z7q3q7z11crfr");
    $this->assertEquals($receipt->appReceipt->purchaseDate, "2012-06-04T23:07:24Z");
    $this->assertEquals($receipt->appReceipt->licenseType, "Full");
  }

  public function testInvalidSignature() {
    $receiptString = file_get_contents(__DIR__."/invalid-signature.xml");
    $validator = new Validator();
    try {
      $receipt = $validator->load($receiptString);
      throw new Exception("Failed");
    }
    catch(ValidationFailedException $ex) {
    }
  }

  public function testMalformedReceipt() {
    $receiptString = file_get_contents(__DIR__."/receipt-without-certificateid.xml");
    $validator = new Validator();
    try {
      $receipt = $validator->load($receiptString);
      throw new Exception("Failed");
    }
    catch(MalformedReceiptException $ex) {
    }
  }
}