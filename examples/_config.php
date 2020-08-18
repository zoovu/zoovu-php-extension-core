<?php

require __DIR__ . '/../vendor/autoload.php';

function makeSxCore()
{
    $config = [
        // required options
//        'apiKey'      => '85owx55emd2gmoh8dtx7y49so44fy745',
//        'projectId'   => 24,
//        'apiUrl' => 'https://stage-magento-v3.semknox.com',
        //---------------------
//        'apiKey'      => 'kc7h2ch9yoypc8w1l73782q5y9na1jct',
//        'projectId'   => 25,
//        'apiUrl' => 'https://stage-magento-v3.semknox.com',
//        //---------------------
//        'apiKey'      => 'to7aor7o0k726h8hw5t7v8d4023j1g68',
//        'projectId'   => 23,
//        'apiUrl' => 'https://stage-oxid-v3.semknox.com',
        //---------------------
        'apiKey'      => 'xhfct2949s3m16c174lwdepu75n71xoc',
        'projectId'   => 9,
        'apiUrl' => 'https://api-oxid-v3.semknox.com',

        // optional options
        //'productTransformer' => MagentoSemknoxProductTransformer::class,

        'storagePath'        => __DIR__ . '/tmp',
        'requestTimeout' => 30,

        'uploadBatchSize' => 150,
    ];

    $sxConfig = new \Semknox\Core\SxConfig($config);
    $sxCore = new \Semknox\Core\SxCore($sxConfig);

    return $sxCore;
}