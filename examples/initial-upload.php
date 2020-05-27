<?php

use Semknox\Core\Services\InitialUpload\Status;

require __DIR__ . '/_config.php';

$jsonProducts = json_decode(file_get_contents('products.json'), true);
//$jsonProducts = [];

$sxCore = makeSxCore();
$uploader = $sxCore->getInitialUploader();

// start a new upload (start collecting products)
if($uploader->isStopped()) {
    $uploader->startCollecting([
        'expectedNumberOfProducts' => count($jsonProducts)
    ]);
}

// while it is collecting products add products
if($uploader->isCollecting()) {
    foreach($jsonProducts as $product) {
        $uploader->addProduct($product);
    }

    // start upload when done collecting
    $uploader->startUploading();
}


if($uploader->isUploading()) {
    // send product batches to semknox
    //do {
    //    $numUploaded = $uploader->sendUploadBatch();
    //} while($numUploaded > 0);

    if($uploader->sendUploadBatch() === 0) {
        // signalize that all products have been sent
        $uploader->finalizeUpload();
    }
}
