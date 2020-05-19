<?php

namespace Semknox\Core\Services\InitialUpload;

class WorkingDirectory
{
    /**
     * @var string
     */
    protected $workingDirectoryPath;

    /**
     * Initialize working directory for initial upload.
     *
     * @param string $workingDirectoryPath
     */
    public function __construct($workingDirectoryPath)
    {
        $this->workingDirectoryPath = $workingDirectoryPath;
    }

    public function __toString()
    {
        return $this->workingDirectoryPath ?: '';
    }

    /**
     * When this class is invoked as a function.
     * @param $path
     */
    public function __invoke($path)
    {
        return $this->workingDirectoryPath . '/' . $path;
    }


    /**
     * Return the path to the latest working directory
     *
     * @return string
     */
    public function getPath()
    {
        return $this->workingDirectoryPath;
    }


    /**
     * Rename the current working directory.
     * @param $newDirectoryName
     *
     * @return bool
     */
    public function rename($newDirectoryName)
    {
        $directory = $this->getPath();

        $newDirectoryName = dirname($directory)
                            . '/'
                            . $newDirectoryName;

        $status = rename($directory, $newDirectoryName);

        if($status) {
            $this->workingDirectoryPath = $newDirectoryName;
        }

        return $status;
    }

    /**
     * Rename te phase part of the current working directory
     * @param $newPhase
     */
    public function renamePhase($newPhase)
    {
        $directoryName = (string) basename($this->getPath());

        $currentPhase = strrchr($directoryName, '.');
        $newName = str_replace($currentPhase, '.' . $newPhase, $directoryName);

        return $this->rename($newName);
    }
}