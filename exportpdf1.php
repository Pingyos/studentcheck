<?php
require('fpdf186/fpdf.php');

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '...';
        $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '...';
        $startDateFormattedThai = $this->formatDateThai($startDate);
        $endDateFormattedThai = $this->formatDateThai($endDate);
        $this->AddFont('THSarabunBoldPSK', '', 'THSarabunBoldPSK.php');
        $this->SetFont('THSarabunBoldPSK', '', 14);
        $this->Cell(80);
        $this->Cell(0, 1, iconv('UTF-8', 'TIS-620', 'SAC - 1'), 0, 1, 'R');
        $this->Cell(80);
        $this->SetFont('THSarabunBoldPSK', '', 18);
        $this->Cell(30, 7, iconv('UTF-8', 'TIS-620', 'รายงานการขาดเรียน'), 0, 1, 'C');
        $this->Cell(80);
        $this->Cell(30, 7, iconv('UTF-8', 'TIS-620', 'ระหว่างวันที่: ' . $startDateFormattedThai . '  ถึงวันที่: ' . $endDateFormattedThai), 0, 0, 'C');
        $this->Ln(10);
    }

    function formatDateThai($date)
    {
        $dateTime = new DateTime($date);
        $thaiMonths = array(
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม',
            'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน',
            'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        );
        $formattedDateThai = $dateTime->format('d') . ' ' . $thaiMonths[$dateTime->format('m') - 1] . ' ' . ($dateTime->format('Y') + 543);
        return $formattedDateThai;
    }
    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->AddFont('THSarabunPSK', '', 'THSarabunPSK.php');
        $this->SetFont('THSarabunPSK', '', 12);

        $this->Cell(0, 10, iconv('UTF-8', 'TIS-620', 'หน้า ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->AddFont('THSarabunPSK', '', 'THSarabunPSK.php');
$pdf->AddFont('THSarabunBoldPSK', '', 'THSarabunBoldPSK.php');

$selectedCourse = isset($_GET['course']) ? $_GET['course'] : '';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');
$cause = isset($_GET['cause']) ? $_GET['cause'] : 'ขาดเรียน';

require_once 'connect.php';
$sql = "SELECT s.tb_student_tname, s.tb_student_name, s.tb_student_sname,s.tb_student_sex,s.tb_student_degree, c.absent, COUNT(c.absent) as count 
FROM ck_checking c
JOIN ck_students s ON c.absent = s.tb_student_code
WHERE 1=1 ";

if ($selectedCourse) {
    $sql .= " AND c.courses = :courseCode";
}

if ($startDate && $endDate) {
    $sql .= " AND DATE(c.time) BETWEEN :startDate AND :endDate";
}

if ($cause) {
    $sql .= " AND c.cause = :cause";
}

$sql .= " GROUP BY c.absent ORDER BY 
          s.tb_student_degree ASC, 
          s.tb_student_sex ASC, 
          c.absent ASC";

$stmt = $conn->prepare($sql);

if ($selectedCourse) {
    $stmt->bindParam(':courseCode', $selectedCourse);
}

if ($startDate && $endDate) {
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
}

if ($cause) {
    $stmt->bindParam(':cause', $cause);
}

$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$roomMapping = [
    1 => 'ม.1/1',
    2 => 'ม.1/2',
    3 => 'ม.1/3',
    4 => 'ม.2/1',
    5 => 'ม.2/2',
    6 => 'ม.2/3',
    7 => 'ม.3/1',
    8 => 'ม.3/2',
    9 => 'ม.3/3',
    10 => 'ม.4/1',
    11 => 'ม.4/2',
    12 => 'ม.4/3',
    13 => 'ม.5/1',
    14 => 'ม.5/2',
    15 => 'ม.5/3',
    16 => 'ม.6/1',
    17 => 'ม.6/2',
    18 => 'ม.6/3',
];
ob_start();

if (count($students) > 0) {
    require_once 'connect.php';
    if (isset($_GET['studentCode'])) {
        $studentCode = $_GET['studentCode'];
        $sqlStudent = "SELECT tb_student_tname, tb_student_name, tb_student_sname, tb_student_degree 
                       FROM ck_students 
                       WHERE tb_student_code = :studentCode
                       ORDER BY tb_student_sex ASC";
        $stmtStudent = $conn->prepare($sqlStudent);
        $stmtStudent->bindParam(':studentCode', $studentCode);
        $stmtStudent->execute();
        $studentData = $stmtStudent->fetch(PDO::FETCH_ASSOC);
    }

    $pdf->SetFont('THSarabunBoldPSK', '', 16);
    $pdf->Cell(10, 8, iconv('utf-8', 'cp874', 'ลำดับ'), 1, 0, 'C');
    $pdf->Cell(30, 8, iconv('utf-8', 'cp874', 'รหัสนักเรียน'), 1, 0, 'C');
    $pdf->Cell(100, 8, iconv('utf-8', 'cp874', 'ชื่อ-นามสกุล'), 1, 0, 'C');
    $pdf->Cell(25, 8, iconv('utf-8', 'cp874', 'ระดับชั้น'), 1, 0, 'C');
    $pdf->Cell(25, 8, iconv('utf-8', 'cp874', 'จำนวนคาบ'), 1, 1, 'C');

    $pdf->SetFont('THSarabunPSK', '', 16);
    $counter = 1;
    $processedStudents = array();
    $totalCount = 0;


    foreach ($students as $student) {
        if (!in_array($student['absent'], $processedStudents)) {
            $processedStudents[] = $student['absent'];
            $pdf->Cell(10, 8, iconv('utf-8', 'cp874', $counter), 1, 0, 'C');
            $pdf->Cell(30, 8, iconv('utf-8', 'cp874', $student['absent']), 1, 0, 'C');
            $pdf->Cell(100, 8, iconv('utf-8', 'cp874', $student['tb_student_tname'] . ' ' . $student['tb_student_name'] . ' ' . $student['tb_student_sname']), 1, 0, 'L');
            $pdf->Cell(25, 8, iconv('utf-8', 'cp874', $roomMapping[$student['tb_student_degree']]), 1, 0, 'C');
            $pdf->Cell(25, 8, $student['count'], 1, 1, 'C');
            $totalCount += $student['count'];

            $counter++;
        }
    }
} else {
    $pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'ไม่มีข้อมูลนักเรียนที่ขาด'), 0, 1, 'C');
}

$pdf->Cell(165, 8, iconv('utf-8', 'cp874', 'รวม' . ' '), 1, 0, 'R');
$pdf->Cell(25, 8, iconv('utf-8', 'cp874', '' . ' ' . $totalCount), 1, 0, 'C');
$pdf->Cell(0, 20, iconv('utf-8', 'cp874', ''), 0, 1, 'C');

$pdf->Cell(65, 8, iconv('utf-8', 'cp874', 'ลงชื่อ .................................................'), 0, 0, 'C');
$pdf->Cell(65, 8, iconv('utf-8', 'cp874', 'ลงชื่อ .................................................'), 0, 0, 'C');
$pdf->Cell(65, 8, iconv('utf-8', 'cp874', 'ลงชื่อ .................................................'), 0, 1, 'C');

$id = 2001;
$sql = "SELECT * FROM ck_users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$courseData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($courseData) {
    $name = $courseData['name'];
    $pdf->Cell(65, 7, iconv('utf-8', 'cp874', '(' . $name . ')'), 0, 0, 'C');
}
$id = 2002;
$sql = "SELECT * FROM ck_users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$courseData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($courseData) {
    $name = $courseData['name'];
    $pdf->Cell(65, 7, iconv('utf-8', 'cp874', '(' . $name . ')'), 0, 0, 'C');
}
$id = 2003;
$sql = "SELECT * FROM ck_users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$courseData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($courseData) {
    $name = $courseData['name'];
    $pdf->Cell(65, 7, iconv('utf-8', 'cp874', '(' . $name . ')'), 0, 1, 'C');
}

$pdf->Cell(65, 7, iconv('utf-8', 'cp874', 'ผู้ช่วยรองผู้อำนวยการฝ่ายวิชาการ'), 0, 0, 'C');
$pdf->Cell(65, 7, iconv('utf-8', 'cp874', 'รองผู้อำนวยการโรงเรียนถ้ำปินวิทยาคม'), 0, 0, 'C');
$pdf->Cell(0, 7, iconv('utf-8', 'cp874', 'ผู้อำนวยการโรงเรียนถ้ำปินวิทยาคม'), 0, 1, 'C');
ob_end_clean();
$filename = "report_" . date('Y-m-d') . ".pdf";

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"$filename\"");
$pdf->Output();
