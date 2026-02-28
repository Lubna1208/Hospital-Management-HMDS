<?php

$serverName = "MAHI\\SQLEXPRESS";

$connectionOptions = [
    "Database" => "HMDS",
    "Uid" => "hdms_user",
    "PWD" => "1234",
    "CharacterSet" => "UTF-8",
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("<div class='error'><b>DB Connection Failed:</b><br>" . print_r(sqlsrv_errors(), true) . "</div>");
}
