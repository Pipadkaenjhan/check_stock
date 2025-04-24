<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>อัปโหลดไฟล์ Excel</title>
</head>
<body>
    <h2>อัปโหลด Excel เพื่อนำเข้าข้อมูลสินค้า</h2>
    <form action="import_excel.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="excel_file" accept=".xls,.xlsx" required>
        <button type="submit">อัปโหลดและบันทึก</button>
    </form>
</body>
</html>
