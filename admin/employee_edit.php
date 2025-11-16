<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../db/config.php';
include __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<div class='container'><div class='alert alert-danger'>ไม่พบรหัสพนักงาน</div></div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

$errors = []; $success = null;

// fetch employee
$stmt = $pdo->prepare("SELECT * FROM Employee WHERE Employee_id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    echo "<div class='container'><div class='alert alert-danger'>ไม่พบข้อมูลพนักงาน</div></div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF token ไม่ถูกต้อง";
    } else {
        $name = trim($_POST['name'] ?? '');
        $dept = trim($_POST['department'] ?? null);
        $pos = trim($_POST['position'] ?? null);
        $hire_date = $_POST['hire_date'] ?: null;
        $salary = is_numeric($_POST['salary'] ?? '') ? (float)$_POST['salary'] : 0;
        $email = $_POST['email'] ?: null;
        $phone = $_POST['phone'] ?: null;
        $status = in_array($_POST['status'] ?? '', ['Active','Inactive']) ? $_POST['status'] : 'Active';

        if ($name === '') $errors[] = "กรุณากรอกชื่อ";

        if (empty($errors)) {
            $sql = "UPDATE Employee SET Name=:name, Department=:dept, Position=:pos, Hire_date=:hire, Salary=:salary, Email=:email, Phone_number=:phone, Status=:status WHERE Employee_id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'=>$name, ':dept'=>$dept, ':pos'=>$pos, ':hire'=>$hire_date,
                ':salary'=>$salary, ':email'=>$email, ':phone'=>$phone, ':status'=>$status, ':id'=>$id
            ]);
            $success = "บันทึกการแก้ไขเรียบร้อย";
            // refresh data
            $stmt = $pdo->prepare("SELECT * FROM Employee WHERE Employee_id = ?");
            $stmt->execute([$id]);
            $emp = $stmt->fetch();
        }
    }
}
?>

<div class="container">
  <h2>แก้ไขข้อมูลพนักงาน</h2>
  <?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>
  <?php foreach ($errors as $e) echo "<div class='alert alert-danger'>".htmlspecialchars($e)."</div>"; ?>

  <form method="post" class="row g-2">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <div class="col-md-4">
      <label>ชื่อ</label>
      <input name="name" class="form-control" value="<?php echo htmlspecialchars($emp['Name']); ?>">
    </div>
    <div class="col-md-3">
      <label>แผนก</label>
      <input name="department" class="form-control" value="<?php echo htmlspecialchars($emp['Department']); ?>">
    </div>
    <div class="col-md-3">
      <label>ตำแหน่ง</label>
      <input name="position" class="form-control" value="<?php echo htmlspecialchars($emp['Position']); ?>">
    </div>
    <div class="col-md-2">
      <label>วันที่เริ่มงาน</label>
      <input type="date" name="hire_date" class="form-control" value="<?php echo htmlspecialchars($emp['Hire_date']); ?>">
    </div>
    <div class="col-md-3">
      <label>เงินเดือน</label>
      <input name="salary" class="form-control" value="<?php echo htmlspecialchars($emp['Salary']); ?>">
    </div>
    <div class="col-md-3">
      <label>อีเมล</label>
      <input name="email" class="form-control" value="<?php echo htmlspecialchars($emp['Email']); ?>">
    </div>
    <div class="col-md-3">
      <label>เบอร์โทร</label>
      <input name="phone" class="form-control" value="<?php echo htmlspecialchars($emp['Phone_number']); ?>">
    </div>
    <div class="col-md-3">
      <label>สถานะ</label>
      <select name="status" class="form-select">
        <option value="Active" <?php if ($emp['Status']=='Active') echo 'selected'; ?>>Active</option>
        <option value="Inactive" <?php if ($emp['Status']=='Inactive') echo 'selected'; ?>>Inactive</option>
      </select>
    </div>
    <div class="col-12 mt-2">
      <button class="btn btn-primary">บันทึก</button>
      <a href="employee_manage.php" class="btn btn-outline-secondary">ยกเลิก</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
