<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 08.10.18
 * Time: 17:33
 */

namespace Phore\System;


class PhoreProc
{

    private $cmd;
    private $cwd;
    private $env;

    private $listener = [
        1 => null, // Default: Open STDOUT
        2 => null  // Default: Open STDERR
    ];

    private $pipes = null;
    private $proc = null;

    public function __construct($cmd, array $params=[],string $cwd=null, array $env=[])
    {
        $this->cmd = phore_escape($cmd, $params, function(string $input) { return escapeshellarg($input);});
        $this->cwd = $cwd;
        $this->env = $env;
    }


    /**
     * Register a callback on channel if output occures. After feof() this callback
     * will be called with null as data
     *
     * <example>
     * phore_exec("ls -l")->watch(1, function($data, $len, PhoreProc $proc) {})->wait();
     * </example>
     *
     * @param int $channel
     * @param callable|null $callback
     * @return PhoreProc
     */
    public function watch(int $channel, callable $callback = null) : self
    {
        $this->listener[$channel] = $callback;
        return $this;
    }

    /**
     * Return the actual executed cmd (escaped)
     *
     * @return string
     */
    public function getCmd() : string
    {
        return $this->cmd;
    }


    /**
     * Execute the process.
     *
     * This method will not wait unitl the process exists. So you have to call wait() afterwards!
     *
     * @return PhoreProc
     * @throws \Exception
     */
    public function exec() : self
    {
        $descSpec = [
            0 => ["pipe", "r"]
        ];
        foreach ($this->listener as $chanId => $listener) {
            $descSpec[$chanId] = ["pipe", "w"];
        }

        $this->proc = proc_open($this->cmd, $descSpec, $pipes);

        if ($this->proc === false)
            throw new \Exception("Unable to proc_open()");

        //print_r (proc_get_status($this->proc));

        foreach ($this->listener as $chanId => $listener) {
            stream_set_blocking($pipes[$chanId], 0);
        }
        $this->pipes = $pipes;
        return $this;
    }


    /**
     * Wait for the process to exit.
     *
     * This will call exec() if not done before.
     *
     * @param bool $throwExceptionOnError
     * @return PhoreProcResult
     * @throws PhoreExecException
     */
    public function wait(bool $throwExceptionOnError=true) : PhoreProcResult
    {

        if ($this->proc === null)
            $this->exec();

        $buf = null;
        if ($buf === null) {
            $buf = [];
            foreach ($this->listener as $chanId => $listener) {
                $buf[$chanId] = "";
            }
        }

        while(true) {
            $allPipesClosed = true;
            $noData = true;
            foreach ($buf as $chanId => &$buffer) {
                if ( ! feof($this->pipes[$chanId])) {
                    $allPipesClosed = false;
                    $dataRead = fread($this->pipes[$chanId], 1024 * 32);
                    $dataReadLen = strlen($dataRead);
                    if ($dataReadLen > 0) {
                        $noData = false;
                        if ($this->listener[$chanId] !== null) {
                            ($this->listener[$chanId])($dataRead, $dataReadLen, $this);
                        } else {
                            $buffer .= $dataRead;
                        }
                    }
                }
            }
            if ($allPipesClosed) {
                break;
            }
            if ($noData) {
                usleep(500);
            }
        }

        foreach ($this->listener as $chanId => $listener) {
            if ($listener === null)
                continue;
            $listener(null, null, $this);
            fclose($this->pipes[$chanId]);
        }
        fclose($this->pipes[0]);


        $exitStatus = proc_close($this->proc);
        $errmsg = "";
        if (isset ($buf[2]))
            $errmsg = $buf[2];
        if ($exitStatus !== 0) {
            throw new PhoreExecException("Command '$this->cmd' returned with exit-code $exitStatus: $errmsg", $exitStatus);
        }
        return new PhoreProcResult($exitStatus, $buf);
    }

}