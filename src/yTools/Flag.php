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

use yTools\Exception\TooEarlyToRunException;

class Flag {
    const RUN_ONE_IN_THE_DAY = 1;
    const RUN_SECOND_PERIOD = 2;
    const RUN_MINUTE_PERIOD = 3;
    const RUN_HOUR_PERIOD = 4;
    const RUN_DAY_PERIOD = 5;
    const RELEASE_BEFORE_RUN_FUNC = 'before';
    const RELEASE_AFTER_RUN_FUNC = 'after';

    /**
     * @var integer
     */
    private $type = null;
    private $periodInterval = null;

    /**
     * @var string
     */
    private $flagDirectory;

    /**
     * @var string
     */
    private $flagPrefix = 'ProcessFlag';
    private $flagRelease = self::RELEASE_BEFORE_RUN_FUNC;

    /**
     *
     * @param strng $flagDirectory
     * @param integer $type
     * @param type $periodInterval
     * @throws \InvalidArgumentException
     */
    public function __construct($flagDirectory, $type = self::RUN_ONE_IN_THE_DAY, $periodInterval = 0) {
        if (!is_dir($flagDirectory)) {
            throw new \InvalidArgumentException('Given path does not exists or is not a directory. ' . $flagDirectory);
        }
        if (!is_writable($flagDirectory)) {
            throw new \InvalidArgumentException('In given path could not write a file.');
        }
        $this->flagDirectory = $flagDirectory;
        $this->setFlagType($type, $periodInterval);
    }

    public function getFlagDirectory() {
        return $this->flagDirectory;
    }

    public function getType() {
        return $this->type;
    }

    public function getPeriodInterval() {
        return $this->periodInterval;
    }

    public function setFlagPrefix($prefix) {
        return $this->flagPrefix = mb_ereg_replace('[^a-zA-Z0-9]+', '_', $prefix);
    }

    public function getFlagPrefix() {
        return $this->flagPrefix;
    }

    public function setFlagRelease($constRelease) {
        if (
            in_array((int) $constRelease, array(
                self::RELEASE_BEFORE_RUN_FUNC,
                self::RELEASE_AFTER_RUN_FUNC,
            ))
        ) {
            $this->flagRelease = (int) $constRelease;
            return true;
        } else {
            return false;
        }
    }

    public function getFlagRelease() {
        return $this->flagRelease;
    }

    public function removeFlag() {
        if (file_exists($file = $this->getFlagFilePath())) {
            unlink($file);
            return!file_exists($file);
        } else {
            return true;
        }
    }

    /**
     * Check flag and runs a function.
     *
     * @param type $function Function to call.
     * @param array $param Function parameters.
     * @return mix The returned value from function.
     * @throws \BadFunctionCallException, \Exception
     */
    public function run($function, array $param = array()) {
        if (is_callable($function)) {
            if (null != ($tmp = $this->isPossibleRun())) {
                if ($this->flagRelease == self::RELEASE_BEFORE_RUN_FUNC) {
                    $retFlag = $this->createFlag();
                }
                $return = call_user_func_array($function, $param);
                if ($this->flagRelease == self::RELEASE_AFTER_RUN_FUNC) {
                    $retFlag = $this->createFlag();
                }
                return $return;
            } else {
                throw new \Exception('Too early to run the given function');
            }
        } else {
            throw new \BadFunctionCallException('The given function is not callable.');
        }
    }

    private function setFlagType($type, $periodInterval) {
        if (
            in_array((int) $type, array(
                self::RUN_ONE_IN_THE_DAY,
            ))
        ) {
            $this->type = (int) $type;
            $this->periodInterval = 0;
            return true;
        } elseif (
            in_array((int) $type, array(
                self::RUN_SECOND_PERIOD,
                self::RUN_MINUTE_PERIOD,
                self::RUN_HOUR_PERIOD,
                self::RUN_DAY_PERIOD,
            ))
            && 0 < (int) $periodInterval
        ) {
            $this->type = (int) $type;
            $this->periodInterval = (int) $periodInterval;
            return true;
        } else {
            throw new \Exception('Unknown flag type or wrong periond value');
            return false;
        }
    }

    private function isPossibleRun() {
        $factor = 1;
        switch ($this->type) {
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
                return (time() - $factor * $this->periodInterval) > $this->getFileTime();
                break;
            default:
                return false;
        }
    }

    private function createFlag() {
        if (null != $file = fopen($this->getFlagFilePath(), 'w')) {
            fclose($file);
            return true;
        } else {
            throw new \Exception('Can not create flag at ' . $this->getFlagFilePath());
            return false;
        }
    }

    private function getFileTime() {
        if (file_exists($file = $this->getFlagFilePath())) {
            return filemtime($file);
        } else {
            return -1;
        }
    }

    private function getFlagFilePath() {
        return $this->flagDirectory . DIRECTORY_SEPARATOR . sprintf('%s.flag', $this->flagPrefix);
    }

}