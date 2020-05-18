<?php

use Semknox\Core\Services\InitialUpload\Status;

require __DIR__ . '/_config.php';

$jsonProducts = json_decode(file_get_contents('products.json'), true);
//$jsonProducts = [];

$sxCore = makeSxCore();
$overview = $sxCore->getInitialUploadOverview();

var_dump($overview->getRunningUploads());