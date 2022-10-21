<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 08.10.18
 * Time: 17:33
 */

namespace Phore\System;


use Phore\Core\Exception\TimeoutException;

class PhoreProc
{

    private $cmd;
    private $cwd;
    private $env;
    private $timeout = null;

    private $listener = [
        1 => null, // Default: Open STDOUT
        2 => null  // Default: Open STDERR
    ];

    private $pipes = null;
    private $proc = null;

    public function __construct($cmd, array $params=[],string $cwd=null, array $env=null)
    {
        $this->cmd = phore_escape($cmd, $params, function(string $input) { return escapeshellarg($input);});
        $this->cwd = $cwd;
        $this->env = $env;
    }

    public function setTimeout(int $timeout) : self
    {
        $this->timeout = $timeout;
        return $this;
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
     * Write data (default: to stdin)
     *
     * <example>
     * // Exec password programm and wirte "some data" to stdin.
     * phore_proc("passwd")->exec()->fwrite("some data")->close()->wait();
     * </example>
     *
     *
     * @param string $data
     * @param int $channel
     */
    public function write(string $data, int $channel = 0) : self
    {
        if ( ! fwrite($this->pipes[$channel], $data))
            throw new \InvalidArgumentException("Cannot write to channel $channel");
        return $this;
    }

    /**
     * Close a channel (default: stdin) after writing to it
     *
     * @param int $channel
     */
    public function close(int $channel = 0) : self
    {
        fclose($this->pipes[$channel]);
        $this->pipes[$channel] = null;
        return $this;
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

        $this->proc = proc_open($this->cmd, $descSpec, $pipes, $this->cwd, $this->env);

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
     * @throws TimeoutException
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

        $startTime = time();
        $timeoutReached = false;
        while(true) {
            if ($this->timeout !== null && (time() - $this->timeout) > $startTime) {
                $timeoutReached = true;
                proc_terminate($this->proc, SIGKILL);
                break;
            }

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
        if ($this->pipes[0] !== null)
            fclose($this->pipes[0]);

        $exitStatus = proc_close($this->proc);
        $errmsg = "";
        if (isset ($buf[2]))
            $errmsg = $buf[2];
        if ($timeoutReached)
            throw new TimeoutException("Command '$this->cmd' timeout after $this->timeout seconds", $exitStatus);
        if ($exitStatus !== 0 && $throwExceptionOnError) {
            throw new PhoreExecException("Command '$this->cmd' returned with exit-code $exitStatus: $errmsg", $exitStatus);
        }
        return new PhoreProcResult($exitStatus, $buf);
    }

}
