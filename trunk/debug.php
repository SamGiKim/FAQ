<?php

// 에러 로깅 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ini_set('error_log', '/tmp/php_errors.log');  // This will create a log file in /tmp
ini_set('error_log', '/var/log/apache2/error.log');  // This will create a log file in /tmp


// Check the log file at /tmp/php_errors.log
// If you don't have the file create it first:
// touch /tmp/php_errors.log
// To check live:
//tail -f /tmp/php_errors.log
?>
