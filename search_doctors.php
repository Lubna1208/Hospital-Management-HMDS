<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "patient") {
    header("Location: login.php");
    exit;
}

$search_name = trim($_GET['search_name'] ?? '');
$filter_department_id = (int)($_GET['department_id'] ?? 0);
$departments = get_departments($conn);

// Fetch doctors based on search or filter
$sql = "SELECT d.full_name, dep.department_name AS department, d.phone 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Doctors — PISD</title>
<link rel="stylesheet" href="assets/patient.css">
</head>
<body>
<div class="container">

<nav class="nav">
    <div class="logo">🏥</div>
    <div class="actions">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Patient"); ?></span>
        <a class="btn" href="patient_home.php">Home</a>
        <a class="btn" href="logout.php">Logout</a>
    </div>
</nav>

<div style="display:flex; gap:32px; margin-top:32px;">

    <!-- Sidebar: Department Dropdown -->
    <div style="width:250px; background:white; padding:24px; border-radius:16px; box-shadow:0 8px 24px rgba(10,44,62,.08);">
        <h3 style="margin-bottom:16px;">Filter by Department</h3>
        <form method="get">
            <select class="form-field" name="department_id" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo (int)$department['department_id']; ?>" 
                        <?php echo $filter_department_id === (int)$department['department_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($search_name): ?>
                <input type="hidden" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- Main Content -->
    <div style="flex:1;">
        <h2 class="section-title">Search Doctors</h2>

        <!-- Search Form -->
        <form method="get" style="margin-bottom:24px; display:flex; gap:12px;">
            <input class="form-field" type="text" name="search_name" placeholder="Search by doctor name" 
                   value="<?php echo htmlspecialchars($search_name); ?>">
            <?php if ($filter_department_id > 0): ?>
                <input type="hidden" name="department_id" value="<?php echo (int)$filter_department_id; ?>">
            <?php endif; ?>
            <button class="btn primary" type="submit">Search</button>
        </form>

        <!-- Doctor Cards -->
        <div class="features-grid">
            <?php if (count($doctors) === 0): ?>
                <p>No doctors found.</p>
            <?php else: ?>
                <?php foreach ($doctors as $doc): ?>
                <div class="feature-card">
                    <div class="feature-icon">👨‍⚕️</div>
                    <h3><?php echo htmlspecialchars($doc['full_name']); ?></h3>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($doc['department']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($doc['phone']); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
</body>
</html>
