<?php

$serverName = "DESKTOP-7OHN20R\\SQLEXPRESS";

$connectionOptions = [
    "Database" => "HMDS",
    "Uid" => "hmds_user",
    "PWD" => "DataC2",
    "CharacterSet" => "UTF-8",
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("<div class='error'><b>DB Connection Failed:</b><br>" . print_r(sqlsrv_errors(), true) . "</div>");
}
