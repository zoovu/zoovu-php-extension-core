<?php

namespace Semknox\Core\Services\ProductUpdate;


class Status
{
    const PHASE_COLLECTING = 'COLLECTING';

    const PHASE_UPLOADING = 'UPLOADING';

    const PHASE_COMPLETED = 'COMPLETED';

    const PHASE_ABORTED = 'ABORTED';

    private $statusFileName = 'info.json';

    /**
     * @var WorkingDirectory
     */
    protected $workingDirectory;

    /**
     * Status data, like number of collected products.
     * @var array
     */
    protected $data;

    /**
     * InitialUploadStatus constructor.
     *
     * @param array $status Status information
     */
    public function __construct(WorkingDirectory &$workingDirectory, array $config=[])
    {
        $this->workingDirectory = $workingDirectory;

        $statusFile = $workingDirectory($this->statusFileName);

        if(file_exists($statusFile)) {
            $content = file_get_contents($statusFile);

            $this->data = json_decode($content, true);
        }
        else {
            $this->data = $this->getDefaultStatus($config);
        }
    }

    public function __toString()
    {
        return $this->getPhase();
    }

    /**
     * Return the default status. This is used when no upload has been started yet.
     * @param array $config
     * @return array
     */
    public function getDefaultStatus($config=array())
    {
        $phase = is_dir($this->workingDirectory->getPath())
            ? self::PHASE_COLLECTING
            : self::PHASE_COMPLETED; // has status completed when no upload exists yet.

        $expectedNumberOfProducts = isset($config['expectedNumberOfProducts'])
            ? $config['expectedNumberOfProducts']
            : 0;

        return [
            'phase' => $phase,
            'startTime' => date('Y-m-d H:i:s'),
            'duration'  => 0,
            'collected' => 0,
            'uploaded'  => 0,
            'expectedNumberOfProducts' => $expectedNumberOfProducts
        ];
    }

    /**
     * Return if the current initial upload is still running (collecting or uploading).
     * @return bool
     */
    public function isRunning()
    {
        $phase = $this->getPhase();

        return in_array($phase, [
            self::PHASE_COLLECTING,
            self::PHASE_UPLOADING
        ]);
    }

    /**
     * Return if the current initial upload is stopped (aborted or completed).
     * @return bool
     */
    public function isStopped()
    {
        return !$this->isRunning();
    }

    /**
     * Return if the current upload status is "collecting".
     * @return bool
     */
    public function isCollecting()
    {
        return $this->getPhase() === self::PHASE_COLLECTING;
    }

    /**
     * Return if the current upload status is "uploading".
     * @return bool
     */
    public function isUploading()
    {
        return $this->getPhase() === Status::PHASE_UPLOADING;
    }

    /**
     * Return if the current upload status is "completed".
     * @return bool
     */
    public function isCompleted()
    {
        return $this->getPhase() === Status::PHASE_COMPLETED;
    }

    /**
     * Return if the current upload status is "aborted".
     * @return bool
     */
    public function isAborted()
    {
        return $this->getPhase() === Status::PHASE_ABORTED;
    }

    /**
     * Get the phase the upload is currently in. Can be
     *      self::STATUS_COLLECTING
     *      self::STATUS_UPLOADING
     *      self::STATUS_COMPLETED
     *      self::STATUS_ABORTED
     * @return mixed
     */
    public function getPhase()
    {
        return $this->data['phase'];
    }

    public function setPhase($phase)
    {
        $validPhases = [
            self::PHASE_COLLECTING,
            self::PHASE_UPLOADING,
            self::PHASE_COMPLETED,
            self::PHASE_ABORTED,
        ];

        if(!in_array($phase, $validPhases)) {
            throw new \RuntimeException('Invalid phase');
        }

        $this->data['phase'] = $phase;
    }

    /**
     * Increase number of collected products by $numCollected.
     * @param int $numCollected
     *
     * @return $this
     */
    public function increaseNumberOfCollected($numCollected=1)
    {
        $this->data['collected'] += $numCollected;

        return $this;
    }

    /**
     * Get the amount of already collected products.
     * @return int
     */
    public function getNumberOfCollected()
    {
        return $this->data['collected'];
    }

    /**
     * Get the the current progress of collecting products in percent (%).
     * @return int
     */
    public function getCollectingProgress()
    {
        $expected = $this->data['expectedNumberOfProducts'];

        return $expected
            ? round(($this->data['collected'] / $expected) * 100)
            : 0;
    }

    /**
     * Increase number of uploaded products by $numUploaded.
     * @param int $numUploaded
     *
     * @return $this
     */
    public function increaseNumberOfUploaded($numUploaded=1)
    {
        $this->data['uploaded'] += $numUploaded;

        return $this;
    }

    /**
     * Return number of already uploaded products.
     * @return int
     */
    public function getNumberOfUploaded()
    {
        return $this->data['uploaded'];
    }

    /**
     * Get the the current progress of uploading products in percent (%).
     * @return int
     */
    public function getUploadingProgress()
    {
        $expected = $this->data['expectedNumberOfProducts'];

        return $expected
            ? round(($this->data['uploaded'] / $expected) * 100)
            : 0;
    }

    /**
     * Return the total progress of this upload.
     */
    public function getTotalProgress()
    {
        $phase = $this->getPhase();

        switch($phase) {
            // collecting is the first 89% of the upload
            case self::PHASE_COLLECTING:
                return floor($this->getCollectingProgress() * 0.89);
                break;
            // uploading is the remaining 10% of the upload
            case self::PHASE_UPLOADING:
                return floor(89 + ($this->getUploadingProgress() * 0.1));
                break;
            // aborted or completed should be 100%
            default:
                return 100;
        }
    }

    /**
     * Persist current status on file system.
     */
    public function writeToFile()
    {
        $workingDirectory = $this->workingDirectory;

        $file = $workingDirectory($this->statusFileName);

        $content = json_encode($this->data);

        if(!is_dir(dirname($file))) {
            mkdir(dirname($file));
        }

        file_put_contents($file, $content);
    }
}