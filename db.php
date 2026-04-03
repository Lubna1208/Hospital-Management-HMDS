<?php

$serverName = "TARIN\\SQLEXPRESS";

$connectionOptions = [
    "Database" => "HMDS",
    //"Uid" => "hmds_user",
    //"PWD" => "DataC2",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("<pre style='color:#ffd0d0;background:#200;padding:12px;border-radius:12px;'>" .
        print_r(sqlsrv_errors(), true) .
        "</pre>");
}

if (!function_exists('ensure_patient_record')) {
    function ensure_patient_record($conn, $user_id)
    {
        $user_id = (int)$user_id;

        $patientStmt = sqlsrv_query($conn, "SELECT TOP 1 id FROM dbo.patients WHERE id = ?", [$user_id]);
        if ($patientStmt === false) {
            return false;
        }

        if (sqlsrv_fetch_array($patientStmt, SQLSRV_FETCH_ASSOC)) {
            return true;
        }

        $userStmt = sqlsrv_query($conn, "SELECT TOP 1 name FROM dbo.users WHERE id = ?", [$user_id]);
        if ($userStmt === false) {
            return false;
        }

        $user = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
        if (!$user) {
            return false;
        }

        $insertStmt = sqlsrv_query(
            $conn,
            "INSERT INTO dbo.patients (id, PatientName) VALUES (?, ?)",
            [$user_id, $user['name']]
        );

        return $insertStmt !== false;
    }
}

if (!function_exists('get_departments')) {
    function get_departments($conn)
    {
        $departments = [];
        $stmt = sqlsrv_query($conn, "SELECT department_id, department_name FROM dbo.departments ORDER BY department_name");

        if ($stmt === false) {
            return $departments;
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $departments[] = $row;
        }

        return $departments;
    }
}

if (!function_exists('department_exists')) {
    function department_exists($departments, $department_id)
    {
        $department_id = (int)$department_id;

        foreach ($departments as $department) {
            if ((int)($department['department_id'] ?? 0) === $department_id) {
                return true;
            }
        }

        return false;
    }
}
