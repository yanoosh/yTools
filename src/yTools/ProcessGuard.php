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
     * @var integer
     */
    private $processNumber = 1;
    /**
     *
     * @var file
     */
    private $lockFile = null;
    /**
     *
     * @var string
     */
    private $lockDir;
    /**
     *
     * @var string
     */
    private $lockPrefix = 'ProcessGuard';
    /**
     *
     * @var integer
     */
    private $lockNumber = null;
    /**
     *
     * @var integer
     */
    private $processId = null;

    public function __construct() {
        $this->lockDir = getcwd();
    }

    /**
     * Sets the prefix name of locked files.
     * 
     * @param string $prefix
     * @return ProcessGuard Returns this object.
     */
    public function setlockPrefix($prefix) {
        $this->lockPrefix = mb_ereg_replace('[^a-zA-Z0-9]+', '_', $prefix);
        return $this;
    }

    /**
     * Sets the path where lockes will be saved.
     *
     * @param string $path
     * @return ProcessGuard Returns this object.
     */
    public function setlockDir($path) {
        $this->lockDir = realpath($path);
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
    public function getNumberOlocks() {
        return count($this->getLocksProcessInfo());
    }

    /**
     * Gets a information about running processes.
     *
     * @return array
     */
    public function getRunningProcessesInfo() {
        $ids = array();
        for ($lockNumber = 0; $lockNumber < $this->processNumber; $lockNumber++) {
            $filePath = $this->getLockFile($lockNumber);
            if (
                file_exists($filePath)
                && null != ($lockFile = fopen($filePath, 'r'))
            ) {
                if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
                    $ids[] = array(
                        'process_id' => (int) fread($lockFile, 32),
                        'lock_number' => $lockNumber,
                        'file_path' => $filePath,
                        'modified_date_time' => filemtime($filePath),
                    );
                }
                fclose($lockFile);
            }
        }
        return $ids;
    }

    /**
     * Returns lock number of running process.
     *
     * @return integer
     */
    public function getlockNumber() {
        return $this->lockNumber;
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

    public function __clone() {
        $this->unlock();
    }

    private function findAndLock() {
        for ($lockNumber = 0; $lockNumber < $this->processNumber; $lockNumber++) {
            if ($this->lock($lockNumber)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param integer $lockNumber
     * @return bool
     */
    private function lock($lockNumber) {
        $lockFile = $this->getLockFile($lockNumber);
        if (is_resource($this->lockFile)) {
            throw new \RuntimeException('The object is already in use.');
        }
        $this->lockFile = fopen($lockFile, 'a');
        if (flock($this->lockFile, LOCK_EX | LOCK_NB)) {
            ftruncate($this->lockFile, 0);
            fwrite($this->lockFile, $tmp = getmypid());
            $this->lockNumber = $lockNumber;
            $this->processId = $tmp;
            return true;
        } else {
            $this->unlock();
            return false;
        }
    }

    private function unlock() {
        fclose($this->lockFile);
        $this->lockNumber = null;
        $this->processId = null;
        $this->lockFile = null;
    }

    private function getLockFile($processNumber) {
        return $this->lockDir . DIRECTORY_SEPARATOR . sprintf(
            '%s-%03d-%03d.flock', $this->lockPrefix, $this->processNumber, $processNumber
        );
    }

}