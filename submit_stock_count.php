<?php
require_once __DIR__ . '/DatabaseConfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['count'])) {
    $counts = $_POST['count']; // [barcode => counted_qty]

    try {
        $conn = DatabaseConfig::getConnection();
        $stmt = $conn->prepare("
            INSERT INTO stock_count_log (barcode, counted_qty, counted_at)
            VALUES (:barcode, :qty, NOW())
        ");

        foreach ($counts as $barcode => $qty) {
            if ($qty === '' || !is_numeric($qty)) continue;

            $stmt->execute([
                ':barcode' => $barcode,
                ':qty' => $qty
            ]);
        }

        echo "<p>✅ บันทึกการนับสำเร็จ</p><a href='stock_count.php'>กลับ</a>";

    } catch (Exception $e) {
        die("เกิดข้อผิดพลาด: " . $e->getMessage());
    }
} else {
    echo "❌ ไม่มีข้อมูลสำหรับบันทึก";
}
?>
