<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 10.04.19
 * Time: 14:19
 */

namespace Phore\Tests;


use Phore\System\PhoreExecException;
use PHPUnit\Framework\TestCase;

class PhoreExecTest extends TestCase
{

    public function testExec()
    {
        $ret = phore_exec("tests/test.sh", []);
        $this->assertEquals("ERR\nOK", $ret);
    }


    public function testExecFail()
    {
        $this->expectException(PhoreExecException::class);
        phore_exec("test/notfound");
    }


    public function testEscaping()
    {
        $ret = phore_exec("tests/testEscaping.sh :arg1 :arg2", ["arg1" => "';", "arg2" => "\n\";"]);
        $this->assertEquals("A1:';\nA2: \";", $ret);
    }

}