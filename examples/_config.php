<?php

require __DIR__ . '/../vendor/autoload.php';

function makeSxCore()
{
    $config = [
        // required options
        'apiKey'      => '85owx55emd2gmoh8dtx7y49so44fy745',
        'projectId'   => 24,

        // optional options
        //'productTransformer' => MagentoSemknoxProductTransformer::class,
        'apiUrl' => 'https://stage-magento-v3.semknox.com',
        'storagePath'        => __DIR__ . '/tmp',
        'requestTimeout' => 30
    ];

    $sxConfig = new \Semknox\Core\SxConfig($config);
    $sxCore = new \Semknox\Core\SxCore($sxConfig);

    return $sxCore;
}