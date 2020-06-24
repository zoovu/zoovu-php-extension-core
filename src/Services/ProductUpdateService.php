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
    }


    /**
     * When the request is ended, permanent the collected products into the currently active file.
     */
    public function __destruct()
    {
        // permanent all collected products to file
        $this->productCollection->writeToFile();
    }

    /**
     * @inheritDoc
     */
    public function addProduct($product, $parameters=[])
    {
        parent::addProduct($product, $parameters);
    }

    /**
     * @inheritDoc
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

        $this->client->request('POST', 'products');

        $numberOfProducts = count($products);

        // rename file to .completed.
        // Todo: this should not be done by this service
        rename($file, str_replace('.json', '.uploaded.json', $file));

        return $numberOfProducts;
    }
}