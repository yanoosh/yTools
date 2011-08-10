<?php

/**
 * This file is part of the yTools package.
 *
 * (c) Janusz Jablonski <januszjablonski.pl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yTools;

class ProcessGuard {

    /**
     *
     * @var array
     */
    private $callFunction = null;
    /**
     *
     * @var integer
     */
    private $processNumber = 1;
    /**
     *
     * @var file
     */
    private $flockFile = null;
    /**
     *
     * @var string
     */
    private $flockDir;
    /**
     *
     * @var string
     */
    private $flockPrefix = 'ProcessGuard';
    /**
     *
     * @var integer
     */
    private $flockNumber = null;
    /**
     *
     * @var integer
     */
    private $processId = null;

    public function __construct() {
        $this->flockDir = getcwd();
    }

    /**
     * Sets the prefix name of flocked files.
     * 
     * @param string $prefix
     * @return ProcessGuard Returns this object.
     */
    public function setFlockPrefix($prefix) {
        $this->flockPrefix = mb_ereg_replace('[^a-zA-Z0-9]+', '_', $prefix);
        return $this;
    }

    /**
     * Sets the path where flockes will be saved.
     *
     * @param string $path
     * @return ProcessGuard Returns this object.
     */
    public function setFlockDir($path) {
        $this->flockDir = realpath($path);
        return $this;
    }

    /**
     * Sets maximum processes number which could be run.
     *
     * @param int $number
     * @return ProcessGuard Returns this object.
     */
    public function setProcessNumber($number) {
        if (0 < (int) $number) {
            $this->processNumber = (int) $number;
        } else {
            $this->processNumber = 1;
        }
        return $this;
    }

    /**
     * Returns a number of locked files.
     *
     * @return integer
     */
    public function getNumberOfLocks() {
        return count($this->getLocksProcessInfo());
    }

    /**
     * Gets a information about running processes.
     *
     * @return array
     */
    public function getRunningProcessesInfo() {
        $ids = array();
        for ($flockNumber = 0; $flockNumber < $this->processNumber; $flockNumber++) {
            $filePath = $this->getLockFile($flockNumber);
            if (
                file_exists($filePath)
                && null != ($flockFile = fopen($filePath, 'r'))
            ) {
                if (!flock($flockFile, LOCK_EX | LOCK_NB)) {
                    $ids[] = array(
                        'process_id' => (int) fread($flockFile, 32),
                        'flock_number' => $flockNumber,
                        'file_path' => $filePath,
                        'modified_date_time' => filemtime($filePath),
                    );
                }
                fclose($flockFile);
            }
        }
        return $ids;
    }

    /**
     * Returns flock number of running process.
     *
     * @return integer
     */
    public function getFlockNumber() {
        return $this->flockNumber;
    }

    /**
     * Gets process number of running process.
     *
     * @return integer
     */
    public function getProcessId() {
        return $this->processId;
    }

    /**
     * @return mix
     * @throws \RuntimeException, \InvalidArgumentException
     */
    public function run($function, array $param = array()) {
        if (is_callable($function)) {
            if ($this->findAndLock()) {
                $return = call_user_func_array($function, $param);
                $this->unlock();
                return $return;
            } else {

                throw new \RuntimeException('Too many running processes.');
            }
        } else {
            throw new \InvalidArgumentException('Given value is not a callable function.');
        }
    }

    private function findAndLock() {
        for ($flockNumber = 0; $flockNumber < $this->processNumber; $flockNumber++) {
            if ($this->lock($flockNumber)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param integer $flockNumber
     * @return bool
     */
    private function lock($flockNumber) {
        $flockFile = $this->getLockFile($flockNumber);
        if (is_resource($this->flockFile)) {
            $this->unlock();
        }
        $this->flockFile = fopen($flockFile, 'a');
        if (flock($this->flockFile, LOCK_EX | LOCK_NB)) {
            ftruncate($this->flockFile, 0);
            fwrite($this->flockFile, $tmp = getmypid());
            $this->flockNumber = $flockNumber;
            $this->processId = $tmp;
            return true;
        } else {
            $this->unlock();
            return false;
        }
    }

    private function unlock() {
        fclose($this->flockFile);
        $this->flockNumber = null;
        $this->processId = null;
        $this->flockFile = null;
    }

    private function getLockFile($processNumber) {
        return $this->flockDir . DIRECTORY_SEPARATOR . sprintf(
            '%s-%03d-%03d.flock', $this->flockPrefix, $this->processNumber, $processNumber
        );
    }

}