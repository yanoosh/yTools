<?php

namespace yTools;

class DesignePatternDecoratorTest extends \PHPUnit_Framework_TestCase {

    protected $object = null;

    public function setUp() {
        $this->object = new TestDecorator(new HelperClassDesignePatternDecoratorTest());
    }

    public function testCall() {
        $this->assertEquals('callObjectFunction', $this->object->callObjectFunction());
        $this->assertEquals('callStaticFunction', $this->object->callStaticFunction());
        $object = $this->object;
        $this->assertEquals('callStaticFunction', $object::callStaticFunction());
    }
    
    public function testVariable() {
        $this->assertEquals('variableObject', $this->object->variableObject);
        $this->object->variableObject = 'variableObject1';
        $this->assertEquals('variableObject1', $this->object->variableObject);
        $this->assertEquals(false, isset($this->object->variableTest));
        $this->object->variableTest = 'variableTest';
        $this->assertEquals('variableTest', $this->object->variableTest);
        $this->assertEquals(true, isset($this->object->variableTest));
        unset($this->object->variableTest);
        $this->assertEquals(false, isset($this->object->variableTest));
    }

}

class HelperClassDesignePatternDecoratorTest {

    public $variableObject = 'variableObject';

    public static function callStaticFunction() {
        return 'callStaticFunction';
    }

    public function callObjectFunction() {
        return 'callObjectFunction';
    }

}

class TestDecorator extends DesignePatternDecorator {}