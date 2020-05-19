<?php namespace Semknox\Core\Services;

use Semknox\Core\Services\InitialUpload\ProductCollection;
use Semknox\Core\Services\InitialUpload\Status;
use Semknox\Core\Services\InitialUpload\WorkingDirectory;
use Semknox\Core\Services\InitialUpload\WorkingDirectoryFactory;
use Semknox\Core\SxConfig;


/**
 * Class InitialUploadService. Handles collecting of products and upload to Semknox.
 *
 * @package Semknox\Core\Services
 * @method bool isRunning()
 * @method bool isCollecting()
 * @method bool isUploading()
 * @method bool isCompleted()
 * @method bool isAborted()
 * @method string getPhase()
 */
class InitialUploadService {

    /**
     * @var ApiClient
     */
    private $client;

    /**
     * @var SxConfig
     */
    private $config;

    /**
     * Full class name of the transformer to use
     * @var string
     */
    private $transformerClass;

    /**
     * The path to the current working directory.
     * @var WorkingDirectory
     */
    private $workingDirectory;

    /**
     * Current initial upload status
     * @var Status
     */
    private $status;

    /**
     * Products that are being collected.
     *
     * @var ProductCollection
     */
    private $productCollection;


    public function __construct(ApiClient $client, SxConfig $config)
    {
        $this->client = $client;

        $this->config = $config;

        $this->workingDirectory = WorkingDirectoryFactory::getLatest(
            $config->getStoragePath(),
            $config->getInitialUploadDirectoryIdentifier()
        );

        $this->transformerClass = $this->config->getProductTransformer();

        $this->init();
    }

    /**
     * Initialize the InitialUploadService:
     *   - get the current upload status from the working directory
     *   - initialize the product collection
     */
    private function init()
    {
        $this->status = new Status($this->workingDirectory);

        $this->productCollection = new ProductCollection($this->workingDirectory, [
            'maxSize' => $this->config->getInitialUploadBatchSize()
        ]);
    }


    /**
     * When the request is ended, permanent the products into the currently active file.
     */
    public function __destruct()
    {
        // permanent all products to file
        $this->productCollection->writeToFile();

        // write status from memory to file
        $this->status->writeToFile();
    }

    /**
     * Pass through status methods (isRunning())
     */
    public function __call($method, $args)
    {
        if(method_exists($this->status, $method)) {
            return call_user_func_array([$this->status, $method], $args);
        }
    }

    /**
     * Start a new initial upload. This creates a directory to collect all products to be sent to Semknox.
     * Config (todo):
     *  - expectedNumberOfProducts     (how many products we're expecting in total, helps with status report)
     */
    public function startCollecting($config = [])
    {
        $this->workingDirectory = WorkingDirectoryFactory::createNew(
            $this->config->getStoragePath(),
            $this->config->getInitialUploadDirectoryIdentifier()
        );

        $this->init();

        $this->status->writeToFile();
    }

    /**
     * Add a product to be uploaded.
     * @param $product
     */
    public function addProduct($product, $parameters=[])
    {
        if($this->getPhase() !== Status::PHASE_COLLECTING) {
            throw new \RuntimeException('Can not add products because current initial upload is not in phase "collecting".');
        }

        if($this->transformerClass) {
            $transformer = new $this->transformerClass($product);

            $product = $transformer->transform($parameters);
        }

        // todo: validation

        // add product to collection
        $this->productCollection->add($product);

        $this->status->increaseNumberOfCollected();
    }

    /**
     * Start uploading all collected products to Semknox.
     * This goes
     */
    public function startUploading()
    {
        $currentPhase = $this->getPhase();

        if($currentPhase !== ($this->status)::PHASE_COLLECTING) {
            throw new \RuntimeException('Can not startUploading because current upload is not in phase "collecting"');
        }

        $this->setPhaseTo(($this->status)::PHASE_UPLOADING);

        // signalise start of inital product upload
        $this->client->request('POST', 'products/batch/initiate');

        // start upload of batches
        foreach($this->productCollection->allFiles() as $file) {
            $products = file_get_contents($file);
            $products = json_decode($products, true);

            $this->client->setParam('products', $products);

            $this->client->request('POST', 'products/batch/upload');

            $this->status->increaseNumberOfUploaded(count($products));
            $this->status->writeToFile();
        }

        // when done change signal Semknox to start processing...
        $this->client->request('POST', 'products/batch/start');

        // ..and change directory name to .COMPLETED
        $this->setPhaseTo(($this->status)::PHASE_COMPLETED);
    }

    /**
     * Abort the current initial upload.
     */
    public function abort()
    {
        $this->setPhaseTo(($this->status)::PHASE_ABORTED);
    }

    private function setPhaseTo($newPhase)
    {
        // write products currently in memory to file
        $this->productCollection->writeToFile();
        $this->productCollection->clear();

        // change name of working directory e.g. from .COLLECTING to .UPLOADING
        $this->workingDirectory->renamePhase($newPhase);

        // change phase in status
        $this->status->setPhase($newPhase);
        $this->status->writeToFile();
    }
}