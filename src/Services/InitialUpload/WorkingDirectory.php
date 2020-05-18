<?php

namespace Semknox\Core\Services\InitialUpload;


use Semknox\Core\Exceptions\FilePermissionException;
use Semknox\Core\SxConfig;

class WorkingDirectory
{
    /**
     * @var SxConfig
     */
    protected $config;

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * Initialize working directory for initial upload.
     *
     * @param array $status Status information
     */
    public function __construct(SxConfig $config)
    {
        $this->config = $config;

        $this->workingDirectory = $this->getWorkingDirectory();
    }

    public function __toString()
    {
        return $this->workingDirectory ?: '';
    }

    /**
     * When this class is invoked as a function.
     * @param $path
     */
    public function __invoke($path)
    {
        return $this->workingDirectory . '/' . $path;
    }



    /**
     * Return the path to the latest working directory
     *
     * @return string
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    private function getWorkingDirectory()
    {
        if($this->workingDirectory) {
            return $this->workingDirectory;
        }

        $workingDirectory = $this->getLatestDirectory();

        return $workingDirectory ?: $this->getNextWorkingDirectoryName();
    }

    /**
     * Return the path to the latest working directory or null, if none has been created yet.
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     * @return
     */
    public function getLatestDirectory()
    {
        $storagePath = rtrim($this->config->getStoragePath(), '/');

        $identifier = $this->config->getInitialUploadDirectoryIdentifier();

        $directories = "$storagePath/$identifier-*";

        // get latest working directory
        $directories = glob($directories, GLOB_ONLYDIR);

        // because glob orders alphabetically, the latest directory is the last one in $directories
        return count($directories) ? end($directories) : null;
    }

    /**
     * Create a new directory called "semknox-upload-<time>" in $config->getStoragePath().
     * If configuration value 'initialUploadIdentifier' has been set "semknox-upload" is replaces with the identifier.
     *
     * @return string
     * @throws FilePermissionException
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    public function createNew()
    {
        $directory = $this->getNextWorkingDirectoryName();

        if(!is_writable(dirname($directory))) {
            throw new FilePermissionException('Can not create a new directory for initial upload');
        }

        if(!is_dir($directory)) {
            mkdir($directory);
        }

        return $directory;
    }

    /**
     * Return the name of what would be the next working directory.
     * @return string
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    private function getNextWorkingDirectoryName()
    {
        $storagePath = rtrim($this->config->getStoragePath(), '/');

        $identifier = $this->config->getInitialUploadDirectoryIdentifier();

        $time = date('YmdHis');

        return "$storagePath/$identifier-$time." . Status::PHASE_COLLECTING;
    }


    public function rename($newDirectory)
    {
        $directory = $this->getWorkingDirectory();

        $newDirectory = dirname($directory)
                        . '/'
                        . $newDirectory;

        $status = rename($directory, $newDirectory);

        if($status) {
            $this->workingDirectory = $newDirectory;
        }

        return $status;
    }

    /**
     * Rename te phase part of the current working directory
     * @param $newPhase
     */
    public function renamePhase($newPhase)
    {
        $directoryName = (string) basename($this->getWorkingDirectory());

        $currentPhase = strrchr($directoryName, '.');
        $newName = str_replace($currentPhase, '.' . $newPhase, $directoryName);

        return $this->rename($newName);
    }
}