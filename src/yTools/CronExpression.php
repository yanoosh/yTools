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

/**
 * @todo Add support for expressions that begin with "@".
 */
class CronExpression {
    const CRON_MINUTE = 0;
    const CRON_HOUR = 1;
    const CRON_DAY = 2;
    const CRON_MONTH = 3;
    const CRON_DAY_OF_WEEK = 4;
    const ALL_VALUE = '*';
    const SPLITER_MULTIVALUE = ',';
    const SPLITER_RANGE = '-';
    const SPLITER_DIVISION = '/';
    const VALUE_FOUND = 0;
    const VALUE_IN_RANGE = 1;
    const VALUE_RESET = 2;
    static protected $listDayOfWeek = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
    static protected $listMonth = array(1 => 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
    static protected $timeFromat = array(
        self::CRON_MINUTE => 'i',
        self::CRON_HOUR => 'H',
        self::CRON_DAY => 'd',
        self::CRON_MONTH => 'm',
        self::CRON_DAY_OF_WEEK => 'w',
    );
    protected $parsedExpression;

    public function __construct($expression) {
        $tmp = mb_split('\\s+', trim($expression));
        $parsedExpression = array();
        if (4 < count($tmp)) {
            try {
                $parsedExpression[self::CRON_MINUTE] = $this->parseMinute($tmp[self::CRON_MINUTE]);
                $parsedExpression[self::CRON_HOUR] = $this->parseHour($tmp[self::CRON_HOUR]);
                $parsedExpression[self::CRON_DAY] = $this->parseDay($tmp[self::CRON_DAY]);
                $parsedExpression[self::CRON_MONTH] = $this->parseMonth($tmp[self::CRON_MONTH]);
                $parsedExpression[self::CRON_DAY_OF_WEEK] = $this->parseDayOfWeek($tmp[self::CRON_DAY_OF_WEEK]);
                $this->parsedExpression = $parsedExpression;
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException();
            }
        } else {
            throw new \InvalidArgumentException();
        }
    }

    public function checkDate(\DateTime $date) {
        $ret = true;
        foreach ($this->parsedExpression as $pos => $set) {
            if (!( // Negation !!
                true === $set
                || in_array((int) $date->format(static::$timeFromat[$pos]), $set)
                )) {
                $ret = false;
                break;
            }
        }
        return $ret;
    }

    /**
     *
     * @param \DateTime $date 
     */
    public function getNextDate(\DateTime $date = null) {
        if ($date === null) {
            $date = new \DateTime();
        } else {
            $date = clone $date;
        }
        $baseTimestamp = $date->getTimestamp();
        $minute = $date->format(static::$timeFromat[self::CRON_MINUTE]);
        $hour = $date->format(static::$timeFromat[self::CRON_HOUR]);
        $day = $date->format(static::$timeFromat[self::CRON_DAY]);
        $month = $date->format(static::$timeFromat[self::CRON_MONTH]);
        $year = $date->format('Y');

        $sets = $this->parsedExpression;
        if (self::VALUE_FOUND != ($tmp = $this->setArrayPosition(&$sets[self::CRON_MONTH], &$month))) {
            if (self::VALUE_RESET === $tmp) {
                $year++;
            }
            $day = null;
        }
        if (self::VALUE_FOUND != $this->setArrayPosition(&$sets[self::CRON_DAY], &$day)) {
            $hour = null;
        };
        if (self::VALUE_FOUND != $this->setArrayPosition(&$sets[self::CRON_HOUR], &$hour)) {
            $minute = null;
        }
        $this->setArrayPosition(&$sets[self::CRON_MINUTE], &$minute);
        $continue = true;
        $i = 0;
        do {
            if (checkdate($month, $day, $year)) {
                $date->setDate($year, $month, $day)->setTime($hour, $minute);
                if ($baseTimestamp < $date->getTimestamp()) {
                    if (
                        true === $sets[self::CRON_DAY_OF_WEEK]
                        || in_array(
                            $date->format(static::$timeFromat[self::CRON_DAY_OF_WEEK]), $sets[self::CRON_DAY_OF_WEEK]
                        )
                    ) {
                        $continue = false;
                    } else {
                        $sets[self::CRON_MINUTE] === true ? $minute = -1 : end($sets[self::CRON_MINUTE]);
                        $sets[self::CRON_HOUR] === true ? $hour = -1 : end($sets[self::CRON_HOUR]);
                    }
                }
            }
            !$this->nextValue(60, $sets[self::CRON_MINUTE], $minute)
            && !$this->nextValue(24, $sets[self::CRON_HOUR], $hour)
            && !$this->nextValue(32, $sets[self::CRON_DAY], $day, 1)
            && !$this->nextValue(13, $sets[self::CRON_MONTH], $month, 1)
            && $year++;
        } while ($continue);
        return $date;
    }

    /**
     *
     * @param array $array Array with numbers.
     * @param integer $value Searching value.
     * @return boolean If find equal value return true, otherwise false.
     */
    protected function setArrayPosition(&$array, &$value) {
        if (is_array($array)) {
            if (null !== $value) {
                $tmp = reset($array);
                do {
                    if ($tmp > $value) {
                        $value = $tmp;
                        return self::VALUE_IN_RANGE;
                    } elseif ($tmp == $value) {
                        return self::VALUE_FOUND;
                    }
                } while (false !== ($tmp = next($array)));
            }
            $value = reset($array);
            return self::VALUE_RESET;
        } elseif (true === $array) {
            return self::VALUE_FOUND;
        }
    }

    protected function nextValue($mod, &$set, &$value, $minValue = 0) {
        if ($set === true) {
            $value = (($value + 1) % $mod);
            if (0 == $value) {
                $value = $minValue;
                return false;
            } else {
                return true;
            }
        } else {
            if (false !== ($value = next($set))) {
                return true;
            } else {
                $value = reset($set);
                return false;
            }
        }
    }

    protected function parseMinute($expr) {
        return $this->parseExpression(array(0, 59), $expr);
    }

    protected function parseHour($expr) {
        return $this->parseExpression(array(0, 23), $expr);
    }

    protected function parseDay($expr) {
        return $this->parseExpression(array(1, 31), $expr);
    }

    protected function parseMonth($expr) {
        $expr = str_replace(static::$listMonth, array_keys(static::$listMonth), $expr);
        return $this->parseExpression(array(1, 12), $expr);
    }

    protected function parseDayOfWeek($expr) {
        $expr = str_replace(static::$listDayOfWeek, array_keys(static::$listDayOfWeek), $expr);
        return $this->parseExpression(array(0, 6), $expr);
    }

    protected function parseExpression($range, $expr, $noComa = false) {
        if (false !== strpos($expr, self::SPLITER_MULTIVALUE)) {
            $tmp = array();
            foreach (explode(self::SPLITER_MULTIVALUE, $expr) as $item) {
                $tmp[] = $this->parseExpression($range, $item);
            }
            $ret = call_user_func_array('array_merge', $tmp);
            sort($ret);
            unset($tmp);
        } elseif (false !== mb_ereg('^(.*?)' . self::SPLITER_DIVISION . '([0-9]+)$', $expr, $regs)) {
            $dividend = $this->parseExpression($range, $regs[1]);
            $divisor = (int)$regs[2];
            if (true === $dividend) {
                $ret = range($range[0], $range[1], $divisor);
            } else {
                $ret = array_filter($dividend, function($dividend) use ($range, $divisor) {
                   return 0 == ($dividend + $range[0]) % $divisor;
                });
            }
        } elseif (false !== strpos($expr, self::SPLITER_RANGE)) {
            $tmp = explode(self::SPLITER_RANGE, $expr);
            $ret = range(max($range[0], $tmp[0]), min($range[1], $tmp[1]));
        } elseif (self::ALL_VALUE == $expr) {
            $ret = true;
        } elseif ($range[0] <= $expr && $range[1] >= $expr) {
            $ret = array((int) $expr);
        } else {
            throw new \InvalidArgumentException();
        }
        return $ret;
    }
    
}