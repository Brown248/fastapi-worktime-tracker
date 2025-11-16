<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../db/config.php';
include __DIR__ . '/includes/header.php';

$errors=[]; $success=null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['approve','reject'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token ไม่ถูกต้อง";
    } else {
        $act = $_POST['action'];
        $id = (int)($_POST['id'] ?? 0);
        $status = ($act === 'approve') ? 'Approved' : 'Rejected';
        $stmt = $pdo->prepare("UPDATE Leave_request SET Approval_status = ?, Approved_by = ?, Approved_date = CURDATE() WHERE Leave_id = ?");
        $stmt->execute([$status, $_SESSION['user_id'], $id]);
        $success = ($act === 'approve') ? "อนุมัติคำขอลาเรียบร้อย" : "ปฏิเสธคำขอลาเรียบร้อย";
    }
}

// Fetch leaves
$stmt = $pdo->query("SELECT l.*, e.Name FROM Leave_request l JOIN Employee e ON l.Employee_id = e.Employee_id ORDER BY l.Start_date DESC");
$leaves = $stmt->fetchAll();
?>

<div class="container">
  <h2>คำขอใบลา</h2>
  <?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>
  <?php foreach ($errors as $e) echo "<div class='alert alert-danger'>".htmlspecialchars($e)."</div>"; ?>

  <table class="table table-bordered">
    <thead><tr><th>พนักงาน</th><th>ประเภท</th><th>จาก</th><th>ถึง</th><th>จำนวนวัน</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
    <tbody>
      <?php foreach ($leaves as $l): ?>
      <tr>
        <td><?php echo htmlspecialchars($l['Name']); ?></td>
        <td><?php echo htmlspecialchars($l['Leave_type']); ?></td>
        <td><?php echo htmlspecialchars($l['Start_date']); ?></td>
        <td><?php echo htmlspecialchars($l['End_date']); ?></td>
        <td><?php echo htmlspecialchars($l['Total_days']); ?></td>
        <td><?php echo htmlspecialchars($l['Approval_status']); ?></td>
        <td>
          <?php if ($l['Approval_status'] === 'Pending'): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
              <input type="hidden" name="action" value="approve">
              <input type="hidden" name="id" value="<?php echo $l['Leave_id']; ?>">
              <button class="btn btn-sm btn-success">อนุมัติ</button>
            </form>
            <form method="post" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
              <input type="hidden" name="action" value="reject">
              <input type="hidden" name="id" value="<?php echo $l['Leave_id']; ?>">
              <button class="btn btn-sm btn-danger">ปฏิเสธ</button>
            </form>
          <?php else: echo "—"; endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
