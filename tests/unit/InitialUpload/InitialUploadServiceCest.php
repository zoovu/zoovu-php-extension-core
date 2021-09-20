<?php
/**
 * File created for semknox-core.
 * @author aselle
 * @created 2020-05-15
 */

use Semknox\Core\Services\InitialUploadService;

class InitialUploadServiceCest
{
    /**
     * @var \Semknox\Core\SxCore
     */
    protected $sxCore;

    /**
     * @var InitialUploadService
     */
    protected $initialUpload;

    public function __construct()
    {
        // instantiate $initalUpload
        $this->sxCore = \Helper\UnitTester::getSxCore();
        $this->initialUpload = $this->sxCore->getInitialUploader();
    }

    //--------------------------------------------------------------
    // TESTS
    //--------------------------------------------------------------

    public function testType(UnitTester $I)
    {
        $I->assertInstanceOf(InitialUploadService::class, $this->initialUpload);
    }

    public function testStatusCollecting(UnitTester $I)
    {
        $this->initialUpload->startCollecting();

        $I->assertTrue($this->initialUpload->isCollecting());
    }
}
