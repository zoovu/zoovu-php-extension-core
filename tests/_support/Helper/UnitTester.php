<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class UnitTester extends \Codeception\Module
{
    /**
     * @var \Semknox\Core\SxCore
     */
    public static $sxCore;

    /**
     * @return \Semknox\Core\SxCore
     * @throws \Exception
     */
    public static function getSxCore($getFresh = false)
    {
        if(self::$sxCore && !$getFresh) {
            return self::$sxCore;
        }

        $config = [
            // required options
//                    'apiKey'      => '85owx55emd2gmoh8dtx7y49so44fy745',
//                    'projectId'   => 24,
//                    'apiUrl' => 'https://stage-magento-v3.semknox.com',
            //---------------------
            //        'apiKey'      => 'kc7h2ch9yoypc8w1l73782q5y9na1jct',
            //        'projectId'   => 25,
            //        'apiUrl' => 'https://stage-magento-v3.semknox.com',
            //        //---------------------
            'apiKey'      => 'xhfct2949s3m16c174lwdepu75n71xoc',
            'projectId'   => 9,
            'apiUrl' => 'https://api-oxid-v3.semknox.com',

            // optional options
            //'productTransformer' => MagentoSemknoxProductTransformer::class,

            'storagePath'        => self::getStoragePath(),
            'requestTimeout' => 30
        ];

        $sxConfig = new \Semknox\Core\SxConfig($config);
        self::$sxCore = new \Semknox\Core\SxCore($sxConfig);
        return self::$sxCore;
    }

    /**
     * Return the path to the directory where initial uploads are being stored.
     * @return string
     */
    public static function getStoragePath()
    {
        return realpath(__DIR__ . '/../../_data/');
    }

    /**
     * Delete a directory that is not empty
     * @param $dirPath
     * @return bool
     */
    public static function deleteDir($dirPath) {
        var_dump($dirPath);

        if (! is_dir($dirPath) || strlen($dirPath) < 3) {
            throw new \InvalidArgumentException("'$dirPath' must be a directory and more than 3 characters");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }

        return rmdir($dirPath);
    }

    /**
     * Clean all directories in the test _data directory.
     */
    public static function cleanDataDirectory()
    {
        // clean up initial uploads
        $directory = self::getStoragePath();
        $subdirectories = glob("$directory/*", GLOB_ONLYDIR);

        var_dump($subdirectories);

        foreach ($subdirectories as $dir) {
            self::deleteDir($dir . '/');
        }
    }
}
