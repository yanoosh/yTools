<?php
namespace yTools;

/**
 * @todo Add support expression "1-30/5".
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
	const SPLITER_DIVISOR = '/';
	protected $listDayOfWeek = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
	protected $listMonth = array(1 => 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
	protected $parsedExpression = array();
    protected $timeFromat = array(
        self::CRON_MINUTE => 'i',
        self::CRON_HOUR => 'H',
        self::CRON_DAY => 'd',
        self::CRON_MONTH => 'm',
        self::CRON_DAY_OF_WEEK => 'w',
    );

	public function __construct($expression) {
    	$tmp = preg_split('#\s+#', $expression);
    	$parsedExpression = array();
    	if (
        	4 < count($tmp)
        	&& null != ($parsedExpression[self::CRON_MINUTE] = $this->parseMinute($tmp[self::CRON_MINUTE]))
        	&& null != ($parsedExpression[self::CRON_HOUR] = $this->parseHour($tmp[self::CRON_HOUR]))
        	&& null != ($parsedExpression[self::CRON_DAY] = $this->parseDay($tmp[self::CRON_DAY]))
        	&& null != ($parsedExpression[self::CRON_MONTH] = $this->parseMonth($tmp[self::CRON_MONTH]))
        	&& null != ($parsedExpression[self::CRON_DAY_OF_WEEK] = $this->parseDayOfWeek($tmp[self::CRON_DAY_OF_WEEK]))
    	) {
        	$this->parsedExpression = $parsedExpression;
    	} else {
        	throw new \InvalidArgumentException();
    	}
	}

	public function checkDate(\DateTime $date) {
        $ret = true;
        foreach($this->parsedExpression as $pos => $set) {
            if (!( // Zaprzeczenie !!
                true === $set
                || in_array((int)$date->format($this->timeFromat[$pos]), $set)
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
        $minute = $date->format($this->timeFromat[self::CRON_MINUTE]);
        $hour = $date->format($this->timeFromat[self::CRON_HOUR]);
        $day = $date->format($this->timeFromat[self::CRON_DAY]);
        $month = $date->format($this->timeFromat[self::CRON_MONTH]);
        $year = $date->format('Y');
        
        $sets = $this->parsedExpression;
        $this->setArrayPosition($sets[self::CRON_MINUTE], $minute);
        $this->setArrayPosition($sets[self::CRON_HOUR], $hour);
        $this->setArrayPosition($sets[self::CRON_DAY], $day);
        $this->setArrayPosition($sets[self::CRON_MONTH], $month);
        $continue = true;
        $i = 0;
        do {
            !$this->nextValue(60, $sets[self::CRON_MINUTE], $minute)
            && !$this->nextValue(24, $sets[self::CRON_HOUR], $hour)
            && !$this->nextValue(32, $sets[self::CRON_DAY], $day, 1)
            && !$this->nextValue(13, $sets[self::CRON_MONTH], $month, 1)
            && $year++;
            if (checkdate($month, $day, $year)) {
                $date->setDate($year, $month, $day)->setTime($hour, $minute);
                if (true === $sets[self::CRON_DAY_OF_WEEK]
                    || in_array(
                    $date->format($this->timeFromat[self::CRON_DAY_OF_WEEK]),
                    $sets[self::CRON_DAY_OF_WEEK]
                    )
                ) {
                    $continue = false;
                } else {
                    $sets[self::CRON_MINUTE] === true ? $minute = -1: end($sets[self::CRON_MINUTE]);
                    $sets[self::CRON_HOUR] === true ? $hour = -1: end($sets[self::CRON_HOUR]);
                }
            }
        } while($continue);
        $date->setDate($year, $month, $day)->setTime($hour, $minute);
        return $date;
	}
    
    protected function setArrayPosition(&$set, $value) {
        if (is_array($set)) {
            $tmp = reset($set);
            while ($cond = ($tmp <= $value) && false !== ($tmp = next($set)));
            $cond? reset($set): prev($set);
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
    	$expr = str_replace($this->listDayOfWeek, array_keys($this->listDayOfWeek), $expr);
    	return $this->parseExpression(array(1, 12), $expr);
	}

	protected function parseDayOfWeek($expr) {
    	$expr = str_replace($this->listDayOfWeek, array_keys($this->listDayOfWeek), $expr);
    	return $this->parseExpression(array(0, 6), $expr);
	}
    
	protected function parseExpression($range, $expr, $noComa = false) {
    	if (self::ALL_VALUE == $expr[0]) {
        	if (isset($expr[1]) && self::SPLITER_DIVISOR == $expr[1]) {
            	$ret = range($range[0], $range[1], (int)substr($expr, 2));
        	} else {
            	$ret = true;
        	}
    	} elseif (false !== strpos(self::SPLITER_MULTIVALUE, $expr)) {
        	$tmp = array();
        	foreach(explode(self::SPLITER_MULTIVALUE, $expr) as $item) {
            	$tmp[] = $this->parseExpression($range, $expr);
        	}
        	$ret = call_user_func_array('array_merge', $tmp);
            sort($ret);
        	unset($tmp);
    	} elseif (false !== strpos(self::SPLITER_RANGE, $expr)) {
        	$tmp = explode(self::SPLITER_RANGE, $expr);
        	$ret = range(max($range[0], $tmp[0]), min($range[1], $tmp[1]));
    	} elseif($range[0] <= $expr && $range[1] >= $expr) {
        	$ret = array((int)$expr);
    	} else {
        	$ret =  null;
    	}
    	return $ret;
	}
}