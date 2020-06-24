<?php

use Semknox\Core\Services\ProductUpdate\Status;

require __DIR__ . '/_config.php';

$jsonProducts = json_decode(file_get_contents('products_single.json'), true);
$sxCore = makeSxCore();
$updater = $sxCore->getProductUpdater();

//foreach($jsonProducts as $product) {
//    $updater->addProduct($product);
//}


$updater->sendUploadBatch();


//foreach($jsonProducts as $product) {
//    $updater->addProduct($product);
//}
