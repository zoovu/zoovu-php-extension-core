<?php namespace Semknox\Core\Services;

use Semknox\Core\Exceptions\FilePermissionException;
use Semknox\Core\Services\InitialUpload\ProductCollection;
use Semknox\Core\Services\InitialUpload\Status;
use Semknox\Core\Services\InitialUpload\WorkingDirectory;
use Semknox\Core\SxConfig;
use Semknox\Core\Services\Traits\SingletonTrait;


/**
 * Class InitialUploadService. Handles collecting of products and upload to Semknox.
 *
 *
 * @package Semknox\Core\Services
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

    /**
     * The maximum amount of products to be collected before they are written
     * into a file.
     * @var int
     */
    private $productCollectionMaxSize = 200;



    public function __construct(ApiClient $client, SxConfig $config)
    {
        $this->client = $client;

        $this->config = $config;

        $this->init();
    }

    /**
     * When the request is ended, permanent the products into the currently active file.
     */
    public function __destruct()
    {
        // permanent all products to file
        $this->productCollection->writeToFile();

        $this->status->writeToFile();
    }

    /**
     * Initialize the InitialUploadService:
     *   - find the directory this initial upload is working in
     *   - get the current upload status
     *   - intialize product collection
     */
    private function init()
    {
        $this->transformerClass = $this->config->getProductTransformer();

        $this->workingDirectory = new WorkingDirectory($this->config);

        $this->status = new Status($this->workingDirectory);

        $this->productCollection = new ProductCollection($this->workingDirectory, [
            'maxSize' => $this->config->getInitialUploadBatchSize()
        ]);
    }




    /**
     * Start a new initial upload. This creates a directory to collect all products to be sent to Semknox.
     * Config (todo):
     *  - expectedNumberOfProducts     (how many products we're expecting in total, helps with status report)
     */
    private function addNew($config = [])
    {
        $directory = $this->workingDirectory->nextWorkingDirectoryName();

        $this->createNextProductsFile();

        // put initial status file in directory
        file_put_contents($directory . 'info.json', $this->getInfoFileContent());
    }

    /**
     * Add a product to be uploaded.
     * @param $product
     */
    public function addProduct($product, $parameters=[])
    {
        if($this->getPhase() == ($this->status)::PHASE_COMPLETED) {
            $this->addNew();
        }
        else if($this->getPhase() == ($this->status)::PHASE_UPLOADING) {
            throw new \RuntimeException('Can not add products to current initial upload, because upload is already in progress.');
        }

        if($this->transformerClass) {
            $transformer = new $this->transformerClass($product);

            $product = $transformer->transform($parameters);
        }

        // todo: validation

        // add product to collection
        $this->productCollection->add($product);

        $this->status->addCollected();
    }

    /**
     * Start uploading all collected products to Semknox.
     * This goes
     */
    public function start()
    {
        $currentPhase = $this->getPhase();

        if($currentPhase !== ($this->status)::PHASE_COLLECTING) {
            return;
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

            var_dump($file);echo '<br><br>';

            $this->status->addUploaded(count($products));
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

        // change name of working directory from .COLLECTING to .UPLOADING
        $this->workingDirectory->renamePhase($newPhase);

        // change phase in status
        $this->status->setPhase($newPhase);
        $this->status->writeToFile();
    }

    /**
     * Get the current phase (collecting/uploading/completed/aborted)
     * @return mixed
     */
    public function getPhase()
    {
        return $this->status->getPhase();
    }

    public function getStatus()
    {
        return $this->status;
    }
}