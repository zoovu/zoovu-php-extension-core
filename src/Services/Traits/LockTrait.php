<?php namespace Semknox\Core\Services\Traits;

/**
 * Implements a file lock functionality.
 *
 * @package Semknox\Core\Services\Traits
 */
trait LockTrait
{
    /**
     * Path to the lock file that will be checked.
     * @var string
     */
    private $lockFilePath;

    private function setLockFilePath($filePath)
    {
        $this->lockFilePath = $filePath;

        return $this;
    }

    /**
     * Return the file path to the lockfile
     * @return string
     */
    private function getLockFilePath()
    {
        return $this->lockFilePath;
    }
    /**
     * Write lock
     */
    public function setLock()
    {
        file_put_contents($this->getLockFilePath(), time());
    }

    /**
     * Does a lock currently exist?
     * @return bool
     */
    public function hasLock()
    {
        $filePath = $this->getLockFilePath();

        if(!file_exists($filePath)) {
            return false;
        }

        // lock is older than 30 seconds
        if(file_get_contents($filePath) < (time() - 30)) {
            return false;
        }

        return true;
    }

    /**
     * Remove the set lock
     */
    public function removeLock()
    {
        if(file_exists($this->getLockFilePath())){
            unlink($this->getLockFilePath());
        }
    }
}