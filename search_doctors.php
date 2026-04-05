<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "patient") {
    header("Location: login.php");
    exit;
}

$search_name = trim($_GET["search_name"] ?? "");
$filter_department_id = (int)($_GET["department_id"] ?? 0);
$departments = get_departments($conn);

$sql = "SELECT d.id, d.full_name, dep.department_name AS department, d.phone, d.room_no, d.consultation_fee, u.email
        FROM dbo.doctors d
        LEFT JOIN dbo.departments dep ON dep.department_id = d.department_id
        INNER JOIN dbo.users u ON u.id = d.id
        WHERE 1=1 ";
$params = [];

if ($search_name) {
    $sql .= " AND d.full_name LIKE ? ";
    $params[] = "%" . $search_name . "%";
}

if ($filter_department_id > 0) {
    $sql .= " AND d.department_id = ? ";
    $params[] = $filter_department_id;
}

$sql .= " ORDER BY d.full_name";

$docStmt = sqlsrv_query($conn, $sql, $params);
$doctors = [];
while ($row = sqlsrv_fetch_array($docStmt, SQLSRV_FETCH_ASSOC)) {
    $doctors[] = $row;
}

$doctorSchedules = [];
$dayNames = [1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday", 7 => "Sunday"];

if (!empty($doctors)) {
    $doctorIds = array_map(function ($doctor) {
        return (int)$doctor["id"];
    }, $doctors);

    $placeholders = implode(",", array_fill(0, count($doctorIds), "?"));
    $scheduleSql = "
        WITH latest_schedule AS (
            SELECT
                doctor_id,
                day_of_week,
                start_time,
                end_time,
                max_patients,
                ROW_NUMBER() OVER (
                    PARTITION BY doctor_id, day_of_week
                    ORDER BY id DESC
                ) AS rn
            FROM dbo.doctor_schedule
            WHERE doctor_id IN ($placeholders)
        )
        SELECT doctor_id, day_of_week, start_time, end_time, max_patients
        FROM latest_schedule
        WHERE rn = 1
        ORDER BY doctor_id, day_of_week, start_time
    ";
    $scheduleStmt = sqlsrv_query($conn, $scheduleSql, $doctorIds);

    if ($scheduleStmt) {
        while ($schedule = sqlsrv_fetch_array($scheduleStmt, SQLSRV_FETCH_ASSOC)) {
            $doctorId = (int)$schedule["doctor_id"];
            $doctorSchedules[$doctorId][] = $schedule;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Doctors - PISD</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

<nav class="nav">
    <div class="logo">Hospital</div>
    <div class="actions">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Patient"); ?></span>
        <a class="btn" href="patient_home.php">Home</a>
        <a class="btn" href="logout.php">Logout</a>
    </div>
</nav>

<div class="layout-grid">

    <div class="sidebar-card">
        <h3 style="margin-bottom:16px;">Filter by Department</h3>
        <form method="get">
            <select class="form-field" name="department_id" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo (int)$department["department_id"]; ?>" <?php echo $filter_department_id === (int)$department["department_id"] ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($department["department_name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($search_name): ?>
                <input type="hidden" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
            <?php endif; ?>
        </form>
    </div>

    <div style="flex:1; min-width:0;">
        <h2 class="section-title">Search Doctors</h2>

        <form method="get" class="inline-actions" style="margin-bottom:24px;">
            <input class="form-field" type="text" name="search_name" placeholder="Search by doctor name" value="<?php echo htmlspecialchars($search_name); ?>" style="max-width:360px;">
            <?php if ($filter_department_id > 0): ?>
                <input type="hidden" name="department_id" value="<?php echo (int)$filter_department_id; ?>">
            <?php endif; ?>
            <button class="btn" type="submit">Search</button>
        </form>

        <div class="vaccine-grid">
            <?php if (count($doctors) === 0): ?>
                <div class="feature-card">
                    <div class="feature-card-body">
                        <p>No doctors found.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($doctors as $doc): ?>
                <div class="vaccine-card">
                    <h3><?php echo htmlspecialchars($doc["full_name"]); ?></h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($doc["email"] ?? ""); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Department</span>
                            <span class="detail-value"><?php echo htmlspecialchars($doc["department"] ?? "N/A"); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact</span>
                            <span class="detail-value"><?php echo htmlspecialchars($doc["phone"] ?? "N/A"); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Room No</span>
                            <span class="detail-value"><?php echo htmlspecialchars($doc["room_no"] ?? "N/A"); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Consultation Fee</span>
                            <span class="detail-value"><?php echo "Tk " . number_format((float)($doc["consultation_fee"] ?? 0), 2); ?></span>
                        </div>
                    </div>

                    <div class="notes-copy">
                        <span class="detail-label">Weekly Schedule</span>
                        <?php if (!empty($doctorSchedules[(int)$doc["id"]])): ?>
                            <?php foreach ($doctorSchedules[(int)$doc["id"]] as $schedule): ?>
                                <?php
                                $dayName = $dayNames[(int)$schedule["day_of_week"]] ?? "Unknown";
                                $start = $schedule["start_time"] instanceof DateTime ? $schedule["start_time"]->format("H:i") : "--:--";
                                $end = $schedule["end_time"] instanceof DateTime ? $schedule["end_time"]->format("H:i") : "--:--";
                                ?>
                                <p><?php echo htmlspecialchars($dayName . ": " . $start . " - " . $end . " (Max " . (int)($schedule["max_patients"] ?? 0) . ")"); ?></p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No schedule available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
</body>
</html>
