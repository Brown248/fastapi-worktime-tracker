<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../db/config.php';
include __DIR__ . '/includes/header.php';

$errors=[]; $success=null;

// Handle Clock action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clock') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token ไม่ถูกต้อง";
    } else {
        $eid = (int)($_POST['employee_id'] ?? 0);
        $type = ($_POST['clock_type'] ?? 'in') === 'out' ? 'out' : 'in';
        $today = date('Y-m-d');

        // fetch today's record
        $stmt = $pdo->prepare("SELECT * FROM Attendance WHERE Employee_id = ? AND Date = ?");
        $stmt->execute([$eid, $today]);
        $row = $stmt->fetch();

        if ($type === 'in') {
            if ($row) {
                $errors[] = "วันนี้มีการบันทึกไว้แล้ว (โปรดแก้ไขหรือลบ record ก่อน)";
            } else {
                $stmt = $pdo->prepare("INSERT INTO Attendance (Employee_id, Date, Clock_in, Status) VALUES (?, ?, ?, 'Present')");
                $stmt->execute([$eid, $today, date('H:i:s')]);
                $success = "Clock in สำเร็จ";
            }
        } else {
            if (!$row) {
                $errors[] = "ยังไม่มี Clock in สำหรับวันนี้";
            } else {
                // update clock out
                $clockIn = $row['Clock_in'] ?? null;
                $clockOut = date('H:i:s');
                $stmt = $pdo->prepare("UPDATE Attendance SET Clock_out = ? WHERE Attendance_id = ?");
                $stmt->execute([$clockOut, $row['Attendance_id']]);

                // คำนวณ total work minutes ถ้ามี Clock_in
                if ($clockIn) {
                    $in = new DateTime($clockIn);
                    $out = new DateTime($clockOut);
                    $minutes = (int)floor(($out->getTimestamp() - $in->getTimestamp()) / 60);
                    $stmt = $pdo->prepare("UPDATE Attendance SET Total_work_time = ? WHERE Attendance_id = ?");
                    $stmt->execute([$minutes, $row['Attendance_id']]);
                }
                $success = "Clock out สำเร็จ";
            }
        }
    }
}

// filters
$empFilter = (int)($_GET['employee_id'] ?? 0);
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// fetch attendances
$params = [$dateFrom, $dateTo];
$sql = "SELECT a.*, e.Name FROM Attendance a JOIN Employee e ON a.Employee_id = e.Employee_id WHERE a.Date BETWEEN ? AND ?";
if ($empFilter) { $sql .= " AND a.Employee_id = ?"; $params[] = $empFilter; }
$sql .= " ORDER BY a.Date DESC, a.Attendance_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// employee list for selector
$emps = $pdo->query("SELECT Employee_id, Name FROM Employee ORDER BY Name ASC")->fetchAll();
?>

<div class="container">
  <h2>บันทึกเวลา</h2>
  <?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>
  <?php foreach ($errors as $e) echo "<div class='alert alert-danger'>".htmlspecialchars($e)."</div>"; ?>

  <div class="card mb-3 p-3">
    <h6>Clock In / Clock Out</h6>
    <form method="post" class="row g-2">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
      <input type="hidden" name="action" value="clock">
      <div class="col-md-4">
        <select name="employee_id" class="form-select" required>
          <option value="">-- เลือกพนักงาน --</option>
          <?php foreach ($emps as $em) echo "<option value='{$em['Employee_id']}'>".htmlspecialchars($em['Name'])."</option>"; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="clock_type" class="form-select">
          <option value="in">Clock In</option>
          <option value="out">Clock Out</option>
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-primary">บันทึก</button></div>
    </form>
  </div>

  <div class="card mb-3 p-3">
    <h6>ค้นหา</h6>
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <select name="employee_id" class="form-select">
          <option value="0">-- ทุกพนักงาน --</option>
          <?php foreach ($emps as $em) {
            $sel = ($empFilter == $em['Employee_id']) ? 'selected' : '';
            echo "<option $sel value='{$em['Employee_id']}'>".htmlspecialchars($em['Name'])."</option>";
          } ?>
        </select>
      </div>
      <div class="col-md-3"><input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>"></div>
      <div class="col-md-3"><input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>"></div>
      <div class="col-md-2"><button class="btn btn-secondary">ค้นหา</button></div>
    </form>
  </div>

  <table class="table table-striped">
    <thead><tr><th>วันที่</th><th>พนักงาน</th><th>เข้า</th><th>ออก</th><th>รวม (นาที)</th><th>สถานะ</th><th>หมายเหตุ</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['Date']); ?></td>
        <td><?php echo htmlspecialchars($r['Name']); ?></td>
        <td><?php echo htmlspecialchars($r['Clock_in']); ?></td>
        <td><?php echo htmlspecialchars($r['Clock_out']); ?></td>
        <td><?php echo htmlspecialchars($r['Total_work_time']); ?></td>
        <td><?php echo htmlspecialchars($r['Status']); ?></td>
        <td><?php echo htmlspecialchars($r['Remark']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
