<?php

namespace Calibr\MSReceiptValidator;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecEnc;

class Validator {
  public $sslVerifyPeer = true;
  // only for test purposes
  public $skipValidation = false;
  private $publicKey;

  public function setPublicKey($key) {
    $this->publicKey = $key;
  }

  private function fetchPublicKey($certificateId) {
    if($this->publicKey) {
      return $this->publicKey;
    }
    $ch = curl_init("https://lic.apps.microsoft.com/licensing/certificateserver/?cid=$certificateId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if(!$this->sslVerifyPeer) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    $publicKey = curl_exec($ch);
    $errno = curl_errno($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);
    if(!$publicKey) {
      throw new FailFetchPublicKeyException("$errno: $errmsg");
    }
    return $publicKey;
  }

  public function load($receiptString) {
    $doc = new \DOMDocument();

    // prepare receipt XML string
    $receiptString = str_replace(array("\n","\t", "\r"), "", $receiptString);
    $receiptString = preg_replace('/\s+/', " ", $receiptString);
    $receiptString = str_replace("> <", "><", $receiptString);

    $doc->loadXML($receiptString);

    if(!$this->skipValidation) {
      $receipt = $doc->getElementsByTagName('Receipt')->item(0);
      if(!$receipt) {
        throw new MalformedReceiptException("Couldn't find Receipt element");
      }
      $certificateId = $receipt->getAttribute('CertificateId');
      if(!$certificateId) {
        throw new MalformedReceiptException("Couldn't find certificateId");
      }
      $publicKey = $this->fetchPublicKey($certificateId);
      $objXMLSecDSig = new XMLSecurityDSig();
      $objDSig = $objXMLSecDSig->locateSignature($doc);
      if(!$objDSig) {
        throw new MalformedReceiptException("Couldn't locate signature");
      }
      $objXMLSecDSig->canonicalizeSignedInfo();
      if(!$objXMLSecDSig->validateReference()) {
        throw new MalformedReceiptException("Fail to validate reference");
      }
      $objKey = $objXMLSecDSig->locateKey();
      if(!$objKey) {
        throw new MalformedReceiptException("Fail to locate key");
      }
      $objKey->loadKey($publicKey);
      if(!$objXMLSecDSig->verify($objKey)) {
        throw new ValidationFailedException("Fail to verify signature");
      }
    }

    // fill in receipt fields
    $receipt = new Receipt();
    $receipt->publicKey = $publicKey;
    $receiptElem = $doc->getElementsByTagName("Receipt")->item(0);
    $receipt->date = $receiptElem->getAttribute("ReceiptDate");
    $receipt->deviceId = $receiptElem->getAttribute("ReceiptDeviceId");
    if($receiptElem->getElementsByTagName("ProductReceipt")->length) {
      $productReceiptElem = $receiptElem->getElementsByTagName("ProductReceipt")->item(0);
      $productReceipt = new ProductReceipt();
      $productReceipt->id = $productReceiptElem->getAttribute("Id");
      $productReceipt->appId = $productReceiptElem->getAttribute("AppId");
      $productReceipt->productId = $productReceiptElem->getAttribute("ProductId");
      $productReceipt->purchaseDate = $productReceiptElem->getAttribute("PurchaseDate");
      $productReceipt->productType = $productReceiptElem->getAttribute("ProductType");
      $productReceipt->purchasePrice = $productReceiptElem->getAttribute("PurchasePrice");
      $productReceipt->expirationDate = $productReceiptElem->getAttribute("ExpirationDate");
      $receipt->productReceipt = $productReceipt;
    }
    if($receiptElem->getElementsByTagName("AppReceipt")->length) {
      $appReceiptElem = $receiptElem->getElementsByTagName("AppReceipt")->item(0);
      $appReceipt = new AppReceipt();
      $appReceipt->id = $appReceiptElem->getAttribute("Id");
      $appReceipt->appId = $appReceiptElem->getAttribute("AppId");
      $appReceipt->purchaseDate = $appReceiptElem->getAttribute("PurchaseDate");
      $appReceipt->licenseType = $appReceiptElem->getAttribute("LicenseType");
      $receipt->appReceipt = $appReceipt;
    }

    $receipt->xmlDoc = $doc;

    return $receipt;
  }
}