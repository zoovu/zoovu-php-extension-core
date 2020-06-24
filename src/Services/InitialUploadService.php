<?php namespace Semknox\Core\Services;

use Semknox\Core\Exceptions\LogicException;
use Semknox\Core\Services\ProductUpdate\ProductCollection;
use Semknox\Core\Services\ProductUpdate\Status;
use Semknox\Core\Services\ProductUpdate\WorkingDirectory;
use Semknox\Core\Services\ProductUpdate\WorkingDirectoryFactory;
use Semknox\Core\SxConfig;


/**
 * Class InitialUploadService. Handles collecting of products and upload to Semknox.
 */
class InitialUploadService extends ProductUpdateServiceAbstract {

    public function __construct(ApiClient $client, SxConfig $config)
    {
        parent::__construct(
            $client,
            $config
        );

        $this->workingDirectory = WorkingDirectoryFactory::getLatest(
            $config->getStoragePath(),
            $config->getInitialUploadDirectoryIdentifier()
        );

        $this->init();
    }


    /**
     * When the request is ended, permanent the collcted products into the currently active file.
     */
    public function __destruct()
    {
        if($this->getPhase() === ($this->status)::PHASE_COLLECTING) {
            // permanent all collected products to file
            $this->productCollection->writeToFile();
        }

        // write status from memory to file
        $this->status->writeToFile();
    }

    /**
     * Pass through status methods (isRunning(), is Stopped())
     */
    public function __call($method, $args)
    {
        if(method_exists($this->status, $method)) {
            return call_user_func_array([$this->status, $method], $args);
        }
    }

    /**
     * Initialize the InitialUploadService:
     *   - get the current upload status from the working directory
     *   - initialize the product collection
     */
    private function init(array $initialUploadConfig=[])
    {
        $this->status = new Status($this->workingDirectory, $initialUploadConfig);

        $this->productCollection = new ProductCollection($this->workingDirectory, [
            'maxSize' => $this->config->getUploadBatchSize()
        ]);
    }

    /**
     * Start a new initial upload. This creates a directory to collect all products to be sent to Semknox.
     * Config (todo):
     *  - expectedNumberOfProducts     (how many products we're expecting in total. If set we can get the progress in  helps with status report)
     */
    public function startCollecting($config = [])
    {
        if($this->isRunning()) {
            throw new LogicException('Initial upload is already running. Can not start a new initial upload. Please wait for the previous upload to complete or abort the upload first.');
        }

        $this->workingDirectory = WorkingDirectoryFactory::createNew(
            $this->config->getStoragePath(),
            $this->config->getInitialUploadDirectoryIdentifier()
        );

        $this->init($config);

        $this->status->writeToFile();
    }

    /**
     * @inheritDoc
     */
    public function addProduct($product, $parameters=[])
    {
        if($this->getPhase() !== Status::PHASE_COLLECTING) {
            throw new LogicException('Can not add products because current initial upload is not in phase "collecting".');
        }

        $return = parent::addProduct($product, $parameters);

        $this->status->increaseNumberOfCollected();

        return $return;
    }

    /**
     * Starts the initial upload progress. This method tells Semknox, that from now all products will be transmitted in batches using the method `sendUploadBatch()`.
     */
    public function startUploading()
    {
        $currentPhase = $this->getPhase();

        if($currentPhase !== ($this->status)::PHASE_COLLECTING) {
            throw new \RuntimeException('Can not startUploading because current upload is not in phase "collecting"');
        }

        $this->setPhaseTo(($this->status)::PHASE_UPLOADING);

        $this->client->request('POST', 'products/batch/initiate');
    }

    /**
     * Send a single product batch to semknox for processing. Returns the the number of products uploaded in this batch.
     * @return int The number of products sent in this batch.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendUploadBatch()
    {
        $file = $this->productCollection->nextFileToUpload();
        if(!$file) {
            return 0;
        }

        // start upload of batches
        $products = file_get_contents($file);
        $products = json_decode($products, true);

        $this->client->setParam('products', $products);

        $this->client->request('POST', 'products/batch/upload');

        $numberOfProducts = count($products);

        $this->status->increaseNumberOfUploaded($numberOfProducts);
        $this->status->writeToFile();

        // rename file to .completed.
        // Todo: this should not be done by this service
        rename($file, str_replace('.json', '.uploaded.json', $file));

        return $numberOfProducts;
    }



    /**
     * Signalizes Semknox that all product batches have been uploaded and sets status of this upload to "COMPLETED".
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function finalizeUpload()
    {
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

    /**
     * Changes the upload phase. Writes products to file and renames the directory so we can see more easily what state we're in.
     * @param $newPhase
     */
    private function setPhaseTo($newPhase)
    {
        // write products currently in memory to file
        $this->productCollection->writeToFile();
        $this->productCollection->clear();

        // change name of working directory e.g. from .COLLECTING to .UPLOADING
        $this->workingDirectory->renamePhase($newPhase);

        // change phase in status
        $this->status->setPhase($newPhase);
    }
}