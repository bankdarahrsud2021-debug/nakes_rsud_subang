<?php

require_once "../config/auth.php";

$autoload = "../vendor/autoload.php";

if (!file_exists($autoload)) {
    die("PhpSpreadsheet belum terpasang.");
}

require_once $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Data Nakes");


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

$headers = [
    'NIK',
    'NIP',
    'Nama Lengkap',
    'Gelar Depan',
    'Gelar Belakang',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Jenis Kelamin',
    'No. HP',
    'Email',
    'Alamat',
    'Profesi',
    'Unit Kerja',
    'Jabatan',
    'Status Kepegawaian',
    'Tanggal Muali Kerja'
];


foreach ($headers as $col => $header) {

    $cell = $sheet->getCellByColumnAndRow(
        $col + 1,
        1
    );

    $cell->setValue($header);

}


/*
|--------------------------------------------------------------------------
| FORMAT KOLOM
|--------------------------------------------------------------------------
*/

$sheet->getColumnDimension('A')->setWidth(22);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(35);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(22);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(18);
$sheet->getColumnDimension('I')->setWidth(18);
$sheet->getColumnDimension('J')->setWidth(30);
$sheet->getColumnDimension('K')->setWidth(35);
$sheet->getColumnDimension('L')->setWidth(32);
$sheet->getColumnDimension('M')->setWidth(30);
$sheet->getColumnDimension('N')->setWidth(22);
$sheet->getColumnDimension('O')->setWidth(25);
$sheet->getColumnDimension('P')->setWidth(22);


/*
|--------------------------------------------------------------------------
| HEADER STYLE
|--------------------------------------------------------------------------
*/

$sheet->getStyle('A1:P1')->getFont()->setBold(true);

$sheet->freezePane('A2');


/*
|--------------------------------------------------------------------------
| DOWNLOAD
|--------------------------------------------------------------------------
*/

$filename = "template_import_nakes.xlsx";

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment;filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;