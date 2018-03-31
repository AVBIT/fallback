#!/usr/bin/php
<?php
/**
 * FALLBACK
 * Command line tools for checking the availability and response time
 * of any service running on TCP, with sending a mail notification
 * when changing status.
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.03.2018. Last modified on 31.03.2018
 * ----------------------------------------------------------------------------
 * USAGE:
 * php fallback.php --help
 */


if (!function_exists('posix_getsid') ||
    !function_exists('pcntl_signal') ||
    !function_exists('fsockopen')) {
    echo "Error, you need to enable extensions: posix, pcntl, sockets in your binary php file." . PHP_EOL;
    exit(1);
}


// Process lock
$unique_hash = hash('md4', implode (',', $argv)) ;
$lockfile = sys_get_temp_dir() . basename($argv[0], ".php") . "_$unique_hash.lock";
$pid = @file_get_contents($lockfile);
if ($pid === false || posix_getsid($pid) === false) {
    printf("%s FALLBACK: Process has died! restarting...".PHP_EOL, date(DATE_RFC822));
    file_put_contents($lockfile, getmypid()); // create lockfile
} else {
    printf("%s FALLBACK: PID is still alive! Can not run twice!".PHP_EOL, date(DATE_RFC822));
    exit;
}


// Remove the lock on exit (Control+C doesn't count as 'exit'!)
register_shutdown_function('unlink', $lockfile);


/**
 * Define the signal handling
 * @param $signo
 */
function sig_handler($signo){
    if ($signo == SIGTERM || $signo == SIGHUP || $signo == SIGINT){
        printf("%s FALLBACK: got signal %d and will exit.".PHP_EOL, date(DATE_RFC822), $signo);
        exit(); // If this were something important we might do data cleanup here
    }
}
pcntl_signal(SIGTERM,"sig_handler");
pcntl_signal(SIGHUP,"sig_handler");
pcntl_signal(SIGINT,"sig_handler");



// Default command line options value
$host = '';
$port = 80;
$timeout = 20;
$sleep = 1;
$count = 0;
$recipients = [];
$errors = [];

// Command line options
$params = array(
    ''    => 'help',
    'h:'  => 'host:',
    'p::' => 'port::',
    't::' => 'timeout::',
    's::' => 'sleep::',
    'c::' => 'count::',
    'm::' => 'mail::',
);
$options = getopt( implode('', array_keys($params)), $params );


if ( isset($options['host']) || isset($options['h']) ){
    $host = isset( $options['host'] ) ? $options['host'] : $options['h'];
    $host = escapeshellcmd($host);
} else {
    $errors[]   = 'host required';
}
if ( isset($options['port']) || isset($options['p']) ){
    $port = isset( $options['port'] ) ? $options['port'] : $options['p'];
    $port = intval($port,10);
    $port = ($port == 0) ? 80 : $port;
}
if ( isset($options['timeout']) || isset($options['t']) ){
    $timeout = isset( $options['timeout'] ) ? $options['timeout'] : $options['t'];
    $timeout = intval($timeout,10);
    $timeout = ($timeout == 0) ? 20 : $timeout;
}
if ( isset($options['sleep']) || isset($options['s']) ){
    $sleep = isset( $options['sleep'] ) ? $options['sleep'] : $options['s'];
    $sleep = intval($sleep,10);
    $sleep = ($sleep == 0) ? 1 : $sleep;
}
if ( isset($options['count']) || isset($options['c']) ){
    $count = isset( $options['count'] ) ? $options['count'] : $options['c'];
    $count = intval($count,10);
}
if ( isset($options['mail']) || isset($options['m']) ){
    $mail = isset( $options['mail'] ) ? $options['mail'] : $options['m'];
    $recipients = preg_split("/[\s,;|]+/", $mail);
    foreach ($recipients as $key => $recipient){
        $recipient[$key] = escapeshellcmd($recipient);
    }
}
if ( isset($options['help']) || count($errors) )
{
    $help = "
usage: php fallback.php [--help] [-h|--host] [-p|--port=80] [-t|--timeout=20] [-s|--sleep=1] [-m|--mail]
 
Options:
             --help         Show this message
        -h   --host         Ip-address or domain name (required)
        -p   --port         Port number (optional, default: 80)
        -t   --timeout      Maximum response time (optional, default: 20, seconds)
        -s   --sleep        Sleep time after a successful check (optional, default: 1, seconds)
        -c   --count        Stop after count of connection attempts (optional, default: 0, operate until interrupted). 
        -m   --mail         Notification email or emails separated pattern /[\s,;|]+/ (optional)
Example:
        php fallback.php --host='www.example.com' 
        php fallback.php --host='10.11.45.97' --port=80 --timeout=30 --mail='user1@example.com | user2@example.com | userN@example.com' >> /var/log/fallback.log
";
    if ( $errors )
    {
        $help .= PHP_EOL . 'Errors:' . PHP_EOL . implode(PHP_EOL, $errors) . PHP_EOL;
    }
    die($help);
}


