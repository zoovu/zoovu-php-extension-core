<?php

use Semknox\Core\Services\InitialUpload\Status;

require __DIR__ . '/_config.php';

$jsonProducts = json_decode(file_get_contents('products.json'), true);
//$jsonProducts = [];

$sxCore = makeSxCore();
$uploader = $sxCore->getInitialUploader();

if($uploader->isStopped()) {
    $uploader->startCollecting();
}

if($uploader->isCollecting()) {
    foreach($jsonProducts as $product) {
        $uploader->addProduct($product);
    }

    $uploader->startUploading();
}
else if($uploader->isUploading()) {
    echo 'uploading';
}
