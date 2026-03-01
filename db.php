<?php

$serverName = "DESKTOP-7OHN20R\\SQLEXPRESS";

$connectionOptions = [
    "Database" => "HMDS",
    "Uid" => "hmds_user",
    "PWD" => "DataC2",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("<pre style='color:#ffd0d0;background:#200;padding:12px;border-radius:12px;'>" .
        print_r(sqlsrv_errors(), true) .
        "</pre>");
}
