<?php
/**
 * File created for semknox-core.
 * @author aselle
 * @created 2020-05-18
 */

namespace Semknox\Core\Services;


use Semknox\Core\Services\ProductUpdate\Status;
use Semknox\Core\Services\ProductUpdate\WorkingDirectory;
use Semknox\Core\SxConfig;

/**
 * Get a birds-eye view over running initial uploads.
 *
 * @package Semknox\Core\Services
 */
class InitialUploadOverviewService
{
    /**
     * @var SxConfig
     */
    protected $config;

    /**
     * @var string
     */
    protected $storagePath;

	/**
	 * InitialUploadOverviewService constructor.
	 *
	 * @param SxConfig $config
	 */
	public function __construct(SxConfig $config) {
        $this->config = $config;

        $this->storagePath = $config->getStoragePath();
    }

    /**
     * Returns an associative array of running uploads. The array key is the identifier of the the upload, the value is the current upload status.
     *  [
     *      'store-en' => \Semknox\Core\Services\InitialUpload\Status
     *      'store-de' => \Semknox\Core\Services\InitialUpload\Status
     *  ]
     *
     * @return array
     */
    public function getRunningUploads()
    {
        $pattern = $this->storagePath . '/*';

        $directories = glob($pattern, GLOB_ONLYDIR);

        $runningPhases = [
            Status::PHASE_COLLECTING,
            Status::PHASE_UPLOADING
        ];

        $result = [];

        foreach($directories as $directory) {
            $name = basename($directory);

            preg_match('/^[0-9]+-(.+?)-initialupload-([0-9]{14})\.([A-Z]+?)$/i', $name, $matches);

            $identifier = $matches[1];
            $phase = $matches[3];

            if(in_array($phase, $runningPhases)) {
                $workingDirectory = new WorkingDirectory($directory);

                $status = new Status($workingDirectory);

                $result[$identifier] = $status;
            }
        }

        return $result;
    }
}