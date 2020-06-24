<?php namespace Semknox\Core\Services;

use Semknox\Core\Exceptions\LogicException;
use Semknox\Core\Services\ProductUpdate\ProductCollection;
use Semknox\Core\Services\ProductUpdate\Status;
use Semknox\Core\Services\ProductUpdate\WorkingDirectory;
use Semknox\Core\Services\ProductUpdate\WorkingDirectoryFactory;
use Semknox\Core\SxConfig;


/**
 * Class InitialUploadService. Handles collecting of products and upload to Semknox.
 *
 * @package Semknox\Core\Services
 * @method bool isRunning()
 * @method bool isStopped()
 * @method bool isCollecting()
 * @method bool isUploading()
 * @method bool isCompleted()
 * @method bool isAborted()
 * @method string getPhase()
 * @method int getNumberOfCollected()
 * @method int getCollectingProgress()
 * @method int getNumberOfUploaded()
 * @method int getUploadingProgress()
 * @method int getTotalProgress()
 * @method int getRemainingDuration()
 */
abstract class ProductUpdateServiceAbstract {

    /**
     * @var ApiClient
     */
    protected $client;

    /**
     * @var SxConfig
     */
    protected $config;

    /**
     * Full class name of the transformer to use
     * @var string
     */
    protected $transformerClass;

    /**
     * The path to the current working directory.
     * @var WorkingDirectory
     */
    protected $workingDirectory;

    /**
     * Current (initial-) upload status
     * @var Status
     */
    protected $status;


    /**
     * Products that are being collected.
     *
     * @var ProductCollection
     */
    protected $productCollection;


    /**
     * Construct the ProductUpdate service.
     *
     * @param ApiClient $client
     * @param SxConfig $config
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    public function __construct(ApiClient $client, SxConfig $config)
    {
        $this->client = $client;

        $this->config = $config;

        $this->transformerClass = $this->config->getProductTransformer();
    }

    /**
     * Add a product to be uploaded.
     * @param $product
     * @param $parameters Optional parameters that will be passed to the transform method of the Transformer
     */
    public function addProduct($product, $parameters=[])
    {
        if($this->transformerClass) {
            $transformer = new $this->transformerClass($product);

            $product = $transformer->transform($parameters);
        }

        // todo: validation

        // convert all numeric values to string (Semknox requirement)
        $product = $this->enforceStringValues($product);

        // add product to collection
        $this->productCollection->add($product);
    }

    /**
     * Cast all numeric values to strings, because Semknox API requires String values instead of numeric JSON values.
     * @param array $product
     * @return array
     */
    private function enforceStringValues(array $product)
    {
        array_walk_recursive($product, function(&$item, $key) {
            if(is_numeric($item)) {
                $item = (string) $item;
            }
        });

        return $product;
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




}