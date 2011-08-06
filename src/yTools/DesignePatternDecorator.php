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

class DesignePatternDecorator {

    /**
     * Decorated object.
     *
     * @var type 
     */
    static protected $decoratedObject = null;

    /**
     *
     * @param object $object 
     */
    public function __construct($object) {
        static::$decoratedObject = $object;
    }

    /**
     * Run methond from decorated object.
     *
     * @param string $function 
     * @param string $arguments
     * @return mix
     */
    public function __call($function, $arguments) {
        return call_user_func_array(array(static::$decoratedObject, $function), $arguments);
    }

    public static function __callStatic($function, $arguments) {
        return call_user_func_array(get_class(static::$decoratedObject) . '::' . $fuunction, $arguments);
    }

    public function __set($name, $value) {
        $this->decoratedObject->$name = $value;
    }

    public function __get($name) {
        return $this->decoratedObject->$name;
    }

}
