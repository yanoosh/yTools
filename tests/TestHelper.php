<?php

class TestHelper {

    public static function remove($path) {
        $path = realpath($path);
        if (!is_dir($path) || is_link($path)) {
            unlink($path);
        } else {
            foreach (scandir($path) as $item) {
                if ('.' != $item && '..' != $item) {
                    static::remove($path . DIRECTORY_SEPARATOR . $item);
                }
            }
            rmdir($path);
        }
    }

}