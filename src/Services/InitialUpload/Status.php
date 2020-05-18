<?php

namespace Semknox\Core\Services\InitialUpload;


class Status
{
    const PHASE_COLLECTING = 'COLLECTING';

    const PHASE_UPLOADING = 'UPLOADING';

    const PHASE_COMPLETED = 'COMPLETED';

    const PHASE_ABORTED = 'ABORTED';

    private $statusFileName = 'info.json';

    protected $workingDirectory;

    protected $data;

    /**
     * InitialUploadStatus constructor.
     *
     * @param array $status Status information
     */
    public function __construct(WorkingDirectory &$workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;

        $statusFile = $workingDirectory($this->statusFileName);

        if(file_exists($statusFile)) {
            $content = file_get_contents($statusFile);

            $this->data = json_decode($content, true);
        }
        else {
            $this->data = $this->getDefaultStatus();
        }
    }

    public function __toString()
    {
        return $this->getPhase();
    }

    /**
     * Return the default status. This is useful e.g. if the upload has not started yet.
     * @return array
     */
    public function getDefaultStatus()
    {
        return [
            'phase' => self::PHASE_COLLECTING,
            'startTime' => date('Y-m-d H:i:s'),
            'duration'  => 0,
            'collected' => 0,
            'uploaded'  => 0
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

    public function addCollected($numCollected=1)
    {
        $this->data['collected'] += $numCollected;

        return $this;
    }

    public function addUploaded($numUploaded=1)
    {
        $this->data['collected'] += $numUploaded;

        return $this;
    }

    public function writeToFile()
    {
        if($this->data !== $this->getDefaultStatus()) {
            $workingDirectory = $this->workingDirectory;

            $file = $workingDirectory($this->statusFileName);

            $content = json_encode($this->data);

            file_put_contents($file, $content);
        }
    }
}