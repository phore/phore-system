<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 10.04.19
 * Time: 11:50
 */

namespace Phore\Tests;


use Phore\System\PhoreExecException;
use Phore\System\PhoreProc;
use PHPUnit\Framework\TestCase;

class PhoreProcTest extends TestCase
{



    public function testExecFileNotFound()
    {
        $this->expectException(PhoreExecException::class);
        phore_proc("test/fileNotfound")
            ->wait();

    }

    public function testChannelsDefaultOpen()
    {
        $result = phore_proc("tests/test.sh")->wait();
        $this->assertEquals("OK\n", $result->getSTDOUTContents());
        $this->assertEquals("ERR\n", $result->getSTDERRContents());
    }

    public function testMultiCannelCallbacks()
    {
        $stdout = "";
        $stderr = "";
        phore_proc("tests/test.sh")
            ->watch(1,
                function ($data, $dataLen, PhoreProc $proc) use (&$stdout) {
                    if ($data === null) {
                        echo "STDOUT CHAN CLOSE";
                        return;
                    }
                    $stdout .= $data;
                    echo "STDOUT: $data";
                })
            ->watch(2,
                function ($data, $dataLen, PhoreProc $proc) use (&$stderr) {

                    if ($data === null) {
                        echo "#ERROR CHAN CLOSE";
                        return;
                    }
                    $stderr .= $data;
                    echo "STDERR: $data";
                })
            ->wait();

        $this->assertEquals("OK\n", $stdout);
        $this->assertEquals("ERR\n", $stderr);
    }


    public function testProperEscaping()
    {
        $proc = phore_proc("someCmd ?", [";'some\'"]);
        $this->assertEquals("someCmd ';'\''some\'\'''", $proc->getCmd());
    }

    public function testEscaping()
    {
        $ret = phore_proc("tests/testEscaping.sh :arg1 :arg2", ["arg1" => "';", "arg2" => "\n\";"])->wait();
        $this->assertEquals("A1:';\nA2: \";\n", $ret->getSTDOUTContents());
    }


}