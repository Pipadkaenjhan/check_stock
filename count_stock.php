<?php
require_once __DIR__ . '/Config.php'; // ✅ ชัวร์สุด

try {
    $conn = DatabaseConfig::getConnection();
    $stmt = $conn->query("
        SELECT      
            p.barcode as barcode, 
            p.product_code as product_code, 
            p.product_name as product_name,
            i.balance_qty as balance_qty,
            p.unit as unit, 
            p.warehouse as warehouse, 
            p.location as location,
            p.address as address,
            p.created_at, 
            p.updated_at
        FROM ims_products p
        LEFT JOIN ic_inventory i ON i.code = p.product_code
        ORDER BY p.product_code ASC
    ");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}

// กำหนดคลังสินค้าทั้งหมดเพื่อใช้ในการกรอง
$warehouses = [];
foreach ($products as $product) {
    if (!in_array($product['warehouse'], $warehouses) && !empty($product['warehouse'])) {
        $warehouses[] = $product['warehouse'];
    }
}
sort($warehouses);

// ดึงค่าจากการค้นหาหรือกรอง
$search = isset($_GET['search']) ? $_GET['search'] : '';
$warehouseFilter = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>นับสต๊อกสินค้า</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- เพิ่ม Font Awesome สำหรับไอคอน -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @media print {
      .no-print {
        display: none !important;
      }
      .print-only {
        display: block !important;
      }
      body {
        font-size: 12px;
      }
    }
    .sticky-header th {
      position: sticky;
      top: 0;
      background-color: #e5e7eb;
      z-index: 10;
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="max-w-7xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden my-4">
    <!-- หัวเพจ -->
    <div class="bg-blue-600 text-white p-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold">📦 ระบบนับสต๊อกสินค้า (เทียบกับจำนวนคงเหลือ)</h1>
      <div class="text-sm">
        <p>วันที่นับ: <?php echo date('d/m/Y'); ?></p>
        <p>เวลา: <?php echo date('H:i:s'); ?></p>
      </div>
    </div>
    
    <!-- แถบค้นหาและกรอง -->
    <div class="p-4 bg-gray-50 border-b no-print">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
          <form action="" method="GET" class="flex space-x-2">
            <div class="flex-1">
              <div class="relative">
                <input 
                  type="text" 
                  name="search" 
                  value="<?php echo htmlspecialchars($search); ?>"
                  placeholder="ค้นหาด้วยบาร์โค้ด, รหัส หรือชื่อสินค้า" 
                  class="w-full px-4 py-2 border rounded-lg pl-10"
                  autocomplete="off"
                  id="searchInput"
                >
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <i class="fas fa-search text-gray-400"></i>
                </div>
              </div>
            </div>
            <div class="w-48">
              <select name="warehouse" class="w-full px-4 py-2 border rounded-lg">
                <option value="">ทุกคลังสินค้า</option>
                <?php foreach ($warehouses as $warehouse): ?>
                  <option value="<?php echo htmlspecialchars($warehouse); ?>" <?php echo $warehouseFilter === $warehouse ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($warehouse); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
              <i class="fas fa-filter mr-1"></i> กรอง
            </button>
          </form>
        </div>
        <div class="flex space-x-2">
          <button onclick="focusBarcode()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
            <i class="fas fa-barcode mr-1"></i> สแกนบาร์โค้ด
          </button>
          <button onclick="window.print()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
            <i class="fas fa-print mr-1"></i> พิมพ์
          </button>
        </div>
      </div>
    </div>

    <!-- แสดงข้อมูลการกรอง -->
    <?php if ($search || $warehouseFilter): ?>
    <div class="p-2 bg-yellow-50 border-b no-print">
      <div class="flex items-center text-sm">
        <span class="mr-2"><i class="fas fa-filter text-yellow-600"></i> กำลังกรอง:</span>
        <?php if ($search): ?>
          <span class="mr-2 bg-yellow-100 px-2 py-1 rounded">คำค้นหา: <?php echo htmlspecialchars($search); ?></span>
        <?php endif; ?>
        <?php if ($warehouseFilter): ?>
          <span class="mr-2 bg-yellow-100 px-2 py-1 rounded">คลัง: <?php echo htmlspecialchars($warehouseFilter); ?></span>
        <?php endif; ?>
        <a href="?" class="text-blue-600 hover:underline ml-auto"><i class="fas fa-times"></i> ล้างตัวกรอง</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- ฟอร์มนับสต๊อก -->
    <form action="submit_stock_count.php" method="POST" id="stockCountForm">
      <input type="hidden" name="count_date" value="<?php echo date('Y-m-d'); ?>">
      <input type="hidden" name="count_by" value="">

      <div class="overflow-x-auto max-h-[70vh]">
        <table class="w-full text-sm border-collapse">
          <thead class="sticky-header">
            <tr class="bg-gray-200 text-left">
              <th class="p-2 border">#</th>
              <th class="p-2 border">บาร์โค้ด</th>
              <th class="p-2 border">รหัสสินค้า</th>
              <th class="p-2 border">ชื่อสินค้า</th>
              <th class="p-2 border">จำนวนคงเหลือ</th>
              <th class="p-2 border">หน่วย</th>
              <th class="p-2 border">คลัง</th>
              <th class="p-2 border">ที่เก็บ</th>
              <th class="p-2 border">ที่อยู่</th>
              <th class="p-2 border w-32">จำนวนที่นับได้</th>
              <th class="p-2 border w-20 no-print">การดำเนินการ</th>
            </tr>
          </thead>
          <tbody id="productTable">
            <?php 
            $displayCount = 0;
            foreach ($products as $index => $item): 
              // กรองตามคำค้นหาและคลังสินค้า
              if (($search && 
                  stripos($item['barcode'], $search) === false && 
                  stripos($item['product_code'], $search) === false && 
                  stripos($item['product_name'], $search) === false) ||
                  ($warehouseFilter && $item['warehouse'] != $warehouseFilter)) {
                  continue;
              }
              $displayCount++;
            ?>
              <tr class="hover:bg-gray-50" id="row-<?php echo htmlspecialchars($item['barcode']); ?>">
                <td class="p-2 border"><?php echo $displayCount; ?></td>
                <td class="p-2 border barcode-cell"><?php echo htmlspecialchars($item['barcode']); ?></td>
                <td class="p-2 border"><?php echo htmlspecialchars($item['product_code']); ?></td>
                <td class="p-2 border"><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td class="p-2 border font-medium text-blue-700"><?php echo number_format($item['balance_qty'] ?? 0); ?></td>
                <td class="p-2 border"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="p-2 border"><?php echo htmlspecialchars($item['warehouse']); ?></td>
                <td class="p-2 border"><?php echo htmlspecialchars($item['location']); ?></td>
                <td class="p-2 border"><?php echo htmlspecialchars($item['address']); ?></td>
                <td class="p-2 border">
                  <input 
                    type="number" 
                    name="count[<?php echo $item['barcode']; ?>]" 
                    id="count-<?php echo htmlspecialchars($item['barcode']); ?>"
                    class="w-full px-2 py-1 border rounded count-input" 
                    placeholder="0"
                    min="0"
                    step="1"
                    data-barcode="<?php echo htmlspecialchars($item['barcode']); ?>"
                    data-balance="<?php echo $item['balance_qty'] ?? 0; ?>"
                  >
                </td>
                <td class="p-2 border text-center no-print">
                  <button type="button" onclick="quickFill('<?php echo htmlspecialchars($item['barcode']); ?>')" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus-circle"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($displayCount == 0): ?>
              <tr>
                <td colspan="10" class="p-4 text-center text-gray-500">ไม่พบรายการสินค้าที่ตรงกับเงื่อนไขการค้นหา</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- สรุปและปุ่มส่ง -->
      <div class="p-4 bg-gray-50 border-t flex flex-col md:flex-row justify-between items-center">
        <div class="flex items-center mb-4 md:mb-0">
          <span class="text-gray-700 mr-2">รายการทั้งหมด: <strong id="total-items"><?php echo $displayCount; ?></strong> รายการ</span>
          <span class="text-gray-700 mx-2">|</span>
          <span class="text-gray-700 mr-2">นับแล้ว: <strong id="counted-items">0</strong> รายการ</span>
          <span class="text-gray-700 mx-2">|</span>
          <span class="text-gray-700">ยังไม่ได้นับ: <strong id="remaining-items"><?php echo $displayCount; ?></strong> รายการ</span>
        </div>
        <div>
          <button type="button" onclick="fillFromBalance()" class="bg-blue-600 text-white px-4 py-2 rounded-lg mr-2 hover:bg-blue-700 no-print">
            <i class="fas fa-database mr-1"></i> ใช้ข้อมูลคงเหลือ
          </button>
          <button type="button" onclick="clearAllCounts()" class="bg-red-600 text-white px-4 py-2 rounded-lg mr-2 hover:bg-red-700 no-print">
            <i class="fas fa-trash-alt mr-1"></i> ล้างทั้งหมด
          </button>
          <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 no-print">
            <i class="fas fa-save mr-1"></i> บันทึกการนับสต๊อก
          </button>
        </div>
      </div>
    </form>

    <!-- โมดัลสแกนบาร์โค้ด -->
    <div id="barcodeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 no-print">
      <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">สแกนบาร์โค้ด</h3>
          <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="mb-4">
          <input 
            type="text" 
            id="barcodeInput" 
            class="w-full px-4 py-3 border rounded-lg text-center text-lg font-mono"
            placeholder="สแกนบาร์โค้ดที่นี่"
            autocomplete="off"
          >
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนที่นับได้:</label>
          <input 
            type="number" 
            id="modalQuantity" 
            class="w-full px-4 py-3 border rounded-lg text-center text-lg"
            value="1"
            min="0"
          >
        </div>
        <div class="flex justify-end">
          <button onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2 hover:bg-gray-600">
            ยกเลิก
          </button>
          <button onclick="addQuantity()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
            <i class="fas fa-plus mr-1"></i> เพิ่มจำนวน
          </button>
        </div>
        <div id="scanStatus" class="mt-3 text-center hidden"></div>
      </div>
    </div>
  </div>

  <script>
    // ฟังก์ชั่นสำหรับเปิดโมดัลสแกนบาร์โค้ด
    function focusBarcode() {
      document.getElementById('barcodeModal').classList.remove('hidden');
      document.getElementById('barcodeModal').classList.add('flex');
      document.getElementById('barcodeInput').focus();
    }

    // ฟังก์ชั่นสำหรับปิดโมดัล
    function closeModal() {
      document.getElementById('barcodeModal').classList.add('hidden');
      document.getElementById('barcodeModal').classList.remove('flex');
      document.getElementById('scanStatus').classList.add('hidden');
      document.getElementById('scanStatus').textContent = '';
      document.getElementById('barcodeInput').value = '';
    }

    // ฟังก์ชั่นสำหรับเพิ่มจำนวนจากโมดัล
    function addQuantity() {
      const barcode = document.getElementById('barcodeInput').value.trim();
      const quantity = parseInt(document.getElementById('modalQuantity').value) || 0;
      
      if (!barcode) {
        showScanStatus('กรุณาสแกนบาร์โค้ดก่อน', 'text-red-600');
        return;
      }

      // ค้นหา input field ที่ตรงกับบาร์โค้ด
      const inputField = document.getElementById(`count-${barcode}`);
      
      if (inputField) {
        // อัพเดตค่าหรือบวกเพิ่ม
        const currentValue = parseInt(inputField.value) || 0;
        inputField.value = currentValue + quantity;
        
        // ไฮไลท์แถวที่พบ
        const row = document.getElementById(`row-${barcode}`);
        if (row) {
          row.classList.add('bg-green-50');
          setTimeout(() => {
            row.classList.remove('bg-green-50');
          }, 2000);
          
          // เลื่อนไปที่แถวนั้น
          row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        updateCountSummary();
        showScanStatus(`เพิ่มจำนวน ${quantity} สำหรับบาร์โค้ด ${barcode} เรียบร้อยแล้ว`, 'text-green-600');
        
        // เคลียร์ค่า input และเตรียมสแกนชิ้นต่อไป
        document.getElementById('barcodeInput').value = '';
        document.getElementById('barcodeInput').focus();
      } else {
        showScanStatus(`ไม่พบสินค้าที่มีบาร์โค้ด: ${barcode}`, 'text-red-600');
      }
    }

    // แสดงสถานะการสแกน
    function showScanStatus(message, className) {
      const status = document.getElementById('scanStatus');
      status.textContent = message;
      status.className = `mt-3 text-center ${className}`;
      status.classList.remove('hidden');
    }

    // ฟังก์ชั่นสำหรับเพิ่มจำนวนเร็วๆ (เพิ่ม 1)
    function quickFill(barcode) {
      const inputField = document.getElementById(`count-${barcode}`);
      if (inputField) {
        const currentValue = parseInt(inputField.value) || 0;
        inputField.value = currentValue + 1;
        updateCountSummary();
      }
    }

    // ฟังก์ชั่นล้างค่าที่นับทั้งหมด
    function clearAllCounts() {
      if (confirm('คุณแน่ใจหรือไม่ที่จะล้างข้อมูลการนับทั้งหมด?')) {
        document.querySelectorAll('.count-input').forEach(input => {
          input.value = '';
        });
        updateCountSummary();
      }
    }
    
    // ฟังก์ชั่นนำจำนวนคงเหลือมาใส่เป็นจำนวนที่นับได้
    function fillFromBalance() {
      if (confirm('คุณต้องการใช้ข้อมูลจำนวนคงเหลือเป็นจำนวนที่นับได้หรือไม่?')) {
        document.querySelectorAll('.count-input').forEach(input => {
          const balance = parseFloat(input.getAttribute('data-balance')) || 0;
          input.value = balance;
        });
        updateCountSummary();
      }
    }

    // ฟังก์ชั่นอัพเดตสรุปการนับ
    function updateCountSummary() {
      const totalItems = document.querySelectorAll('.count-input').length;
      let countedItems = 0;
      
      document.querySelectorAll('.count-input').forEach(input => {
        if (input.value.trim() !== '') {
          countedItems++;
        }
      });
      
      document.getElementById('total-items').textContent = totalItems;
      document.getElementById('counted-items').textContent = countedItems;
      document.getElementById('remaining-items').textContent = totalItems - countedItems;
    }

    // Event listener สำหรับการกด Enter ในช่องสแกนบาร์โค้ด
    document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addQuantity();
      }
    });

    // Event listener สำหรับการเปลี่ยนแปลงใน input จำนวน
    document.querySelectorAll('.count-input').forEach(input => {
      input.addEventListener('change', updateCountSummary);
    });

    // อัพเดตสรุปเมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function() {
      updateCountSummary();
      
      // เพิ่มการเปรียบเทียบจำนวนที่นับกับข้อมูลคงเหลือ
      document.querySelectorAll('.count-input').forEach(input => {
        input.addEventListener('change', function() {
          const balance = parseFloat(this.getAttribute('data-balance')) || 0;
          const counted = parseFloat(this.value) || 0;
          
          // ไฮไลท์ความแตกต่าง
          if (counted !== 0 && counted !== balance) {
            if (counted > balance) {
              this.classList.add('bg-yellow-100');
              this.classList.remove('bg-red-100');
            } else if (counted < balance) {
              this.classList.add('bg-red-100');
              this.classList.remove('bg-yellow-100');
            }
          } else {
            this.classList.remove('bg-yellow-100', 'bg-red-100');
          }
        });
      });
    });

    // ฟังก์ชั่นสำหรับการค้นหาอัตโนมัติ
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
      }
    });
  </script>
</body>
</html>