<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../db/config.php';
include __DIR__ . '/includes/header.php';

$errors = [];
$success = null;

// Handle Add new employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token ไม่ถูกต้อง";
    } else {
        // อ่านค่าและ validate เบื้องต้น
        $name = trim($_POST['name'] ?? '');
        $dept = trim($_POST['department'] ?? null);
        $pos = trim($_POST['position'] ?? null);
        $hire_date = $_POST['hire_date'] ?: null;
        $salary = is_numeric($_POST['salary'] ?? '') ? (float)$_POST['salary'] : 0;
        $email = $_POST['email'] ?: null;
        $phone = $_POST['phone'] ?: null;

        if ($name === '') $errors[] = "กรุณากรอกชื่อ";

        if (empty($errors)) {
            $sql = "INSERT INTO Employee (Name, Department, Position, Hire_date, Salary, Status, Email, Phone_number)
                    VALUES (:name, :dept, :pos, :hire, :salary, 'Active', :email, :phone)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'=>$name,
                ':dept'=>$dept,
                ':pos'=>$pos,
                ':hire'=>$hire_date,
                ':salary'=>$salary,
                ':email'=>$email,
                ':phone'=>$phone
            ]);
            $success = "เพิ่มพนักงานเรียบร้อย";
        }
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token ไม่ถูกต้อง";
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM Employee WHERE Employee_id = ?");
            $stmt->execute([$id]);
            $success = "ลบข้อมูลเรียบร้อย";
        }
    }
}

// Pagination simple
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$start = ($page-1)*$perPage;
$total = (int)$pdo->query("SELECT COUNT(*) FROM Employee")->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM Employee ORDER BY Employee_id DESC LIMIT :start, :limit");
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<div class="container">
  <h2>จัดการพนักงาน</h2>
  <?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>
  <?php foreach ($errors as $e) echo "<div class='alert alert-danger'>".htmlspecialchars($e)."</div>"; ?>

  <div class="card mb-3">
    <div class="card-body">
      <h6>เพิ่มพนักงานใหม่</h6>
      <form method="post" class="row g-2">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="action" value="add">
        <div class="col-md-4"><input name="name" class="form-control" placeholder="ชื่อ (จำเป็น)"></div>
        <div class="col-md-3"><input name="department" class="form-control" placeholder="แผนก"></div>
        <div class="col-md-3"><input name="position" class="form-control" placeholder="ตำแหน่ง"></div>
        <div class="col-md-2"><input type="date" name="hire_date" class="form-control"></div>
        <div class="col-md-3"><input name="salary" class="form-control" placeholder="เงินเดือน"></div>
        <div class="col-md-3"><input name="email" class="form-control" placeholder="อีเมล"></div>
        <div class="col-md-3"><input name="phone" class="form-control" placeholder="เบอร์โทร"></div>
        <div class="col-md-3"><button class="btn btn-primary">เพิ่ม</button></div>
      </form>
    </div>
  </div>

  <table class="table table-striped">
    <thead><tr><th>รหัส</th><th>ชื่อ</th><th>แผนก</th><th>ตำแหน่ง</th><th>เงินเดือน</th><th>จัดการ</th></tr></thead>
    <tbody>
      <?php foreach ($employees as $emp): ?>
      <tr>
        <td><?php echo $emp['Employee_id']; ?></td>
        <td><?php echo htmlspecialchars($emp['Name']); ?></td>
        <td><?php echo htmlspecialchars($emp['Department']); ?></td>
        <td><?php echo htmlspecialchars($emp['Position']); ?></td>
        <td><?php echo number_format($emp['Salary'],2); ?></td>
        <td>
          <a class="btn btn-sm btn-info" href="employee_edit.php?id=<?php echo $emp['Employee_id']; ?>">แก้ไข</a>
          <form method="post" style="display:inline" onsubmit="return confirm('ลบข้อมูลพนักงานใช่หรือไม่?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo $emp['Employee_id']; ?>">
            <button class="btn btn-sm btn-danger">ลบ</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- pagination -->
  <?php
    $last = (int)ceil($total / $perPage);
    for ($i=1;$i<=$last;$i++) {
      $cls = ($i==$page) ? 'btn-primary' : 'btn-outline-secondary';
      echo "<a class='btn btn-sm $cls me-1' href='?page=$i'>$i</a>";
    }
  ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
