<?php

namespace yTools;

class ProcessFlag {
    const RUN_ONE_IN_THE_DAY = 1;
    const RUN_SECOND_PERIOD = 2;
    const RUN_MINUTE_PERIOD = 3;
    const RUN_HOUR_PERIOD = 4;
    const RUN_DAY_PERIOD = 5;
    const RELEASE_BEFORE = 128;
    const RELEASE_AFTER = 129;

    /**
     *
     * @var array
     */
    private $callFunction = null;
    /**
     *
     * @var integer
     */
    private $flagType = null;
    private $flagTypeValue = null;
    /**
     *
     * @var string
     */
    private $flagDir;
    /**
     *
     * @var string
     */
    private $flagPrefix;
    private $flagRelease = self::RELEASE_BEFORE;

    public function __construct($function, $object = null, $flagType = self::ONE_RUN_IN_THE_DAY, $flagTypeValue = null) {
        if (!empty($function)) {
            if (is_object($object)) {
                $tmp = array($object, $function);
            } else {
                $tmp = $function;
            }
            $this->callFunction = $tmp;
            $this->flagPrefix = $this->setFlagPrefix($function);
            $this->flagDir = dirname(__FILE__);
            $this->setFlagType($flagType, $flagTypeValue);
        }
    }

    /**
     *
     * @param string $prefix 
     */
    public function setFlagPrefix($prefix) {
        return $this->flagPrefix = mb_ereg_replace('[^a-zA-Z0-9]+', '_', $prefix);
    }

    public function setFlagDir($dir) {
        $this->flagDir = dirname($dir);
    }

    public function setFlagRelease($put) {
        if (
                in_array((int) $put, array(
                    self::RELEASE_BEFORE,
                    self::RELEASE_AFTER,
                ))
        ) {
            $this->flagRelease = (int) $put;
            return true;
        } else {
            return false;
        }
    }

    public function removeFlag() {
        if (file_exists($file = $this->getFlagFile())) {
            unlink($file);
            return!file_exists($file);
        } else {
            return true;
        }
    }

    public function run() {
        if (
                !empty($this->callFunction)
                && null != ($tmp = $this->isPossibleRun())
        ) {
            if ($this->flagRelease == self::RELEASE_BEFORE) {
                $retFlag = $this->createFlag();
            }
            $param = func_get_args();
            $return = call_user_func_array($this->callFunction, $param);
            if ($this->flagRelease == self::RELEASE_AFTER) {
                $retFlag = $this->createFlag();
            }
            return $return;
        }
        return null;
    }

    private function setFlagType($type, $value = null) {
        if (
                in_array((int) $type, array(
                    self::RUN_ONE_IN_THE_DAY,
                ))
        ) {
            $this->flagType = (int) $type;
            $this->flagTypeValue = 0;
            return true;
        } elseif (
                in_array((int) $type, array(
                    self::RUN_SECOND_PERIOD,
                    self::RUN_MINUTE_PERIOD,
                    self::RUN_HOUR_PERIOD,
                    self::RUN_DAY_PERIOD,
                ))
                && 0 < (int) $value
        ) {
            $this->flagType = (int) $type;
            $this->flagTypeValue = (int) $value;
            return true;
        } else {
            throw new Exception('Unknown flag type or wrong type value');
            return false;
        }
    }

    private function isPossibleRun() {
        $factor = 1;
        switch ($this->flagType) {
            case self::RUN_ONE_IN_THE_DAY:
                return ((int) date('Ymd')) > ((int) date('Ymd', $this->getFileTime()));
                break;
            case self::RUN_DAY_PERIOD:
                $factor *= 24;
            case self::RUN_HOUR_PERIOD:
                $factor *= 60;
            case self::RUN_MINUTE_PERIOD:
                $factor *= 60;
            case self::RUN_SECOND_PERIOD:
                return (time() - $factor * $this->flagTypeValue) > $this->getFileTime();
                break;
            default:
                return false;
        }
    }

    private function createFlag() {
        if (null != $file = fopen($this->getFlagFile(), 'w')) {
            fclose($file);
            return true;
        } else {
            throw new Exception('Can not create flag at ' . $this->getFlagFile());
            return false;
        }
    }

    private function getFileTime() {
        if (file_exists($file = $this->getFlagFile())) {
            return filemtime($file);
        } else {
            return -1;
        }
    }

    private function getFlagFile() {
        return $this->flagDir . DIRECTORY_SEPARATOR . sprintf('%s.flag', $this->flagPrefix);
    }

}