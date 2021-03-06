<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 18.07.18
 * Time: 16:07
 */


/**
 * Execute a command
 *
 * <example>
 * $result = phore_exec("some_command ?", ["unescaped parameter"]);
 * </example>
 *
 * By default, it will return both, stderr and stdout.
 *
 * If third parameter is true, it will return an array with each line
 * 
 *
 * @param $cmd
 * @param array $args
 * @param bool $returnArray
 * @return string|array
 * @throws \Phore\System\PhoreExecException
 */
function phore_exec($cmd, array $args=[], $returnArray=false)
{
    $cmd = phore_escape($cmd, $args, function(string $input) { return escapeshellarg($input); });
    exec($cmd . " 2>&1", $output, $return);
    if ($return !== 0)
        throw new \Phore\System\PhoreExecException("Command '$cmd' returned with code $return. " . implode("\n", $output), $return);
    if ($returnArray)
        return $output;
    return implode("\n", $output);
}

/**
 * @param $cmd
 * @param array $args
 * @param bool $returnArray
 * @return string|array
 * @throws \Phore\System\PhoreExecException
 */
function phore_passthru($cmd, array $args=[])
{
    $cmd = phore_escape($cmd, $args, function(string $input) { return escapeshellarg($input); });
    passthru($cmd . " 2>&1", $return);
    if ($return !== 0)
        throw new \Phore\System\PhoreExecException("Command '$cmd' returned with code $return", $return);
    return true;
}

/**
 * @param $cmd
 * @param array $args
 * @param string|null $cwd
 * @param array $env
 * @return \Phore\System\PhoreProc
 */
function phore_proc($cmd, array $args=[], string $cwd=null, array $env=[]) : \Phore\System\PhoreProc
{
    $proc = new \Phore\System\PhoreProc($cmd, $args, $cwd, $env);
    return $proc;
}

