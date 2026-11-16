<?php

if (!defined('MEDAPP')) {
    http_response_code(403);
    exit;
}

class SimplePdf
{
    private $pageW = 595.28;
    private $pageH = 841.89;
    private $pages = [];
    private $buf = '';
    private $y = 800;
    private $pageNumber = 0;

    public function __construct()
    {
        $this->startPage();
    }

    public function text($size, $text, $bold = false, array $color = [0.102, 0.157, 0.141])
    {
        $size = (float) $size;
        $needed = $size + 8;
        if ($this->y - $needed < 48) {
            $this->startPage();
        }
        $this->drawText(36, $this->y - $size, $size, $text, $bold, $color);
        $this->y -= $needed;
    }

    public function gap($amount)
    {
        $this->y -= (float) $amount;
    }

    public function table(array $header, array $rows, array $widths)
    {
        $this->drawHeader($header, $widths);
        foreach ($rows as $row) {
            if ($this->y - 18 < 48) {
                $this->startPage();
                $this->drawHeader($header, $widths);
            }
            $this->drawRow($row, $widths, false, [1, 1, 1]);
        }
    }

    public function render()
    {
        $this->finishPage();
        $fonts = [
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $pageCount = count($this->pages);
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '';
        $objects[] = $fonts[0];
        $objects[] = $fonts[1];

        $kids = [];
        foreach ($this->pages as $content) {
            $pageObject = count($objects) + 1;
            $contentObject = $pageObject + 1;
            $kids[] = $pageObject . ' 0 R';
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
                . $this->num($this->pageW) . ' ' . $this->num($this->pageH)
                . '] /Contents ' . $contentObject . ' 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> >>';
            $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream';
        }
        $objects[1] = '<< /Type /Pages /Count ' . $pageCount . ' /Kids [' . implode(' ', $kids) . '] >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function startPage()
    {
        $this->finishPage();
        $this->pageNumber = count($this->pages) + 1;
        $this->buf = '';
        $this->fillRect(0, $this->pageH - 36, $this->pageW, 36, [0.122, 0.478, 0.302]);
        $this->drawText(36, $this->pageH - 23, 11, 'Digital Home Healthcare Ltd', true, [1, 1, 1]);
        $this->drawText(36, 24, 9, 'Medicine Reminder', false, [0.37, 0.43, 0.40]);
        $this->drawText($this->pageW - 90, 24, 9, 'Page ' . $this->pageNumber, false, [0.37, 0.43, 0.40]);
        $this->y = $this->pageH - 58;
    }

    private function finishPage()
    {
        if ($this->buf === '') {
            return;
        }
        if (substr($this->buf, -1) !== "\n") {
            $this->buf .= "\n";
        }
        $this->pages[] = $this->buf;
        $this->buf = '';
    }

    private function drawHeader(array $header, array $widths)
    {
        if ($this->y - 20 < 48) {
            $this->startPage();
        }
        $this->drawRow($header, $widths, true, [0.898, 0.957, 0.925]);
    }

    private function drawRow(array $cells, array $widths, $bold, array $fill)
    {
        $height = 18;
        $x = 36;
        $bottom = $this->y - $height;
        $total = 0;
        foreach ($widths as $width) {
            $total += $width;
        }
        $this->fillRect($x, $bottom, $total, $height, $fill);
        $this->buf .= "0.86 0.84 0.80 RG 0.4 w\n";
        $this->buf .= $this->num($x) . ' ' . $this->num($bottom) . ' ' . $this->num($total) . ' ' . $this->num($height) . " re S\n";
        $cursor = $x;
        foreach ($widths as $index => $width) {
            $text = isset($cells[$index]) ? $cells[$index] : '';
            $fitted = $this->fit($text, 8, $width - 8);
            $color = $bold ? [0.08, 0.32, 0.24] : [0.10, 0.16, 0.14];
            $this->drawText($cursor + 4, $bottom + 5, 8, $fitted, $bold, $color);
            $cursor += $width;
        }
        $this->y -= $height;
    }

    private function fillRect($x, $y, $w, $h, array $rgb)
    {
        $this->buf .= sprintf(
            "%.3f %.3f %.3f rg %s %s %s %s re f\n",
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $this->num($x),
            $this->num($y),
            $this->num($w),
            $this->num($h)
        );
    }

    private function drawText($x, $y, $size, $text, $bold, array $rgb)
    {
        $font = $bold ? 'F2' : 'F1';
        $this->buf .= sprintf(
            "BT /%s %s Tf %.3f %.3f %.3f rg 1 0 0 1 %s %s Tm (%s) Tj ET\n",
            $font,
            $this->num($size),
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $this->num($x),
            $this->num($y),
            $this->escape($text)
        );
    }

    private function fit($text, $size, $width)
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text));
        $max = (int) floor($width / ($size * 0.56));
        if ($max < 1) {
            return '';
        }
        if (strlen($text) <= $max) {
            return $text;
        }
        if ($max <= 3) {
            return substr($text, 0, $max);
        }
        return substr($text, 0, $max - 3) . '...';
    }

    private function escape($text)
    {
        $text = (string) $text;
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\xFF]/', '', $text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function num($value)
    {
        return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.');
    }
}

function build_report_pdf(array $lines, array $rows, $includePatient)
{
    $pdf = new SimplePdf();
    $pdf->text(16, 'Medication schedule and activity', true);
    foreach ($lines as $line) {
        $pdf->text(10, $line, false, [0.30, 0.38, 0.35]);
    }
    $pdf->gap(8);

    if ($includePatient) {
        $header = ['Patient', 'Medicine', 'Dosage', 'Date', 'Time', 'Amount', 'Status'];
        $widths = [78, 110, 70, 62, 58, 62, 55];
    } else {
        $header = ['Medicine', 'Dosage', 'Date', 'Time', 'Amount', 'Status'];
        $widths = [140, 90, 70, 62, 80, 70];
    }

    if (!$rows) {
        $pdf->text(11, 'No doses in this period.');
        return $pdf->render();
    }

    $body = [];
    foreach ($rows as $row) {
        $clock = format_clock($row['time']);
        $record = [
            $row['name'],
            $row['dosage'],
            $row['date'],
            $clock['label'],
            $row['quantity'],
            status_label($row['status']),
        ];
        if ($includePatient) {
            array_unshift($record, $row['patient']);
        }
        $body[] = $record;
    }
    $pdf->table($header, $body, $widths);
    return $pdf->render();
}

function build_report_csv(array $rows, $includePatient)
{
    $handle = fopen('php://temp', 'w+');
    fwrite($handle, "\xEF\xBB\xBF");
    $header = ['Medicine', 'Dosage', 'Form', 'Date', 'Time', 'Amount', 'Status', 'Logged at'];
    if ($includePatient) {
        array_unshift($header, 'Username');
        array_unshift($header, 'Patient');
    }
    fputcsv($handle, $header);
    foreach ($rows as $row) {
        $clock = format_clock($row['time']);
        $record = [
            csv_safe($row['name']),
            csv_safe($row['dosage']),
            csv_safe($row['form']),
            $row['date'],
            $clock['label'],
            csv_safe($row['quantity']),
            status_label($row['status']),
            $row['logged_at'] ? $row['logged_at'] : '',
        ];
        if ($includePatient) {
            array_unshift($record, csv_safe($row['username']));
            array_unshift($record, csv_safe($row['patient']));
        }
        fputcsv($handle, $record);
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);
    return $csv;
}

function csv_safe($value)
{
    $value = (string) $value;
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;
    }
    return $value;
}
