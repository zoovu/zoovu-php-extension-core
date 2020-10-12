<?php

use Semknox\Core\Services\ProductUpdate\Status;

require __DIR__ . '/_config.php';

$jsonProducts = json_decode(file_get_contents('products.json'), true);
//$jsonProducts = [];

try {
    $sxCore = makeSxCore();
    $uploader = $sxCore->getInitialUploader();
}
catch(\Semknox\Core\Exceptions\DuplicateInstantiationException $e) {
    // do not continue uploading in this request
    return;
}

// start a new upload (start collecting products)
if($uploader->isStopped()) {
    $uploader->startCollecting([
        'expectedNumberOfProducts' => 4 //count($jsonProducts)
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

//
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
