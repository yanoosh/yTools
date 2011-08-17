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
    private $procMaxNumber = 1;

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

    /**
     *
     * @param string $lockDirectory The path where flockes will be saved.
     */
    public function __construct($lockDirectory) {
        if (!is_dir($lockDirectory)) {
            throw new \InvalidArgumentException('Given path does not exists or is not a directory. ' . $lockDirectory);
        }
        if (!is_writable($lockDirectory)) {
            throw new \InvalidArgumentException('In given path could not write a file.');
        }
        $this->lockDir = $lockDirectory;
    }

    /**
     * Gets the path where flockes will be saved.
     *
     * @return string
     */
    public function getLockDirectory() {
        return $this->lockDir;
    }

    /**
     * Sets the prefix name of locked files.
     * 
     * @param string $prefix
     * @return ProcessGuard Returns this object.
     */
    public function setLockPrefix($prefix) {
        $this->lockPrefix = mb_ereg_replace('[^a-zA-Z0-9]+', '_', $prefix);
        return $this;
    }

    /**
     * Gets the prefix name of locked files.
     *
     * @return int
     */
    public function getLockPrefix() {
        return $this->lockPrefix;
    }

    /**
     * Sets maximum number of processes which could be run.
     *
     * @param int $number
     * @return ProcessGuard Returns this object.
     */
    public function setProcMaxNumber($number) {
        if (0 < (int) $number) {
            $this->procMaxNumber = (int) $number;
        } else {
            $this->procMaxNumber = 1;
        }
        return $this;
    }

    /**
     * Gets maximum processes number which could be run.
     *
     * @return int
     */
    public function getProcMaxNumber() {
        return $this->procMaxNumber;
    }

    /**
     * Returns a number of running processes.
     *
     * @return integer
     */
    public function getProcNumber() {
        return count($this->getProcInfo());
    }

    /**
     * Gets a information about running processes.
     *
     * @return array
     */
    public function getProcInfo() {
        $ids = array();
        for ($lockId = 1; $lockId <= $this->procMaxNumber; $lockId++) {
            $filePath = $this->getLockFile($lockId);
            if (
                file_exists($filePath)
                && null != ($lockFile = fopen($filePath, 'r'))
            ) {
                if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
                    $ids[] = array(
                        'process_id' => (int) fread($lockFile, 32),
                        'lock_id' => $lockId,
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
     * Returns lock id of running process.
     *
     * @return integer
     */
    public function getLockId() {
        return $this->lockNumber;
    }

    /**
     * Gets process id of running process.
     *
     * @return integer
     */
    public function getProcId() {
        return $this->processId;
    }

    /**
     * Sets up lock and runs a function.
     *
     * @param type $function Function to call.
     * @param array $param Function parameters.
     * @return mix The returned value from function.
     * @throws \RuntimeException, \InvalidArgumentException
     */
    public function run($function, array $param = array()) {
        if (is_callable($function)) {
            if ($this->findAndLock()) {
                $return = call_user_func_array($function, $param);
                $this->unlock();
                return $return;
            } else {

                throw new \OverflowException('Too many running processes.');
            }
        } else {
            throw new \BadFunctionCallException('The given function is not callable.');
        }
    }

    public function __clone() {
        $this->unlock();
    }

    private function findAndLock() {
        for ($lockNumber = 1; $lockNumber <= $this->procMaxNumber; $lockNumber++) {
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
            '%s-%03d-%03d.flock', $this->lockPrefix, $this->procMaxNumber, $processNumber
        );
    }

}