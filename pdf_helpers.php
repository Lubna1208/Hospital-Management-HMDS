<?php

function pdf_prepare_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace("/[^\P{C}\n\t]/u", "", $text) ?? $text;

    if (function_exists("iconv")) {
        $converted = @iconv("UTF-8", "Windows-1252//TRANSLIT", $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }

    return str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);
}

function pdf_build_invoice(string $title, array $lines, bool $showPaidSeal = false): string
{
    $statusText = $showPaidSeal ? "Payment Status: PAID" : "Payment Status: PENDING";

    $content = "BT\n/F1 24 Tf\n50 780 Td\n(" . pdf_prepare_text($title) . ") Tj\n";
    $content .= "/F1 16 Tf\n0 -30 Td\n(" . pdf_prepare_text($statusText) . ") Tj\n";
    $content .= "/F1 12 Tf\n0 -24 Td\n(" . pdf_prepare_text(str_repeat("=", 40)) . ") Tj\n";

    if ($showPaidSeal) {
        $content .= "/F1 20 Tf\n0 -28 Td\n(" . pdf_prepare_text("PAID") . ") Tj\n";
        $content .= "/F1 12 Tf\n0 -30 Td\n";
    } else {
        $content .= "/F1 12 Tf\n0 -28 Td\n";
    }

    foreach ($lines as $index => $line) {
        if ($index > 0) {
            $content .= "0 -18 Td\n";
        }
        $content .= "(" . pdf_prepare_text($line) . ") Tj\n";
    }

    $content .= "ET";

    $objects = [];
    $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
    $objects[] = "2 0 obj << /Type /Pages /Count 1 /Kids [3 0 R] >> endobj";
    $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >> endobj";
    $objects[] = "4 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "\nendstream endobj";
    $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";

    $pdf = "%PDF-1.4\n";
    $offsets = [];

    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object . "\n";
    }

    $xrefPosition = strlen($pdf);
    $pdf .= "xref\n0 6\n";
    $pdf .= "0000000000 65535 f \n";

    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    $pdf .= "trailer << /Size 6 /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

    return $pdf;
}

function pdf_output_invoice(string $filename, string $title, array $lines, bool $showPaidSeal = false): void
{
    $pdf = pdf_build_invoice($title, $lines, $showPaidSeal);

    header("Content-Type: application/pdf");
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header("Content-Length: " . strlen($pdf));

    echo $pdf;
    exit;
}
