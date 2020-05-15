<?php

require __DIR__ . '/_boot.php';

$jsonProducts = json_decode(file_get_contents('products.json'), true);

$sxCore = makeSxCore();
$uploader = $sxCore->getInitialUploader();

$uploader->init();


foreach($jsonProducts as $product) {
    $uploader->addProduct($product);
}

$uploader->start();