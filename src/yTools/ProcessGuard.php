<?php
namespace Tools;

class ProcessGuard {

    /**
     *
     * @var array
     */
    private $callFunction = null;
    /**
     *
     * @var boolean
     */
    private $addFlockObject = false;
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
    private $flockPrefix;
    
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

    public function __construct($function, $object = null) {
        if (!empty($function)) {
            if (is_object($object)) {
                $this->callFunction = function($args) use($object, $function) {
                    $tmp = new \ReflectionMethod(get_class($object), $function);
                    $tmp->setAccessible(true);
                    return $tmp->invokeArgs($object, $args);
                };
            } else {
                $this->callFunction = function($args) use($function) {
                    return call_user_func_array($function, $args);
                };
            }
            $this->setFlockPrefix($function);
            $this->flockDir = __DIR__;
        }
    }

    /**
     *
     * @param string $prefix 
     */
    public function setFlockPrefix($prefix) {
        $this->flockPrefix = mb_ereg_replace('[^a-zA-Z0-9]+', '_', $prefix);
    }

    /**
     *
     * @param type $path 
     */
    public function setFlockDir($path) {
        $this->flockDir = realpath($path);
    }

    /**
     *
     * @param integer $number 
     */
    public function setProcessNumber($number) {
        if (0 < (int) $number) {
            $this->processNumber = (int) $number;
        } else {
            $this->processNumber = 1;
        }
    }

    /**
     *
     * @param boolean $enable 
     */
    public function setLastParamFlockObject($enable) {
        $this->addFlockObject = (bool) $enable;
    }

    public function getNumberOfLocks() {
        return count($this->getLocksProcessInfo());
    }
    
    public function getLocksProcessInfo() {
        $ids = array();
        for ($flockNumber = 0; $flockNumber < $this->processNumber; $flockNumber++) {
            $filePath = $this->getLockFile($flockNumber);
            if (
                file_exists($filePath)
                && null != ($flockFile = fopen($filePath, 'r'))
            ) {
                if (!flock($flockFile, LOCK_EX | LOCK_NB)) {
                    $ids[] = array(
                        'process_id' => (int)fread($flockFile, 32),
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
    
    public function getFlockNumber() {
        return $this->flockNumber;
    }
            
    
    public function getProcessId() {
        return $this->processId;
    }

    /**
     * @todo Uruchamianie po funckji anonimowej
     *
     * @return type 
     */
    public function run() {
        if ($this->findAndLock()) {
            if ($this->addFlockObject) {
                $param[] = $this;
            }
            $return = call_user_func($this->callFunction, func_get_args());
            $this->unlock();
            return $return;
        } else {
            
            return null;
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
     * @param type $flockNumber
     * @param integer $processId
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