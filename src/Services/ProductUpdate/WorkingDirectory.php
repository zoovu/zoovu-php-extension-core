<?php

namespace Semknox\Core\Services\ProductUpdate;

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
     * @return string
     */
    public function __invoke($path)
    {
        return $this->getPath($path);
    }


    /**
     * Return the path to the latest working directory
     *
     * @return string
     */
    public function getPath($path = '')
    {
        return $this->workingDirectoryPath . '/' . $path;
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
     * @return
     */
    public function renamePhase($newPhase)
    {
        $directoryName = (string) basename($this->getPath());

        $currentPhase = strrchr($directoryName, '.');
        $newName = str_replace($currentPhase, '.' . $newPhase, $directoryName);

        return $this->rename($newName);
    }

    /**
     * Get the phase from the directory name.
     *
     * @return false|string
     */
    public function getPhase()
    {
        $phaseWithDot = strrchr($this->workingDirectoryPath, '.');

        if(!$phaseWithDot) {
            return false;
        }

        return substr($phaseWithDot, 1);
    }

    /**
     * Removes the current working directory and all files in it.
     */
    public function remove()
    {
        array_map('unlink', glob($this->workingDirectoryPath . "/*.*"));

        if(is_dir($this->workingDirectoryPath)){
            rmdir($this->workingDirectoryPath);
        }
    }
}