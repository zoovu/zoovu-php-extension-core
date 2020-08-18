<?php namespace Semknox\Core\Services;

use Semknox\Core\Exceptions\LogicException;
use Semknox\Core\Services\ProductUpdate\ProductCollection;
use Semknox\Core\Services\ProductUpdate\Status;
use Semknox\Core\Services\ProductUpdate\WorkingDirectory;
use Semknox\Core\Services\ProductUpdate\WorkingDirectoryFactory;
use Semknox\Core\SxConfig;


/**
 * Service to send product updates.
 * Expected usage:
 *  - collect product updates
 *  - cronjob every minute: sendUploadBatch()
 *
 * ProductUpdateService collects all updates in a directory
 *
 * @package Semknox\Core\Services
 */
class ProductUpdateService extends ProductUpdateServiceAbstract {
    /**
     * Skip incremental updates when an initial upload is ongoing
     * @var bool
     */
    protected $skip = false;

    public function __construct(ApiClient $client, SxConfig $config)
    {
        parent::__construct(
            $client,
            $config
        );

        $this->workingDirectory = WorkingDirectoryFactory::getToday(
            $config->getStoragePath(),
            $config->getProductUpdateDirectoryIdentifier()
        );

        $this->productCollection = new ProductCollection($this->workingDirectory, [
            'maxSize' => $this->config->getUploadBatchSize()
        ]);

        $this->skipIfInitialUploadExists();
    }

    /**
     * Check if there is a directory for a running initial upload for this
     * shopId + storeIdentifier.
     */
    private function skipIfInitialUploadExists()
    {
        $initialUploadDir = WorkingDirectoryFactory::getLatest(
            $this->config->getStoragePath(),
            $this->config->getInitialUploadDirectoryIdentifier()
        );

        $skip = is_dir($initialUploadDir)
                &&
                in_array($initialUploadDir->getPhase(), [
                    Status::PHASE_COLLECTING,
                    Status::PHASE_UPLOADING
                ]);

        if($skip) {
            $this->skip = true;

            // remove all collected uploads, because
            // everything will be submitted anyway with the initial upload
            $this->workingDirectory->remove();
        }
    }

    /**
     * When the request is ended, permanent the collected products into the currently active file.
     */
    public function __destruct()
    {
        if($this->skip) return;

        // permanent all collected products to file
        $this->productCollection->writeToFile();
    }

    /**
     * @inheritDoc
     */
    public function addProduct($product, $parameters=[])
    {
        if($this->skip) return;

        parent::addProduct($product, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function sendUploadBatch()
    {
        if($this->skip) return;

        $this->productCollection->writeToFile();
        $this->productCollection->clear();
        $file = $this->productCollection->nextFileToUpload();

        if(!$file) {
            return 0;
        }

        // start upload of batches
        $products = file_get_contents($file);
        $products = json_decode($products, true);

        $this->client->setParam('products', $products);

        $this->client->request('POST', 'products');

        $numberOfProducts = count($products);

        // delete the uploaded file
        unlink($file);

        return $numberOfProducts;
    }
}