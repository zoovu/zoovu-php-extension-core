<?php namespace Semknox\Core\Services;

use Semknox\Core\Exceptions\FilePermissionException;
use Semknox\Core\SxConfig;
use Semknox\Core\Services\Traits\SingletonTrait;

class InitialUploadService {

    const STATUS_COLLECTING = 'collecting_products';

    const STATUS_UPLOADING = 'uploading-products';

    const STATUS_FINISHED = 'upload-finished';

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
     * Products that are being collected.
     *
     * @var array
     */
    private $products = [];

    public function __construct(ApiClient $client, SxConfig $config)
    {
        $this->client = $client;

        $this->config = $config;

        $this->transformerClass = $config->getProductTransformer();
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->writeProductsToFile();
    }

    /**
     * Initialize the initial upload. This creates a new intial upload directory to collect all products to be sent to Semknox.
     * Config:
     *  - expectedNumberOfProducts     (how many products we're expecting in total, helps with status report)
     */
    public function init($config = [])
    {
        $directory = rtrim($this->config->getStoragePath(), '/');
        $directory .= sprintf('/%s-%s/', 'semknox-upload', microtime());

        if(!is_writable(dirname($directory))) {
            throw new FilePermissionException('Can not create a new directory for initial upload');
        }

        if(!is_dir($directory)) {
            mkdir($directory);
        }

        // put initial status file in directory
        file_put_contents($directory . 'info.json', $this->getInfoFileContent());
    }

    /**
     * Add a product to be uploaded.
     * @param $product
     */
    public function addProduct($product)
    {
        if($this->transformerClass) {
            $transformer = new $this->transformerClass($product);

            $product = $transformer->transform();
        }

        // todo: validation

        // add product to collection
        $this->products[] = $product;
    }

    /**
     * Start uploading all collected products to Semknox.
     */
    public function start()
    {

    }

    private function getInfoFileContent()
    {
        return '{"id":"c-00122","productsCollected":296,"collectCompleted":true,"readBatch":2,"sendBatch":1,"sendBatchQty":1,"lastBatch":1575900182,"percentage":100,"startedBy":"cron","storeId":1,"message":"sent query log to SEMKNOX successfully; Initial upload completed; duration: 00:02:01","startTime":1575900122,"status":"finished","currentStep":3,"stepQty":3,"pause":false,"last":{"endTime":"08.12.2019 14:04","startTime":"08.12.2019 14:02","duration":"00:02:01","status":"finished","productsCollected":296},"endTime":1575900243,"duration":121}'
    }

    private function writeProductsToFile()
    {
        // todo
        file_put_contents($this->getCurrentProductFileName(), json_encode($this->products));
    }
}