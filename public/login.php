<?php
// public/login.php
// -------------------------------
// หน้าเข้าสู่ระบบสำหรับ Admin
// ใช้ร่วมกับตาราง Admin ในฐานข้อมูล
// -------------------------------

// เริ่ม session
if (session_status() === PHP_SESSION_NONE) session_start();

// ถ้า login อยู่แล้ว → ไป dashboard เลย
if (!empty($_SESSION['user_id'])) {
    header("Location: ../admin/dashboard.php");
    exit;
}

require_once "../db/config.php"; // เชื่อมฐานข้อมูล (มี PDO → $pdo)

// ตัวแปรไว้เก็บข้อความแจ้งเตือน
$error = "";

// -------------------------------
// เมื่อมีการ submit ฟอร์ม
// -------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // ตรวจข้อมูลว่าง
    if ($username === "" || $password === "") {
        $error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    } else {

        try {
            // เตรียมคำสั่ง SQL เพื่อดึงข้อมูลผู้ใช้
            $sql = "SELECT Admin_id, Username, Password, Role 
                    FROM Admin 
                    WHERE Username = :u 
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":u" => $username]);
            $user = $stmt->fetch();

            if ($user) {

                // ตรวจสอบรหัสผ่านแบบปลอดภัย
                if (password_verify($password, $user["Password"])) {

                    // ตั้ง session
                    $_SESSION["user_id"] = $user["Admin_id"];
                    $_SESSION["username"] = $user["Username"];
                    $_SESSION["role"] = $user["Role"];

                    // Redirect หลังล็อกอินสำเร็จ
                    header("Location: ../admin/dashboard.php");
                    exit;

                } else {
                    $error = "รหัสผ่านไม่ถูกต้อง";
                }

            } else {
                $error = "ไม่พบชื่อผู้ใช้นี้ในระบบ";
            }

        } catch (Exception $e) {
            error_log("LOGIN_ERR: ".$e->getMessage());
            $error = "เกิดข้อผิดพลาด กรุณาลองใหม่ภายหลัง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เข้าสู่ระบบ Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f2f2f2;
    }
    .login-box {
      max-width: 420px;
      margin: 6% auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }
  </style>
</head>
<body>

<div class="login-box">
  <h3 class="text-center mb-3">🔐 เข้าสู่ระบบ Admin</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">ชื่อผู้ใช้</label>
      <input type="text" name="username" class="form-control" required placeholder="กรอกชื่อผู้ใช้">
    </div>

    <div class="mb-3">
      <label class="form-label">รหัสผ่าน</label>
      <input type="password" name="password" class="form-control" required placeholder="กรอกรหัสผ่าน">
    </div>

    <button class="btn btn-primary w-100 mt-2">เข้าสู่ระบบ</button>
  </form>
</div>

</body>
</html>
