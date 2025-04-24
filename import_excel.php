<?php
require 'DatabaseConfig.php';
require 'vendor/autoload.php'; // ต้องติดตั้ง phpoffice/phpspreadsheet ก่อน

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // ข้ามหัวตาราง (แถว 1-8 สมมุติว่าเริ่มข้อมูลจริงจากแถว 9)
        $dataRows = array_slice($rows, 8);

        $conn = DatabaseConfig::getConnection();

        $stmt = $conn->prepare("
            INSERT INTO max_stock_report (
                product_code, product_name, barcode, warehouse_code, warehouse_name,
                stock_qty, stock_location_name, unit, remaining_stock
            ) VALUES (
                :product_code, :product_name, :barcode, :warehouse_code, :warehouse_name,
                :stock_qty, :stock_location_name, :unit, :remaining_stock
            )
        ");

        foreach ($dataRows as $row) {
            if (empty($row[0])) continue; // ข้ามแถวว่าง

            $stmt->execute([
                ':product_code'        => $row[0],
                ':product_name'        => $row[1],
                ':barcode'             => $row[2],
                ':warehouse_code'      => $row[3],
                ':warehouse_name'      => $row[4],
                ':stock_qty'           => (int)$row[5],
                ':stock_location_name' => $row[6],
                ':unit'                => $row[7],
                ':remaining_stock'     => (float)$row[8],
            ]);
        }

        echo "บันทึกข้อมูลเรียบร้อยแล้ว";

    } catch (Exception $e) {
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>
