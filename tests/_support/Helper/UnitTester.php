<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class UnitTester extends \Codeception\Module
{
    /**
     * @var \Semknox\Core\SxCore
     */
    private static $sxCore;

    /**
     * @return \Semknox\Core\SxCore
     * @throws \Exception
     */
    public static function getSxCore()
    {
        if(self::$sxCore) {
            return self::$sxCore;
        }

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
            'apiKey'      => 'to7aor7o0k726h8hw5t7v8d4023j1g68',
            'projectId'   => 23,
            'apiUrl' => 'https://stage-oxid-v3.semknox.com',

            // optional options
            //'productTransformer' => MagentoSemknoxProductTransformer::class,

            'storagePath'        => __DIR__ . '/../../_data/',
            'requestTimeout' => 30
        ];

        $sxConfig = new \Semknox\Core\SxConfig($config);
        self::$sxCore = new \Semknox\Core\SxCore($sxConfig);
        return self::$sxCore;
    }
}
