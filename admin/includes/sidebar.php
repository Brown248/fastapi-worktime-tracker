<?php
$active = basename($_SERVER['PHP_SELF']);
?>
<div class="list-group">
  <a href="dashboard.php" class="list-group-item list-group-item-action <?php if ($active==='dashboard.php') echo 'active'; ?>">Dashboard</a>
  <a href="employee_manage.php" class="list-group-item list-group-item-action <?php if ($active==='employee_manage.php') echo 'active'; ?>">จัดการพนักงาน</a>
  <a href="attendance_manage.php" class="list-group-item list-group-item-action <?php if ($active==='attendance_manage.php') echo 'active'; ?>">บันทึกเวลา</a>
  <a href="leave_manage.php" class="list-group-item list-group-item-action <?php if ($active==='leave_manage.php') echo 'active'; ?>">คำขอใบลา</a>
  <a href="payroll_manage.php" class="list-group-item list-group-item-action <?php if ($active==='payroll_manage.php') echo 'active'; ?>">Payroll</a>
</div>
