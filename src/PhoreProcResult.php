<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 10.04.19
 * Time: 12:55
 */

namespace Phore\System;


class PhoreProcResult
{

    private $exitStatus;
    private $pipes;

    public function __construct(int $exitStatus, array $pipes)
    {
        $this->exitStatus = $exitStatus;
        $this->pipes = $pipes;
    }


    public function failed() : bool
    {
        return ($this->exitStatus !== 0);
    }


    public function getExitStatus() : int
    {
        return $this->exitStatus;
    }

    /**
     * Get the contents of a channel
     *
     * Only availabe if no
     *
     * @param int $channel  1: STDOUT / 2: STDERR
     */
    public function getChanContents(int $channel=1) : string
    {
        if ( ! isset ($this->pipes[$channel]))
            throw new \InvalidArgumentException("No channel '$channel' defined.");
        return $this->pipes[$channel];
    }

    public function getSTDOUTContents() : string
    {
        return $this->getChanContents(1);
    }


    public function getSTDERRContents() : string
    {
        return $this->getChanContents(2);
    }

}