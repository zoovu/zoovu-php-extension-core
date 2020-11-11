<?php

use Semknox\Core\Services\ProductUpdate\Status;

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();
$overview = $sxCore->getInitialUploadOverview();

var_dump($overview->getRunningUploads());