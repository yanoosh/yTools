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

abstract class DesignePatternDecorator {

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
        return call_user_func_array(get_class(static::$decoratedObject) . '::' . $function, $arguments);
    }

    /**
     * Writing data to property.
     * Function does not work with static variables. https://bugs.php.net/bug.php?id=45002
     *
     * @param type $name Property name.
     * @param mix $value Property data.
     */
    public function __set($name, $value) {
        static::$decoratedObject->$name = $value;
    }

    /**
     * Reading data from property.
     * Function does not work with static variables. https://bugs.php.net/bug.php?id=45002
     *
     * @param type $name Property name.
     * @return mix Property data.
     */
    public function __get($name) {
        return static::$decoratedObject->$name;
    }

    /**
     * Check is property exists.
     *
     * @param string $name Property name.
     * @return boolean
     */
    public function __isset($name) {
        return isset(static::$decoratedObject->$name);
    }

    /**
     * Unsetting property.
     *
     * @param string $name Property name.
     */
    public function __unset($name) {
        unset(static::$decoratedObject->$name);
    }

}