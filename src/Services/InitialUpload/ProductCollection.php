<?php namespace Semknox\Core\Services\InitialUpload;

/**
 * Class ProductCollection contains all products that are being collected
 * @package Semknox\Core\Services\InitialUpload
 */
class ProductCollection {
    /**
     * Current working directory for inital upload.
     * @var WorkingDirectory
     */
    protected $workingDirectory;

    /**
     * The current file we're collecting products to.
     * @var string
     */
    protected $currentProductFile = '';

    /**
     * List of collected products in memory. They will be stored to $this->currentProductFile
     * on shutdown.
     * @var array
     */
    protected $productCollection = [];

    /**
     * Maximum size of products to collect in memory.
     * @var int|mixed
     */
    protected $productCollectionMaxSize = 200;


    /**
     * ProductCollection constructor.
     *
     * @param WorkingDirectory $workingDirectory
     * @param array $config
     *
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    public function __construct(WorkingDirectory &$workingDirectory, array $config = [])
    {
        $this->workingDirectory = $workingDirectory;

        $this->currentProductFile = $this->getCurrentProductFilePath();

        $this->readProductsFromFileToMemory();

        if(isset($config['maxSize'])) {
            $this->productCollectionMaxSize = $config['maxSize'];
        }
    }

    private function readProductsFromFileToMemory()
    {
        $filePath = $this->currentProductFile;

        if(file_exists($filePath)) {
            $content = file_get_contents($filePath);

            $this->productCollection = json_decode($content, true);
        }

        return $this;
    }

    public function writeToFile()
    {
        $filePath = $this->getCurrentProductFilePath();

        if(count($this->productCollection)) {
            if(!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath));
            }

            file_put_contents($filePath, json_encode($this->productCollection));
        }

        return $this;
    }

    /**
     * Create an empty new product file and return the path to the file
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     * @return string
     */
    private function createNextProductFile()
    {
        $filePath = $this->getCurrentProductFilePath();

        $fileDirectory =  dirname($filePath);

        if($filePath) {
            preg_match('/upload-data_([0-9]+)\.json$/', $filePath, $matches);

            $id = $matches[1];

            // create file with empty product collection array
            $nextFilePath = sprintf('%s/upload-data_%d.json', $fileDirectory, ++$id);
        }
        else {
            $nextFilePath = $fileDirectory . '/upload-data_1.json';
        }

        file_put_contents($nextFilePath, '[]');

        return $nextFilePath;
    }

    /**
     * Return the path to the latest product collection file.
     *
     * @return string
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    private function getCurrentProductFilePath()
    {
        if($this->currentProductFile) {
            return $this->currentProductFile;
        }

        $workingDirectory = (string) $this->workingDirectory;

        $productFiles = glob($workingDirectory . '/upload-data_*.json');

        if(count($productFiles)) {
            $productFile = end($productFiles);
        }
        else {
            $productFile = $workingDirectory . '/upload-data_1.json';
        }

        return $productFile;
    }


    /**
     * Add a product to the collection. If enough products have been collected: automatically store them to file and starts the next product file.
     * @param array $product
     */
    public function add(array $product)
    {
        $product = $this->enforceStringValues($product);

        $this->productCollection[] = $product;

        if(count($this->productCollection) >= $this->productCollectionMaxSize) {
            $this->writeToFile();

            $this->currentProductFile = $this->createNextProductFile();

            $this->clear();
        }
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
     * Return all product files in the current working directory.
     * @return array|false
     */
    public function allFiles()
    {
        $pattern = (string) $this->workingDirectory . '/upload-data_*.json';

        return glob($pattern);
    }

    /**
     * Return all product files that still have to be uploaded.
     * Retu
     */
    public function filesToUpload()
    {
        $allFiles = $this->allFiles();

        if(!$allFiles) {
            return $allFiles;
        }

        // remove all elements that end in .completed.json
        return array_filter($allFiles, function($path) {
            return (strpos($path, '.completed.json') === false);
        });
    }

    /**
     * Return the next file to upload or false if no more file has to be uploaded.
     * @return string|false
     */
    public function nextFileToUpload()
    {
        $allFiles = $this->filesToUpload();

        return reset($allFiles);
    }

    /**
     * Clear the current product collection.
     */
    public function clear()
    {
        $this->productCollection = [];
    }

}