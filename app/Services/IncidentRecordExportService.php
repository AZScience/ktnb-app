<?php

namespace App\Services;

use App\Models\IncidentRecord;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentRecordExportService
{
    public function download(IncidentRecord $record): StreamedResponse
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(13);

        $section = $phpWord->addSection([
            'marginLeft' => 1134, // 2cm
            'marginRight' => 1134,
            'marginTop' => 1134,
            'marginBottom' => 1134,
        ]);

        // M?u 18 header
        $section->addText('M?u 18. Biên b?n ghi nh?n s? vi?c', ['bold' => true, 'size' => 12]);
        
        $headerTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $headerTable->addRow();
        $headerTable->addCell(4500)->addText("TRU?NG ÐH NGUY?N T?T THÀNH\nPHÒNG KI?M TRA N?I B?", ['bold' => true], ['alignment' => Jc::CENTER]);
        $headerTable->addCell(5500)->addText("C?NG HÒA XÃ H?I CH? NGHIA VI?T NAM\nÐ?c l?p - T? do - H?nh phúc", ['bold' => true], ['alignment' => Jc::CENTER]);
        
        $section->addTextBreak(1);

        $section->addText('BIÊN B?N GHI NH?N S? VI?C', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);

        $incidentTime = Carbon::parse($record->incident_time);
        $section->addText(sprintf(
            "Vào lúc: %s gi? %s phút, ngày %s tháng %s nam %s, t?i %s",
            $incidentTime->format('H'),
            $incidentTime->format('i'),
            $incidentTime->format('d'),
            $incidentTime->format('m'),
            $incidentTime->format('Y'),
            $record->location
        ));

        $section->addText('Chúng tôi g?m:', ['bold' => true]);
        
        $participants = is_array($record->participants) ? $record->participants : [];
        for ($i = 0; $i < 4; $i++) {
            $name = $participants[$i]['name'] ?? '................................................';
            $role = $participants[$i]['role'] ?? '................................................';
            $section->addText(sprintf("%d. %s Ch?c v?: %s", $i + 1, str_pad($name, 40, '.'), $role));
        }

        $section->addText('N?i dung ghi nh?n:', ['bold' => true]);
        
        // Add content lines. In the actual form it's ruled lines. 
        // We will just print the text, optionally underlined.
        $lines = explode("\n", $record->content);
        foreach ($lines as $line) {
            $section->addText(trim($line), [], ['spaceAfter' => 120]);
        }
        
        // Fill remaining with dotted lines for looks
        for ($i = 0; $i < 5; $i++) {
            $section->addText('.........................................................................................................................................................');
        }

        $section->addTextBreak(1);

        $conclusionTime = Carbon::parse($record->conclusion_time);
        $section->addText(sprintf(
            "Biên b?n l?p xong lúc: %s gi? %s ngày %s tháng %s nam %s",
            $conclusionTime->format('H'),
            $conclusionTime->format('i'),
            $conclusionTime->format('d'),
            $conclusionTime->format('m'),
            $conclusionTime->format('Y')
        ));

        $section->addTextBreak(1);

        $signTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $signTable->addRow();
        $signTable->addCell(5000)->addText("NGU?I CH?NG KI?N\n(Ký và ghi rõ h? tên)", ['bold' => true], ['alignment' => Jc::CENTER]);
        $signTable->addCell(5000)->addText("NGU?I L?P BIÊN B?N\n(Ký và ghi rõ h? tên)", ['bold' => true], ['alignment' => Jc::CENTER]);
        
        $signTable->addRow();
        $signTable->addCell(5000)->addText("\n\n\n" . ($record->witness_name ?: ''), ['bold' => true], ['alignment' => Jc::CENTER]);
        $signTable->addCell(5000)->addText("\n\n\n" . ($record->creator_name ?: ''), ['bold' => true], ['alignment' => Jc::CENTER]);

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        
        $tempFile = tempnam(sys_get_temp_dir(), 'incident_record');
        $objWriter->save($tempFile);

        $filename = sprintf("BienBanSuViec_%s.docx", $incidentTime->format('Y_m_d_His'));

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
