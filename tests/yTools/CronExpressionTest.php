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
     * @var array
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
            3 => new CronExpression('*/3 * */5 * sun')
        );
        $this->dateList = array(
            new \DateTime('2011-07-21 22:13:00'),
            new \DateTime('2011-08-23 09:14:00'),
            new \DateTime('2010-02-01 06:15:00'),
            new \DateTime('2013-10-23 12:16:00'),
            new \DateTime('2011-05-01 00:06:00'),
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }

    /**
     * @todo Implement testCheckDate().
     */
    public function testCheckDate() {
        $result = array(
            0 => array(true, true, true, true, true),
            1 => array(false, true, false, true, true),
            2 => array(false, false, true, false, true),
            3 => array(false, false, false, false, true),
        );
        foreach($this->object as $objectID => $object) {
            foreach ($this->dateList as $dateID => $date) {
                $this->assertEquals(
                    $result[$objectID][$dateID], 
                    $object->checkDate($date),
                    sprintf('CronExpression object ID: %d, date ID: %d.', $objectID, $dateID)
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
            ),
            // '*/2 * * * *'
            1 => array(
                '2011-07-21 22:14:00',
                '2011-08-23 09:16:00',
                '2010-02-01 06:16:00',
                '2013-10-23 12:18:00',
                '2011-05-01 00:08:00',
            ),
            2 => array(
                '2011-07-21 22:15:00',
                '2011-08-23 09:15:00',
                '2010-02-01 06:18:00',
                '2013-10-23 12:18:00',
                '2011-05-01 00:09:00',
            ),
            3 => array(
                '2011-07-31 00:00:00',
                '2011-09-11 00:00:00',
                '2010-02-21 00:00:00',
                '2013-12-01 00:00:00',
                '2011-05-01 00:09:00',
            ),
        );
        foreach($this->object as $objectID => $object) {
            foreach ($this->dateList as $dateID => $date) {
                $this->assertEquals(
                    new \DateTime($resultDate[$objectID][$dateID]), 
                    $object->getNextDate($date),
                    sprintf('CronExpression object ID: %d, date: %s.', $objectID, $date->format('Y-m-d H:i:s'))
                );
            }
        }
    }

}