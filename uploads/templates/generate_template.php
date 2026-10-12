<?php
// Tự động nạp thư viện PhpSpreadsheet (yêu cầu đã cài bằng Composer: composer require phpoffice/phpspreadsheet)
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('CauHoiMau');

// 1. Tạo Tiêu đề các Cột (Header)
$headers = [
    'A1' => 'Khối lớp (6-9)',
    'B1' => 'Nội dung câu hỏi',
    'C1' => 'Đáp án A',
    'D1' => 'Đáp án B',
    'E1' => 'Đáp án C',
    'F1' => 'Đáp án D',
    'G1' => 'Đáp án đúng (A/B/C/D)',
    'H1' => 'Lời giải chi tiết'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// 2. Dữ liệu Mẫu ví dụ (Dòng 2 và 3)
$sampleData = [
    [
        '9',
        'Cách mạng tháng Tám năm 1945 ở Việt Nam diễn ra vào thời gian nào?',
        'Tháng 8/1944',
        'Tháng 8/1945',
        'Tháng 9/1945',
        'Tháng 8/1946',
        'B',
        'Cách mạng tháng Tám thành công trọn vẹn trong tháng 8/1945.'
    ],
    [
        '8',
        'Sự kiện nào mở đầu cho Chiến tranh thế giới thứ hai?',
        'Đức tấn công Ba Lan (1/9/1939)',
        'Đức tấn công Liên Xô',
        'Nhật Bản tấn công Trân Châu Cảng',
        'Mỹ thả bom nguyên tử',
        'A',
        'Ngày 1/9/1939, Đức tấn công Ba Lan mở đầu Chiến tranh thế giới thứ hai.'
    ]
];

$row = 2;
foreach ($sampleData as $dataRow) {
    $col = 'A';
    foreach ($dataRow as $val) {
        $sheet->setCellValue($col . $row, $val);
        $col++;
    }
    $row++;
}

// 3. Định dạng Style (Định dạng Màu, Font chữ, Căn chỉnh)
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '27AE60']], // Màu xanh lá
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(28);

// Tự động căn chỉnh chiều rộng cột
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 4. Lưu File vào đường dẫn uploads/templates/Mau_Import_Cau_Hoi.xlsx
$dir = __DIR__ . '/../uploads/templates/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true); // Tự động tạo thư mục nếu chưa có
}

$filePath = $dir . 'Mau_Import_Cau_Hoi.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filePath);

echo "✅ Đã tạo thành công file mẫu tại: " . realpath($filePath);
?>