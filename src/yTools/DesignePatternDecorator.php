<?php
namespace yTools;

class DesignePatternDecorator {
    /**
     * Decorated object.
     *
     * @var type 
     */
    protected $decoratedObject = null;
    
    /**
     *
     * @param object $object 
     */
    public function __construct($object) {
        $this->decoratedObject = $object;
    }
    
    /**
     * Run methond from decorated object.
     *
     * @param string $function 
     * @param string $arguments
     * @return mix
     */
    public function __call($function, $arguments) {
        return call_user_func_array(array($this, $function), $arguments);
    }
    
    public static function __callStatic($function, $arguments) {
        return call_user_func_array(get_class($this->decoratedObject) . '::' . $fuunction, $arguments);
    }
    
    public function __set($name, $value) {
        $this->decoratedObject->$name = $value;
    }
    
    public function __get($name) {
        return $this->decoratedObject->$name;
    }
}
