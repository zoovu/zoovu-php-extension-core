<?php
/**
 * File created for semknox-core.
 * @author aselle
 * @created 2020-05-15
 */

use Semknox\Core\Services\InitialUploadService;

class InitialUploadServiceCest
{
    protected $initialUpload;

    public function _before(UnitTester $I)
    {
        // instantiate $initalUpload
        $core = $I->getSxCore();

        $this->initialUpload = $core->getInitialUploader();
    }

    public function testType(UnitTester $I)
    {
        $I->assertInstanceOf(InitialUploadService::class, $this->initialUpload);
    }

    public function test()
    {
        
    }
}
