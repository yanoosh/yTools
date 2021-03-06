<?php

namespace yTools;

/**
 * Test class for ProcessGuard.
 * Generated by PHPUnit on 2011-08-10 at 01:38:25.
 */
class ProcessGuardTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var ProcessGuard
     */
    protected $object;
    protected $tmpDirectory = 'ProcessGuardTest';
    public $multiProcessesLockPrefix = 'MultiProcess';
    public $multiProcessesLockDir = 'MultiProcess';

    public function __construct($name = NULL, array $data = array(), $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->tmpDirectory =
            \TestConfiguration::getInstance()->getTemporaryDirectory() .
            '/' . $this->tmpDirectory;
        $this->multiProcessesLockDir = $this->tmpDirectory . '/' . $this->multiProcessesLockDir;
    }

    public function setUp() {
        if (file_exists($this->tmpDirectory)) {
            \TestHelper::remove($this->tmpDirectory);
        }
        mkdir($this->tmpDirectory, 0770, true);
    }
    
    protected function getDefaultProcessGuard($lockDir = null, $lockPrefix = null, $processNumber = null) {
        $object = new ProcessGuard($lockDir ? : $this->tmpDirectory);
        $object->setLockPrefix($lockPrefix ? : 'ProcessGuardTest');
        $object->setProcMaxNumber($processNumber ? : 1);
        return $object;
    }

    public function testConstruct() {
        $this->assertInstanceOf(__NAMESPACE__ . '\\ProcessGuard', new ProcessGuard($this->tmpDirectory));
        try {
            new ProcessGuard($this->tmpDirectory . '/xyz');
            $this->fail('The expected exception has not been raised.');
        } catch (\InvalidArgumentException $expected) {}
        $testConstructorDir = $this->tmpDirectory . '/TestConstruct';
        mkdir($testConstructorDir, 0550);
        try {
            new ProcessGuard($testConstructorDir);
            $this->fail('The expected exception has not been raised.');
        } catch (\InvalidArgumentException $expected) {}
    }

    public function testLockPrefix() {
        $object = $this->getDefaultProcessGuard();
        $object->setLockPrefix('TestLockPrefixŚĆĄ');
        $this->assertEquals('TestLockPrefix_', $object->getLockPrefix());
        $object->setLockPrefix('TestLockPrefix123%%%456_');
        $this->assertEquals('TestLockPrefix123_456_', $object->getLockPrefix());
        $object->setLockPrefix('TestLockPrefix');
        $this->assertEquals('TestLockPrefix', $object->getLockPrefix());
        $lockFile = $this->tmpDirectory . '/TestLockPrefix-001-001.flock';
        $isExists = $object->run(
            function($lockFile) {
                return is_file($lockFile);
            }, array($lockFile)
        );
        $this->assertEquals(true, $isExists);
    }
    
    public function testLockDirectory() {
        $object = $this->getDefaultProcessGuard($this->tmpDirectory);
        $this->assertEquals(realpath($this->tmpDirectory), $object->getLockDirectory());
        unset($object);
        $lockDir = $this->tmpDirectory . '/TestGetLockDirectory';
        $lockFile = $lockDir . '/ProcessGuardTest-001-001.flock';
        mkdir($lockDir, 0770);
        $object = $this->getDefaultProcessGuard($lockDir);
        $isExists = $object->run(
            function($file) {
                return is_file($file);
            }, array($lockFile)
        );
        $this->assertEquals(true, $isExists);
        $this->assertEquals(realpath($lockDir), $object->getLockDirectory());
        unset($object);
    }

    public function testMaxLocks() {
        $object1 = $this->getDefaultProcessGuard();
        $object2 = $this->getDefaultProcessGuard();
        try {
            $object1->run(
                function($object2) {
                    $object2->run(function () {
                            return 1;
                        });
                }, array($object2)
            );
            $this->fail('The expected exception has not been raised.');
        } catch (\OverflowException $e) {}
        unset($object1, $object2);
        $object = $this->getDefaultProcessGuard(null, null, 2);
        $this->assertEquals(2, $object->getProcMaxNumber());
        $object->setProcMaxNumber(-1);
        $this->assertEquals(1, $object->getProcMaxNumber());
        unset($object);
        $object = $this->getDefaultProcessGuard(null, null, -1);
        $this->assertEquals(1, $object->getProcMaxNumber());
        $object->setProcMaxNumber(10);
        $this->assertEquals(10, $object->getProcMaxNumber());
        unset($object);
    }

    public function testClone() {
        $function = function($object) {
                return $object->run(function () {
                            return true;
                        });
            };
        $object = $this->getDefaultProcessGuard(null, null, 2);
        try {
            $object->run($function, array($object));
            $this->fail('The expected exception has not been raised.');
        } catch (\RuntimeException $e) {}
        $result = $object->run($function, array(clone $object));
        $this->assertEquals(true, $result, 'Cloning works.');
        unset($object);
    }

    public function providerInformationMethods() {
        return array(
            array(1),
            array(3),
            array(6),
            array(9),
        );
    }

    /**
     * @dataProvider providerInformationMethods
     */
    public function testMultiProcesses($number) {
        mkdir($this->multiProcessesLockDir, 0770, true);
        $processes = unserialize($this->externalMultiProcesses($number, $number));
        $runningProcesses = $number;
        foreach ($processes as $process) {
            $countLockFile = 0;
            $dirListing = $process['test']['dir_listing'];
            foreach ($process['object']['running_processes_info'] as $info) {
                $fileKey = $number . '_' . $info['lock_id'];
                $this->assertEquals(true, isset($dirListing[$fileKey]), 'Lock file was not found.');
                $test = $dirListing[$fileKey];
                unset($dirListing[$fileKey]);
                $this->assertEquals(true, $test['is_file_lock']);
                $this->assertEquals(true, $test['is_file']);
                $this->assertEquals($info['lock_id'], $test['lock_id']);
                $this->assertEquals($info['file_path'], $test['file_path']);
                $this->assertEquals($info['process_id'], (int) $test['content']);
                $countLockFile++;
            }
            foreach ($dirListing as $file) {
                $this->assertEquals(false, $file['is_file_lock'], "Any other files should not be locked");
            }
            unset($dirListing);
            $fileKey = $number . '_' . $process['object']['lock_id'];
            $this->assertEquals(true, isset($process['test']['dir_listing'][$fileKey]), 'Lock file of the running process was not found.');
            $test = $process['test']['dir_listing'][$fileKey];
            $this->assertEquals(true, $test['is_file_lock']);
            $this->assertEquals(true, $test['is_file']);
            $this->assertEquals($process['object']['lock_id'], $test['lock_id']);
            $path = sprintf(
                '%s/%s-%03s-%03s.flock', $process['object']['locks_directory'], $process['object']['lock_prefix'], $number, $process['object']['lock_id']
            );
            $this->assertEquals($path, $test['file_path']);
            $this->assertEquals($process['object']['process_id'], (int) $test['content']);
            $this->assertEquals($process['object']['number_of_locks'], $runningProcesses);
            $this->assertEquals($runningProcesses, $countLockFile);
            $runningProcesses--;
        }
    }

    protected function externalMultiProcesses($i, $processNumber) {
        $object = $this->getDefaultProcessGuard(
            $this->multiProcessesLockDir, $this->multiProcessesLockPrefix, $processNumber
        );
        $instance = $this;
        return $object->run(function () use($instance, $i, $processNumber, $object) {
                    if (0 < --$i) {
                        $result = $instance->runExternalProcess('externalMultiProcesses', array($i, $processNumber), $procStatus);
                        $return = unserialize($result);
                        $tmp = end($return);
                        $tmp['test']['process_id'] = $procStatus['pid'];
                    } else {
                        $return = array();
                    }
                    $dirListing = array();
                    foreach (scandir($instance->multiProcessesLockDir) as $file) {
                        if ('..' != $file && '.' != $file) {
                            $filePath = $instance->multiProcessesLockDir . '/' . $file;
                            $res = fopen($filePath, 'r');
                            $tmp = array(
                                'file_path' => $filePath,
                                'content' => fread($res, 1024),
                                'is_file' => is_file($filePath),
                                'is_file_lock' => !flock($res, LOCK_EX | LOCK_NB),
                            );
                            if (preg_match('#([0-9]{3})\\-([0-9]{3})\\.flock$#', $filePath, $matches)) {
                                $tmp['proc_max_number'] = (int) $matches['1'];
                                $tmp['lock_id'] = (int) $matches['2'];
                                $dirListing[$tmp['proc_max_number'] . '_' . $tmp['lock_id']] = $tmp;
                            } else {
                                $tmp['lock_id'] = null;
                                $tmp['proc_max_number'] = null;
                                $dirListing[] = $tmp;
                            }
                            fclose($res);
                        }
                    }
                    $return[] = array(
                        'object' => array(
                            'locks_directory' => $object->getLockDirectory(),
                            'lock_id' => $object->getLockId(),
                            'lock_prefix' => $object->getLockPrefix(),
                            'proc_max_number' => $object->getProcMaxNumber(),
                            'number_of_locks' => $object->getProcNumber(),
                            'process_id' => $object->getProcId(),
                            'running_processes_info' => $object->getProcInfo(),
                        ),
                        'test' => array(
                            'dir_listing' => $dirListing,
                            'process_id' => null,
                        )
                    );
                    return serialize($return);
                });
    }

    public function testRun() {
        $object = $this->getDefaultProcessGuard();
        $function = function ($text = 'lambdaFunction') {
                return $text;
            };
        $this->assertEquals('lambdaFunction', $object->run($function));
        $this->assertEquals('lambdaFunction1', $object->run($function, array('lambdaFunction1')));
        $function = __NAMESPACE__ . '\ProcessGuardTest::publicStaticRun';
        $this->assertEquals('publicStaticRun', $object->run($function));
        $this->assertEquals('publicStaticRun1', $object->run($function, array('publicStaticRun1')));
        $function = array($this, 'publicRun');
        $this->assertEquals('publicRun', $object->run($function));
        $this->assertEquals('publicRun1', $object->run($function, array('publicRun1')));
        try {
            $object->run('NotExistsFunction', array(123));
            $this->fail('The expected exception has not been raised.');
        } catch (\BadFunctionCallException $e) {}
    }

    public function publicRun($text = 'publicRun') {
        return $text;
    }

    public static function publicStaticRun($text = 'publicStaticRun') {
        return $text;
    }

    public static function runNewProcess($method, $arguments = array()) {
        $instance = new static();
        return call_user_func_array(array($instance, $method), (array) $arguments);
    }

    public function runExternalProcess($method, $arguments = array(), &$procStatus = null) {
        $arguments = array_map('escapeshellcmd', (array) $arguments);
        $command = 'env php ' . __DIR__ . '/ExternalProcess.php ' . $method . ' "' . implode('" "', $arguments) . '"';
        $descriptors = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );
        $process = proc_open($command, $descriptors, $pipes, $this->tmpDirectory);
        $procStatus = proc_get_status($process);
        fclose($pipes[0]);
        $response = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ('' != $error || 0 != $exit) {
            throw new \Exception('In external process has occurred error.' . $command);
        }
        return $response;
    }
}