$status_curr = true;
$status_prev = true;

$service_name = "$host:$port";

$done = ($count<=0) ? 1:$count;

while ($done)
{
    if (pingSocket($host,$port,$timeout)>=0)
    {
        $status_curr = true;
        if (count($recipients) && $status_curr !== $status_prev){
            $status_prev = $status_curr;
            $mail_subject = "Fallback $service_name UP";
            $mail_body = date(DATE_RFC822) . " - The service $service_name successful check.";
            $mail_body .= PHP_EOL. PHP_EOL . "[MailFilterLabel:FALLBACK]" . PHP_EOL . "[MailFilterLabel:$unique_hash]";
            foreach ($recipients as $recipient){
                exec(" ( nohup echo '$mail_body' | mail -s '$mail_subject' $recipient ) &");
            }
        }
        sleep($sleep);
    } else {
        $status_curr = false;
        if (count($recipients) && $status_curr !== $status_prev){
            $status_prev = $status_curr;
            $mail_subject = "Fallback $service_name DOWN";
            $mail_body = date(DATE_RFC822) . " - The service $service_name did not respond to the request within the allotted time - $timeout sec.";
            $mail_body .= PHP_EOL. PHP_EOL . "[MailFilterLabel:FALLBACK]" . PHP_EOL . "[MailFilterLabel:$unique_hash]";
            foreach ($recipients as $recipient){
                exec(" ( nohup echo '$mail_body' | mail -s '$mail_subject' $recipient ) &");
            }
        }
    }
    pcntl_signal_dispatch();
    if ($count>0) $done--;
}


exit(0); // Remove the lock!




/**
 * Check response time
 * @param $domain
 * @param int $port
 * @param int $timeout
 * @return float|int
 */
function pingSocket($domain, $port=80, $timeout = 20)
{
    if (empty($domain)) return -$timeout;
    $port = intval($port, 10);
    $timeout = intval($timeout, 10) < 1 ? 1 : intval($timeout, 10);

    $starttime = microtime(true);
    $fd  = @fsockopen ($domain, $port, $errno, $errstr, $timeout);
    $endtime  = microtime(true);

    if ($errno != 0) {
        // host is down
        $result = -$timeout;
        if (($endtime - $starttime) < $timeout) {
            // may be attempt to connect to UDP port ( always $fd === true; errno:61; errstr: Connection refused)
            if (is_resource($fd)) fclose($fd);
            sleep($timeout);
        }
        printf("%s FALLBACK: %s:%d - DOWN (timeout:%d sec.; errno:%d; errstr: %s)".PHP_EOL, date(DATE_RFC822), $domain, $port, $timeout, $errno, $errstr);
    } else {
        fclose($fd);
        $result = round ( ($endtime - $starttime), 6, PHP_ROUND_HALF_UP);
        printf("%s FALLBACK: %s:%d - OK (time: %.6f sec.)".PHP_EOL, date(DATE_RFC822), $domain, $port, $result);
    }
    return $result;
}
