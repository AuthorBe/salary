<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Helper PDF
 * Membungkus fungsionalitas Dompdf agar mudah dipanggil dari mana saja.
 */

/**
 * Stream/Download PDF dari HTML
 *
 * @param string $html Konten HTML lengkap
 * @param string $filename Nama file (tanpa .pdf di akhir)
 * @param string $paper Ukuran kertas (A4, letter, dll)
 * @param string $orientation Orientasi (portrait, landscape)
 */
function streamPdf(string $html, string $filename, string $paper = 'A4', string $orientation = 'portrait'): void
{
    // Naikkan limit memori & waktu eksekusi untuk rendering tabel/PDF berat
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', '300');

    // Sanitasi nama file agar tidak terjadi HTTP header injection atau error karakter khusus
    $cleanFilename = preg_replace('/[^\w\- .]/', '_', trim($filename));
    if (!$cleanFilename) {
        $cleanFilename = 'dokumen_' . date('Ymd_His');
    }

    // Konfigurasi Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Memungkinkan load gambar dari URL
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new Dompdf($options);
    
    // Load HTML
    $dompdf->loadHtml($html);
    
    // Set paper size & orientation
    $dompdf->setPaper($paper, $orientation);
    
    // Render the HTML as PDF
    $dompdf->render();
    
    // Output the generated PDF to Browser
    $dompdf->stream($cleanFilename . '.pdf', [
        'Attachment' => true // true = force download, false = open in browser
    ]);
    exit;
}
