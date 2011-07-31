<?php

namespace yTools;

require_once dirname(__FILE__) . '/../../src/yTools/CronExpression.php';

/**
 * Test class for CronExpression.
 * Generated by PHPUnit on 2011-07-21 at 19:21:18.
 */
class CronExpressionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var CronExpression[]
     */
    protected $object;
    /**
     *
     * @var string[]
     */
    protected $dateList = array();

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = array(
            0 => new CronExpression('* * * * *'),
            1 => new CronExpression('*/2 * * * *'),
            2 => new CronExpression('*/3 * * * *'),
            3 => new CronExpression('*/3 * */5 * sun'),
            4 => new CronExpression('10-15 6-9 * * *'),
            5 => new CronExpression('13,16,19,22 12,17,22 * * *'),
            6 => new CronExpression('1-10,30-40 */5,*/3 * 7 *'),
            7 => new CronExpression('10-30/3 0-11/2 * * */2'),
//            6 => new CronExpression('0-59 0-23 1-31 1-12 0-6'),
//            7 => new CronExpression('0-70 0-70 0-70 0-70 0-70'),
        );
        $this->dateList = array(
            new \DateTime('2011-07-21 22:13:00'),
            new \DateTime('2011-08-23 09:14:00'),
            new \DateTime('2010-02-01 06:15:00'),
            new \DateTime('2013-10-23 12:16:00'),
            new \DateTime('2011-05-01 00:06:00'),
            new \DateTime('2011-07-31 04:21:00'),
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }

    public function testConstruct() {
        try {
            new CronExpression('*');
            $this->fail('An expected exception has not been raised.');
        } catch (\InvalidArgumentException $expected) {}
        try {
            new CronExpression('a b c d e');
            $this->fail('An expected exception has not been raised.');
        } catch (\InvalidArgumentException $expected) {}
    }

    /**
     * @todo Implement testCheckDate().
     */
    public function testCheckDate() {
        $result = array(
            0 => array(true,  true,  true,  true,  true,  true),
            1 => array(false, true,  false, true,  true,  false),
            2 => array(false, false, true,  false, true,  true),
            3 => array(false, false, false, false, true,  true),
            4 => array(false, true,  true,  false, false, false),
            5 => array(true,  false, false, true,  false, false),
            6 => array(false, false, false, false, false, false),
            7 => array(false, false, false, false, false, true),
        );
        foreach ($this->object as $objectID => $object) {
            foreach ($this->dateList as $dateID => $date) {
                $this->assertEquals(
                    $result[$objectID][$dateID], $object->checkDate($date), sprintf('CronExpression object ID: %d, date ID: %d.', $objectID, $dateID)
                );
            }
        }
    }

    /**
     * @todo Implement testGetNextDate().
     */
    public function testGetNextDate() {
        $resultDate = array(
            0 => array(
                '2011-07-21 22:14:00',
                '2011-08-23 09:15:00',
                '2010-02-01 06:16:00',
                '2013-10-23 12:17:00',
                '2011-05-01 00:07:00',
                '2011-07-31 04:22:00',
            ),
            1 => array(
                '2011-07-21 22:14:00',
                '2011-08-23 09:16:00',
                '2010-02-01 06:16:00',
                '2013-10-23 12:18:00',
                '2011-05-01 00:08:00',
                '2011-07-31 04:22:00',
            ),
            2 => array(
                '2011-07-21 22:15:00',
                '2011-08-23 09:15:00',
                '2010-02-01 06:18:00',
                '2013-10-23 12:18:00',
                '2011-05-01 00:09:00',
                '2011-07-31 04:24:00',
            ),
            3 => array(
                '2011-07-31 00:00:00',
                '2011-09-11 00:00:00',
                '2010-02-21 00:00:00',
                '2013-12-01 00:00:00',
                '2011-05-01 00:09:00',
                '2011-07-31 04:24:00',
            ),
            4 => array(
                '2011-07-22 06:10:00',
                '2011-08-23 09:15:00',
                '2010-02-01 07:10:00',
                '2013-10-24 06:10:00',
                '2011-05-01 06:10:00',
                '2011-07-31 06:10:00',
            ),
            5 => array(
                '2011-07-21 22:16:00',
                '2011-08-23 12:13:00',
                '2010-02-01 12:13:00',
                '2013-10-23 12:19:00',
                '2011-05-01 12:13:00',
                '2011-07-31 12:13:00',
            ),
            6 => array(
                '2011-07-22 00:01:00',
                '2012-07-01 00:01:00',
                '2010-07-01 00:01:00',
                '2014-07-01 00:01:00',
                '2011-07-01 00:01:00',
                '2011-07-31 05:01:00',
            ),
            7 => array(
                '2011-07-23 00:12:00',
                '2011-08-23 10:12:00',
                '2010-02-02 00:12:00',
                '2013-10-24 00:12:00',
                '2011-05-01 00:12:00',
                '2011-07-31 04:24:00',
            )
        );
        foreach ($this->object as $objectID => $object) {
            foreach ($this->dateList as $dateID => $date) {
                $this->assertEquals(
                    new \DateTime($resultDate[$objectID][$dateID]), 
                    $object->getNextDate($date), 
                    sprintf('CronExpression object ID: %d, date: %s.', $objectID, $date->format('Y-m-d H:i:s'))
                );
            }
        }

        $dateIn = new \DateTime();
        $dateOut = $this->object[0]->getNextDate();
        $this->assertType('DateTime', $dateOut);
        $interval = $dateOut->diff($dateIn);
        $this->assertEquals(1, $interval->invert);
    }

}