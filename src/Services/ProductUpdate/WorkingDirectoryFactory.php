<?php

namespace Semknox\Core\Services\ProductUpdate;

use Semknox\Core\Exceptions\FilePermissionException;

abstract class WorkingDirectoryFactory
{
    /**
     * Return an instance of WorkingDirectory pointing at the latest directory.
     *
     * @param $storagePath
     * @param string $identifier
     *
     * @return WorkingDirectory
     */
    public static function getLatest($storagePath, $identifier='default-store')
    {
        $storagePath = rtrim($storagePath, '/');

        $directories = "$storagePath/$identifier-*";

        // get latest working directory
        $directories = glob($directories, GLOB_ONLYDIR);

        // because glob orders alphabetically, the latest directory is the last one in $directories
        $latestDirectory = count($directories) ? end($directories) : null;

        return $latestDirectory
            ? new WorkingDirectory($latestDirectory)
            : new WorkingDirectory(self::getNextWorkingDirectoryPath($storagePath, $identifier));
    }

    /**
     * Return an instance of WorkingDirectory pointing at todays upload directory.
     *
     * @param string $storagePath
     * @param string $identifier
     *
     * @return WorkingDirectory
     */
    public static function getToday($storagePath, $identifier='default-store')
    {
        $storagePath = rtrim($storagePath, '/');

        $today = date('Ymd');

        $directoryPath = "$storagePath/$identifier-$today";

        return new WorkingDirectory($directoryPath);
    }

    /**
     * Create a new empty directory called "<$identifier>-<time>" in $storagePath.
     * @param $storagePath
     * @param string $identifier
     *
     * @return WorkingDirectory
     * @throws FilePermissionException
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    public static function createNew($storagePath, $identifier='default-store')
    {
        if(!file_exists($storagePath)) {
            if(!mkdir($storagePath)) {
                throw new FilePermissionException('Storage path does not exist and can not be created.');
            }
        }

        $directory = self::getNextWorkingDirectoryPath($storagePath, $identifier);

        if(!is_writable(dirname($directory))) {
            throw new FilePermissionException(sprintf('Can not create new directory %s', $directory));
        }

        if(!is_dir($directory)) {
            mkdir($directory);
        }

        return new WorkingDirectory($directory);
    }

    /**
     * Return the name of what would be the next working directory.
     * @return string
     * @throws \Semknox\Core\Exceptions\ConfigurationException
     */
    protected static function getNextWorkingDirectoryPath($storagePath, $identifier)
    {
        $storagePath = rtrim($storagePath, '/');

        $time = date('Ymd') . '-' . (microtime(true) * 10000);

        return "$storagePath/$identifier-$time." . Status::PHASE_COLLECTING;
    }
}