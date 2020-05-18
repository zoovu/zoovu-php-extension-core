<?php

use Semknox\Core\Services\InitialUpload\Status;

require __DIR__ . '/_config.php';

$jsonProducts = json_decode(file_get_contents('products.json'), true);
//$jsonProducts = [];

$sxCore = makeSxCore();
$uploader = $sxCore->getInitialUploader();
$uploadPhase = $uploader->getPhase();

if($uploader->startCollecting());

if($uploadPhase == Status::PHASE_COLLECTING) {
    foreach($jsonProducts as $product) {
        $uploader->addProduct($product);
    }

    $uploader->startUploading();
}
else if($uploadPhase == Status::PHASE_UPLOADING) {
    echo 'uploading';
}
else if($uploadPhase == Status::PHASE_COMPLETED) {
    echo 'done';
}
