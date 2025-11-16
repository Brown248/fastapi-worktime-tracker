<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../db/config.php';
include __DIR__ . '/includes/header.php';

// ดึงสถิติ (ปรับ query ตาม schema จริงของ DB คุณ)
try {
    // จำนวนพนักงานทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM Employee");
    $totalEmp = (int)$stmt->fetchColumn();

    // บันทึกวันนี้
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Attendance WHERE Date = CURDATE()");
    $stmt->execute();
    $todayRecords = (int)$stmt->fetchColumn();

    // Top 5 พนักงานมาสาย
    $stmt = $pdo->query("
      SELECT e.Name, COUNT(a.Attendance_id) AS late_count
      FROM Attendance a
      JOIN Employee e ON a.Employee_id = e.Employee_id
      WHERE a.Status = 'Late'
      GROUP BY a.Employee_id
      ORDER BY late_count DESC
      LIMIT 5
    ");
    $lateTop = $stmt->fetchAll();

    // ข้อมูลกราฟ 7 วันล่าสุด
    $stmt = $pdo->query("
      SELECT Date, IFNULL(SUM(Total_work_time),0) AS total_minutes
      FROM Attendance
      WHERE Date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
      GROUP BY Date
      ORDER BY Date ASC
    ");
    $graphRows = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("[DASHBOARD_ERR] " . $e->getMessage());
    $totalEmp = $todayRecords = 0;
    $lateTop = $graphRows = [];
}
?>
<div class="container mt-3">
  <h2>Dashboard</h2>
  <div class="row">
    <div class="col-md-4">
      <div class="card p-3 mb-3">
        <h6>จำนวนพนักงาน</h6>
        <h3><?php echo $totalEmp; ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 mb-3">
        <h6>บันทึกวันนี้</h6>
        <h3><?php echo $todayRecords; ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 mb-3">
        <h6>พนักงานมาสาย (Top 5)</h6>
        <ul>
          <?php foreach ($lateTop as $r): ?>
            <li><?php echo htmlspecialchars($r['Name']) . " — " . (int)$r['late_count']; ?> ครั้ง</li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="card p-3">
    <h6>ชั่วโมงรวม (7 วันล่าสุด)</h6>
    <canvas id="workChart" height="80"></canvas>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
  const graphData = <?php
    $labels=[]; $values=[];
    foreach ($graphRows as $r) {
      $labels[] = $r['Date'];
      // แปลงนาทีเป็นชั่วโมง (float)
      $values[] = round(($r['total_minutes'] ?? 0) / 60, 2);
    }
    echo json_encode(['labels'=>$labels, 'values'=>$values]);
  ?>;

  const ctx = document.getElementById('workChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: graphData.labels,
      datasets: [{
        label: 'ชั่วโมงต่อวัน',
        data: graphData.values,
        fill: false,
        tension: 0.2
      }]
    },
    options: {
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>
