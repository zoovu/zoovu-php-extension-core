<?php

use Semknox\Core\Services\InitialUpload\Status;

require __DIR__ . '/_config.php';

$jsonProducts = json_decode(file_get_contents('products.json'), true);
//$jsonProducts = [];

$sxCore = makeSxCore();
$uploader = $sxCore->getInitialUploader();

// start a new upload (start collecting products)
if($uploader->isStopped()) {
    $uploader->startCollecting();
}

// while it is collecting products add products
if($uploader->isCollecting()) {
    foreach($jsonProducts as $product) {
        $uploader->addProduct($product);
    }

    // start upload when done collecting
    $uploader->startUploading();
}

// while uploading trigger to send a product batch
if($uploader->isUploading()) {
    echo 'uploading';

    $numUploaded = $uploader->sendUploadBatch();

    if($numUploaded === 0) {
        $uploader->finalizeUpload();
    }
}
