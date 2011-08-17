<?php
class TestConfiguration {
    private $directory;
    private $temporaryDirecotry;
    
    private function __construct() {
        $this->directory = __DIR__;
        $this->prepareTmpDir();
    }
    
    private function prepareTmpDir() {
        $tmpDirecotry = sys_get_temp_dir() . '/PHPUnit';
        if (
            (
                is_dir($tmpDirecotry)
                || mkdir($tmpDirecotry, 0770)
            ) &&
            is_writable($tmpDirecotry)
        ) {
            $this->temporaryDirecotry = $tmpDirecotry;
        } else {
            throw new RuntimeException('Problem with the directory for temporary files [' . $tmpDirecotry . ']. Check access rights to the path.');
        }
    }
    
    public function getDirecotry() {
        return $this->directory;
    }
    
    public function getTemporaryDirectory() {
        return $this->temporaryDirecotry;
    }

    /**
     *
     * @staticvar string $instance
     * @return TestConfiguration
     */
    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}