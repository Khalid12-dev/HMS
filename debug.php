<?php
// debug.php
session_start();
echo "<h2>Debug Information</h2>";
echo "<h3>Session Data</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>SMS Test Log</h3>";
if (file_exists('sms_test.log')) {
    echo "<pre>" . file_get_contents('sms_test.log') . "</pre>";
} else {
    echo "No SMS test log found.";
}

echo "<h3>Error Log</h3>";
echo "<pre>" . file_get_contents('error_log') . "</pre>";