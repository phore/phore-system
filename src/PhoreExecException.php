<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 10.04.19
 * Time: 13:46
 */

namespace Phore\System;


class PhoreExecException extends \Exception
{
    public function setMessage($message){
        $this->message = $message;
    }
}