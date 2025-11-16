<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// สร้าง CSRF token ถ้ายังไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - ระบบบันทึกเวลา</title>
  <!-- Bootstrap CDN (เปลี่ยนเป็น local ถ้าต้องการ) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding-top: 70px; }
    .sidebar { min-height: calc(100vh - 70px); }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">WorkingTime - Admin</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><span class="nav-link">สวัสดี, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">ออกจากระบบ</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar">
      <?php include __DIR__ . '/sidebar.php'; ?>
    </div>
    <div class="col-md-10">
