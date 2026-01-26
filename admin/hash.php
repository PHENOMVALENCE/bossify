<?php

$pswd = 'admin123'; // Set your desired password here
$hash = password_hash($pswd, PASSWORD_DEFAULT);
echo "Password: " . $pswd . "<br>";
echo "Hash: " . $hash . "<br>";



?>