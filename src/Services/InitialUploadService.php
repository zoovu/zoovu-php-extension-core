<?php namespace Semknox\Core\Services;

use Exception;
use Semknox\Core\Exceptions\DuplicateInstantiationException;
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

        if($this->status->hasLock()) {
            throw new DuplicateInstantiationException('Can not create a second InitialUploadService instance while uploading is in progress.');
        }

        if($this->status->isUploading()) {
            $this->status->setLock();
        }
    }


    /**
     * When the request is ended, permanent the collected products into the currently active file.
     */
    public function __destruct()
    {
        if($this->getPhase() === ($this->status)::PHASE_COLLECTING) {
            // permanent all collected products to file
            $this->productCollection->writeToFile();
        }

        if($this->status->changed){
            // write status from memory to file
            $this->status->writeToFile();
        }

        $this->status->removeLock();
    }

    /**
     * Pass through status methods (isRunning(), isStopped())
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

        if($this->isTimeoutActive()) return ['status' => 'success'];

        try {
            $response = $this->client->request('POST', 'products/batch/initiate');
        } catch (Exception $e) {
            $this->status->setTimeout();
            throw new Exception($e->getMessage()); // to get a log entry
        }

        // check if request was successfull before setting phase to uploading
        if ($response['status'] == 'success') {
            $this->setPhaseTo(($this->status)::PHASE_UPLOADING);
        } else {
            $this->status->setTimeout();
        }

        return $response;
    }

    /**
     * Send a single product batch to semknox for processing. Returns the the number of products uploaded in this batch.
     * @return int The number of products sent in this batch.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendUploadBatch($returnFullResponse = false)
    {
        $response = [];

        $file = $this->productCollection->nextFileToUpload();
        if(!$file) {
            return 0;
        }

        // start upload of batches
        $products = file_get_contents($file);
        $products = json_decode($products, true);

        $this->client->setParam('products', $products);

        if ($this->isTimeoutActive()) return ['status' => 'success'];
        try{
            $response = $this->client->request('POST', 'products/batch/upload');
        } catch (Exception $e){
            $this->status->setTimeout();
            throw new Exception($e->getMessage()); // to get a log entry
        }
    
        // check if request was successful before increasing number of uploaded
        if($response['status'] == 'success'){

            $numberOfProducts = count($products);

            $this->status->increaseNumberOfUploaded($numberOfProducts);
            $this->status->writeToFile();

            // rename file to .completed.
            // Todo: this should not be done by this service
            rename($file, str_replace('.json', '.uploaded.json', $file));

            return $numberOfProducts;
        } else {
            $this->status->setTimeout();
        }

        // todo: throw exception on error instead of returning false
        //       that way we don't have to differentiate between 0 and false
        return $returnFullResponse ? $response : false; // attention: 0 !== false
    }



    /**
     * Signalizes Semknox that all product batches have been uploaded and sets status of this upload to "COMPLETED".
     *
     * @param $signal Signalize Semknox that upload is complete and they can start processing products.
     * @param $cleanUp Remove old upload directories
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function finalizeUpload($signal = true, $cleanUp = true)
    {
        $response['status'] = 'success';

        if($signal){
            $this->signalizeSemknoxToStartProcessing();
        }

        // ..and change directory name to .COMPLETED
        $this->setPhaseTo(($this->status)::PHASE_COMPLETED);
        $this->status->writeToFile(); // fixes not saving COMPLETED status

        if($cleanUp) {
            $this->cleanupOldUploadDirectories();
        }

        return $response;
    }

    /**
     * Signalize Semknox that the upload is completed and they can start processing the products.
     *
     * @return array|string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function signalizeSemknoxToStartProcessing()
    {
        // when done change signal Semknox to start processing...
        if ($this->isTimeoutActive()) return ['status' => 'success'];

        try {
            $response = $this->client->request('POST', 'products/batch/start');
        } catch (Exception $e) {
            $this->status->setTimeout();
            throw new Exception($e->getMessage()); // to get a log entry
        }

        // check if request was successfull
        if ($response['status'] != 'success') {
            return $response; // try again on next run!
            // $this->status->setTimeout();
        }
    }

    /**
     * Remove completed and aborted upload directories in storage path.
     */
    private function cleanupOldUploadDirectories()
    {
        $path = $this->config->getStoragePath() . '/'
              . $this->config->getInitialUploadDirectoryIdentifier();

        $keepCompleted = $this->config->getKeepLastCompletedUploads();
        $keepAborted = $this->config->getKeepLastAbortedUploads();

        $directoriesToClean = [
            [$path . '-*.COMPLETED', $keepCompleted],
            [$path . '-*.ABORTED', $keepAborted],
        ];

        foreach($directoriesToClean as $info) {
            list($globPattern, $keep) = $info;

            $directories = glob($globPattern);

            if(count($directories) > $keep) {
                // remove the last $keepCompleted directories
                $directoriesToDelete = array_slice($directories, 0, -$keep);

                foreach ($directoriesToDelete as $directory) {
                    $this->removeDirectory($directory);
                }
            }
        }
    }

    /**
     * Removes a (non-empty) directory
     * @param $directory
     */
    private function removeDirectory($directory)
    {
        $it = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach($it as $file) {
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }

        rmdir($directory);
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


    private function isTimeoutActive()
    {
        if($this->status->isTimeoutActive()){
            return true;
        }

        // after 3 timeouts, ABORT!
        if($this->status->getNumberOfTimeouts() > 3){
            $this->abort();
            return true;
        }

        return false;

    }
}