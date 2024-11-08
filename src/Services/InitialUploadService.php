<?php namespace Semknox\Core\Services;

use Exception;
use Semknox\Core\Exceptions\DuplicateInstantiationException;
use Semknox\Core\Exceptions\LogicException;
use Semknox\Core\Services\ProductUpdate\ProductCollection;
use Semknox\Core\Services\ProductUpdate\Status;
use Semknox\Core\Services\ProductUpdate\WorkingDirectoryFactory;
use Semknox\Core\Services\Traits\LockTrait;
use Semknox\Core\SxConfig;


/**
 * Class InitialUploadService. Handles collecting of products and upload to Semknox.
 */
class InitialUploadService extends ProductUpdateServiceAbstract {

    use LockTrait;

    private $_startTime;

    /**
     * InitialUploadService constructor.
     *
     * @param ApiClient $client
     * @param SxConfig $config
     *
     * @throws DuplicateInstantiationException
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    public function __construct(ApiClient $client, SxConfig $config)
    {
        $this->handleLock($config->getStoragePath(), $config->getInitialUploadDirectoryIdentifier());

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
     * Check if a lockfile already exists and throw an exception if so. If no lock file exists it creates the lock file.
     *
     * @param string $storagePath Path to the directory where all directories are stored.
     * @param string $initialUploadIdentifier Identifier for this shop.
     *
     * @throws DuplicateInstantiationException
     */
    private function handleLock($storagePath, $initialUploadIdentifier)
    {
        $this->setLockFilePath($storagePath . '/' . $initialUploadIdentifier . '.lock');

        if($this->hasLock()) {
            throw new DuplicateInstantiationException('Can not create a second InitialUploadService instance while uploading is in progress.');
        }

        $this->setLock();
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

        $numberOfProducts = $this->productCollection->getProductsAddedInThisRun();
        if ($numberOfProducts) {
            $this->config->getLoggingService()->info("$numberOfProducts products added to upload [".$this->getDuration()."]");
        }

        if($this->status->changed){
            // write status from memory to file
            $this->status->writeToFile();
        }

        if ($this->status->phaseChanged) {
            $this->config->getLoggingService()->info("upload status changed: " . $this->status->getPhase());
        }

        $this->removeLock();
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
        $this->_startTime = microtime(true);

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
    public function startCollecting($config = [], $cleanUp = true)
    {
        if ($cleanUp) {
            $this->cleanupOldUploadDirectories();
        }

        if($this->isRunning()) {
            throw new LogicException('Initial upload is already running. Can not start a new initial upload. Please wait for the previous upload to complete or abort the upload first.');
        }

        $this->workingDirectory = WorkingDirectoryFactory::createNew(
            $this->config->getStoragePath(),
            $this->config->getInitialUploadDirectoryIdentifier()
        );

        $this->init($config);

        $this->status->writeToFile();

        $logMessage = sprintf('New initial upload has started in "%s"', $this->workingDirectory->getPath());
        $this->config->getLoggingService()->info($logMessage);

        $expected = $this->getStatus()->getExpectedNumberOfProducts();

        $this->config->getLoggingService()->info("+/- $expected products in this upload expected [" . $this->getDuration() . "]");
        $this->config->getLoggingService()->info("upload status changed: " . $this->status->getPhase());

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
            $this->config->getLoggingService()->error('Can not startUploading because current upload is not in phase "collecting"');
            throw new \RuntimeException('Can not startUploading because current upload is not in phase "collecting"');
        }

        if($this->isTimeoutActive()){
            $this->config->getLoggingService()->info("still in timeout-mode.");
            return ['status' => 'success'];
        }

        $productsSortedOut = $this->getStatus()->getNumberOfSortedOut();
        $productsPrepared = $this->getStatus()->getNumberOfCollected() - $productsSortedOut;
        $this->config->getLoggingService()->info("$productsPrepared products prepared for upload, $productsSortedOut products sorted out");

        // log that initial upload is now starting uploading
        $logMessage = sprintf('Initial upload "%s" is now uploading the products', $this->workingDirectory->getPath());
        $this->config->getLoggingService()->info($logMessage);

        try {
            $response = $this->client->request('POST', 'products/batch/initiate');
        } catch (Exception $e) {
            $timeout = $this->status->setTimeout();
            $this->config->getLoggingService()->error('POST products/batch/initiate: '.$timeout.' minutes timeout');
            $this->config->getLoggingService()->error($e->getMessage());
            //throw new Exception($e->getMessage()); // to get a log entry
        }

        // check if request was successful before setting phase to uploading
        if (is_array($response) && $response['status'] == 'success') {
            $this->setPhaseTo(($this->status)::PHASE_UPLOADING);
        } else {
            $timeout = $this->status->setTimeout();
            $this->config->getLoggingService()->error('products/batch/initiate RESPONSE='.$response['status'].': '.$timeout.' minutes timeout');
            $this->config->getLoggingService()->error($response['message']);
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
        $file = $this->productCollection->nextFileToUpload();
        if(!$file) {

            //todo: IMPROVE!

            // to finish uploading 
            $numberOfCollected = $this->status->getNumberOfCollected();
            $numberOfUploaded = $this->status->getNumberOfUploaded();

            $fillUp = $numberOfCollected - $numberOfUploaded;

            $this->status->increaseNumberOfUploaded($fillUp);
            $this->status->writeToFile();

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
            $timeout = $this->status->setTimeout();
            $this->config->getLoggingService()->error('POST products/batch/upload: '.$timeout.' minutes timeout');
            $this->config->getLoggingService()->error($e->getMessage());
            //throw new Exception($e->getMessage()); // to get a log entry
        }
    
        // check if request was successful before increasing number of uploaded
        if(is_array($response) && $response['status'] == 'success'){

            $numberOfProducts = count($products);

            $this->status->increaseNumberOfUploaded($numberOfProducts);
            $this->status->writeToFile();

            // rename file to .completed.
            // Todo: this should not be done by this service
            rename($file, str_replace('.json', '.uploaded.json', $file));

            $this->config->getLoggingService()->info("$numberOfProducts products uploaded [" . $this->getDuration() . "]");


            return $numberOfProducts;
        } else {
            $timeout = $this->status->setTimeout();
            $this->config->getLoggingService()->error('products/batch/upload RESPONSE='.$response['status'].': '.$timeout.' minutes timeout');
        }


        if(is_array($response)){   

            if (isset($response['validation'][0]['schemaErrors'][0])) {
                $this->config->getLoggingService()->error('products/batch/upload VALIDATION FAILED:');
                if(isset($response['validation'][0]['schemaErrors']['product']) && isset($response['validation'][0]['schemaErrors']['product']['identifier'])){
                    $this->config->getLoggingService()->error('product: '. $response['validation'][0]['schemaErrors']['product']['identifier']);
                }
                $this->config->getLoggingService()->error(json_encode($response));
            }

            if (isset($response['status']) && $response['status'] !== 'success') {
                $this->config->getLoggingService()->error('products/batch/upload VALIDATION FAILED:');
                if (isset($response['validation'][0]['schemaErrors']['product']) && isset($response['validation'][0]['schemaErrors']['product']['identifier'])) {
                    $this->config->getLoggingService()->error('product: ' . $response['validation'][0]['schemaErrors']['product']['identifier']);
                }
                $this->config->getLoggingService()->error(json_encode($response));
            }

        } if(is_int($response) && $response > 0){
            $this->config->getLoggingService()->info("$response products uploaded in this branch");
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
            $response = $this->signalizeSemknoxToStartProcessing();
        }

        if($response['status'] == 'success'){
            // ..and change directory name to .COMPLETED
            $this->setPhaseTo(($this->status)::PHASE_COMPLETED);
            $this->status->writeToFile(); // fixes not saving COMPLETED status
            $this->removeLock();

            if($cleanUp) {
                $this->cleanupOldUploadDirectories();
            }

            $logMessage = sprintf('Initial upload "%s" finished', $this->workingDirectory->getPath());
            $this->config->getLoggingService()->info($logMessage);  
        } else {
            $logMessage = sprintf('Initial upload "%s" could not finish. Status "success" was not returned by SEMKNOX', $this->workingDirectory->getPath());
            $this->config->getLoggingService()->error($logMessage);
            $this->config->getLoggingService()->error(json_encode($response));
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
        if ($this->isTimeoutActive()){
            $this->config->getLoggingService()->info('still in timeout');
            return ['status' => 'success'];
        } 

        try {
            $response = $this->client->request('POST', 'products/batch/start');
        } catch (Exception $e) {
            $timeout = $this->status->setTimeout();
            $this->config->getLoggingService()->error('POST products/batch/start: '.$timeout.' minutes timeout');
            $this->config->getLoggingService()->error($e->getMessage());
            //throw new Exception($e->getMessage()); // to get a log entry
        }

        if($response['status'] != 'success'){
            $this->config->getLoggingService()->error('products/batch/start RESPONSE='.$response['status'].': try again on next run');
        }

        return $response; // try again on next run
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

    private function getDuration()
    {
        $duration = (microtime(true) - $this->_startTime) / 1000;
        $this->_startTime = microtime(true);
        return $duration.' s';
    }

}