<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../db/config.php';
include __DIR__ . '/includes/header.php';

$errors=[]; $success=null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token ไม่ถูกต้อง";
    } else {
        $month = (int)($_POST['month'] ?? 0);
        $year = (int)($_POST['year'] ?? 0);
        if (!$month || !$year) $errors[] = "ระบุเดือนและปีให้ถูกต้อง";

        if (empty($errors)) {
            // ข้อสมมติ: ชั่วโมงปกติ/เดือน = 160 (แก้ตามนโยบายจริง)
            $regular_minutes_per_month = 160 * 60;

            $emps = $pdo->query("SELECT Employee_id, Salary FROM Employee WHERE Status = 'Active'")->fetchAll();
            foreach ($emps as $emp) {
                // รวม Total_work_time ของเดือน
                $stmt = $pdo->prepare("SELECT IFNULL(SUM(Total_work_time),0) FROM Attendance WHERE Employee_id = ? AND MONTH(Date)=? AND YEAR(Date)=?");
                $stmt->execute([$emp['Employee_id'], $month, $year]);
                $sum_minutes = (int)$stmt->fetchColumn();

                $regular_minutes = min($sum_minutes, $regular_minutes_per_month);
                $ot_minutes = max(0, $sum_minutes - $regular_minutes_per_month);

                // สมมติ: เงินเดือนแบ่งตาม 160 ชั่วโมง/เดือน
                $salary = (float)$emp['Salary'];
                $hourly = ($salary / 160);
                $overtime_pay = $hourly * ($ot_minutes/60) * 1.5; // OT rate 1.5

                // บันทึกลง Payroll (INSERT หรือ UPDATE)
                // *ต้องมี UNIQUE constraint บน (Employee_id, Month, Year) ในตาราง Payroll*
                $stmt = $pdo->prepare("
                    INSERT INTO Payroll (Employee_id, Month, Year, Base_salary, Overtime_pay, Deductions, Payment_status)
                    VALUES (?, ?, ?, ?, ?, 0, 'Pending')
                    ON DUPLICATE KEY UPDATE Base_salary=VALUES(Base_salary), Overtime_pay=VALUES(Overtime_pay), Deductions=VALUES(Deductions), Payment_status='Pending'
                ");
                $stmt->execute([$emp['Employee_id'], $month, $year, $salary, $overtime_pay]);
            }
            $success = "สร้าง payroll สำหรับ {$month}/{$year} เรียบร้อย";
        }
    }
}

// fetch payrolls (recent)
$payrolls = $pdo->query("SELECT p.*, e.Name FROM Payroll p JOIN Employee e ON p.Employee_id = e.Employee_id ORDER BY p.Year DESC, p.Month DESC")->fetchAll();
?>

<div class="container">
  <h2>Payroll</h2>
  <?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>
  <?php foreach ($errors as $e) echo "<div class='alert alert-danger'>".htmlspecialchars($e)."</div>"; ?>

  <div class="card mb-3 p-3">
    <form method="post" class="row g-2">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <input type="hidden" name="action" value="generate">
      <div class="col-md-2"><input type="number" name="month" min="1" max="12" value="<?php echo date('n'); ?>" class="form-control"></div>
      <div class="col-md-2"><input type="number" name="year" min="2000" value="<?php echo date('Y'); ?>" class="form-control"></div>
      <div class="col-md-2"><button class="btn btn-primary">Generate</button></div>
    </form>
  </div>

  <table class="table table-striped">
    <thead><tr><th>พนักงาน</th><th>เดือน/ปี</th><th>Base</th><th>OT</th><th>Total</th><th>สถานะ</th></tr></thead>
    <tbody>
      <?php foreach ($payrolls as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p['Name']); ?></td>
        <td><?php echo htmlspecialchars($p['Month'].'/'.$p['Year']); ?></td>
        <td><?php echo number_format($p['Base_salary'],2); ?></td>
        <td><?php echo number_format($p['Overtime_pay'],2); ?></td>
        <td><?php echo number_format($p['Total_salary'],2); ?></td>
        <td><?php echo htmlspecialchars($p['Payment_status']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
