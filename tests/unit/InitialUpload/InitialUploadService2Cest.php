<?php



use Semknox\Core\Services\InitialUploadService;


/**
 * Start another initialupload to confirm, that creating directories are not interfering.
 */
class InitialUploadService2Cest
{
    /**
     * @var \Semknox\Core\SxCore
     */
    protected $sxCore;

    /**
     * @var InitialUploadService
     */
    protected $initialUpload;

    /**
     * @var string
     */
    protected $directory;

    public function setUp()
    {
        // instantiate $initalUpload
        $this->sxCore = \Helper\UnitTester::getSxCore(true);
        $this->initialUpload = $this->sxCore->getInitialUploader();
        $this->directory = \Helper\UnitTester::getStoragePath();
    }



    //--------------------------------------------------------------
    // TESTS
    //--------------------------------------------------------------

    public function testType(UnitTester $I)
    {
        $I->assertInstanceOf(InitialUploadService::class, $this->initialUpload);
    }

    /**
     * Switch to status collecting: Start a new initial upload
     * @param UnitTester $I
     */
    public function testStatusCollecting(UnitTester $I)
    {
        $this->initialUpload->startCollecting();

        $I->assertTrue($this->initialUpload->isCollecting());
    }

    /**
     * When starting collecting again a logic exception should be executed
     * @param UnitTester $I
     */
    public function testErrorWhenStartingCollectingAgain(UnitTester $I)
    {
        $I->expectThrowable(\Semknox\Core\Exceptions\LogicException::class, function() {
            $this->initialUpload->startCollecting();
        });
    }

    /**
     * When adding products, a file should be written
     * @
     */
    public function testAddingProducts(UnitTester $I)
    {
        $productsFile = __DIR__ . '/products.json';
        $products = json_decode(file_get_contents($productsFile), true);

        foreach ($products as $product) {
            $this->initialUpload->addProduct($product);
        }

        $directories = glob($this->directory . '/*.COLLECTING', GLOB_ONLYDIR);

        $I->assertEquals(1, count($directories), 'Only one upload directory currently collecting');
    }

    public function testCreateAnotherUpload(UnitTester $I)
    {
        $I->expectThrowable(\Semknox\Core\Exceptions\DuplicateInstantiationException::class, function() {
            $initialupload = (\Helper\UnitTester::getSxCore())->getInitialUploader();
            $initialupload->startCollecting();
        });
    }

    public function testStartUploadingProducts(UnitTester $I)
    {
        $this->initialUpload->startUploading();

        $directories = glob($this->directory . '/*.UPLOADING', GLOB_ONLYDIR);

        $I->assertEquals(1, count($directories), 'Only one upload directory currently uploading');
    }

    /**
     * An exception should be thrown when products are added after completing collecting
     * @param UnitTester $I
     */
    public function testGetExceptionWhenNowCollectingMoreProducts(UnitTester $I)
    {
        $I->expectThrowable(\Semknox\Core\Exceptions\LogicException::class, function() {
            $this->initialUpload->addProduct(['id' => 'testproduct']);
        });
    }

    /**
     * When we switched to uploading and now call `startCollecting` again, an exception should be thrown.
     * @param UnitTester $I
     */
    public function testGetExceptionWhenTryingToStartCollectingAgain(UnitTester $I)
    {
        $I->expectThrowable(\Semknox\Core\Exceptions\LogicException::class, function() {
            $this->initialUpload->startCollecting();
        });
    }

    public function uploadProductsToSemknox(UnitTester $I)
    {
        $this->initialUpload->finalizeUpload();

        $directories = glob($this->directory . '/*.COMPLETED', GLOB_ONLYDIR);

        $I->assertEquals(2, count($directories), 'Only one upload directory that is completed');
    }

}
