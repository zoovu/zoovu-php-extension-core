<?php

require __DIR__ . '/../vendor/autoload.php';

function makeSxCore()
{
    $config = [
        // required options
        'apiKey'      => '<your api key>',

        // optional options
        'productTransformer' => MagentoSemknoxProductTransformer::class,
        'storagePath'        => '/path/to/writable/directory',
    ];

    $sxConfig = new \Semknox\Core\SxConfig($config);
    $sxCore = new \Semknox\Core\SxCore($sxConfig);

    return $sxCore;
}