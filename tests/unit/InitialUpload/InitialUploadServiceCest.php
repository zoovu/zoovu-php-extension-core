<?php
/**
 * File created for semknox-core.
 * @author aselle
 * @created 2020-05-15
 */

use Helper\UnitTester;
use Semknox\Core\Services\InitialUploadService;

class InitialUploadServiceCest
{
    protected $initialUpload;

    public function before(UnitTester $I)
    {
        // instantiate $initalUpload
        $core = $I->getSxCore();

        $this->initialUpload = $core->getInitialUploader();
    }

    public function testType()
    {
        $this->assertInstanceOf(InitialUploadService::class, $this->initialUpload);
    }
}
