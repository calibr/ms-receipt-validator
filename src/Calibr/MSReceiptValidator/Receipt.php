<?php

namespace Calibr\MSReceiptValidator;

class ProductReceipt {
  public $id;
  public $appId;
  public $productId;
  public $purchaseDate;
  public $productType;
  public $purchasePrice;
  public $expirationDate;
}

class AppReceipt {
  public $id;
  public $appId;
  public $purchaseDate;
  public $licenseType;
}

class Receipt {
  public $date;
  public $deviceId;
  public $productReceipt = null;
  public $appReceipt = null;

  public $xmlDoc;
}