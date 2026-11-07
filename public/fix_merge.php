<?php
require __DIR__.'/../vendor/autoload.php';

$file = __DIR__.'/../app/Services/ScheduleExcelService.php';
$content = file_get_contents($file);

// We need to rewrite mergeImportRows
$newMethod = <<<'METHOD'
    private function mergeImportRows(array $rawRows): array
    {
        $globalMergedMap = [];

        foreach ($rawRows as $row) {
            $item = $this->rawRowToSchedule($row);
            $status = (string) ($item['status'] ?? 'Phòng học');
            $lecturer = (string) ($item['lecturer'] ?? '');

            if ($status === 'Phòng thi') {
                $key = $this->normalizeImportKey(implode('', [
                    $item['date'], $item['building'], $item['room'], $item['period'], $item['type'],
                    $item['department'], $item['content'],
                ]));
            } else {
                $baseKey = $this->normalizeImportKey(implode('', [
                    $item['date'], $item['building'], $item['room'], $item['period'], $item['type'],
                    $item['department'], $item['class'], (string) ($item['student_count'] ?? ''), $item['content'],
                ]));
                $key = $baseKey.$this->normalizeImportKey($lecturer);
            }

            if (isset($globalMergedMap[$key])) {
                $existing = &$globalMergedMap[$key];
                if ($item['content'] && ! str_contains((string) $existing['content'], (string) $item['content'])) {
                    $existing['content'] = trim(($existing['content'] ?? '').' / '.$item['content'], ' /');
                }

                if ($status === 'Phòng thi') {
                    $newClass = trim((string) ($item['class'] ?? ''));
                    if ($newClass) {
                        $existingClasses = array_map('trim', explode(',', $existing['class'] ?? ''));
                        if (! in_array($newClass, $existingClasses, true)) {
                            $existing['class'] = trim(($existing['class'] ?? '') . ', ' . $newClass, ', ');
                        }
                    }
                    
                    if (isset($item['student_count']) && is_numeric($item['student_count'])) {
                        $existing['student_count'] = ((int) ($existing['student_count'] ?? 0)) + ((int) $item['student_count']);
                    }

                    $nextLec = $lecturer;
                    if ($nextLec && ! in_array($nextLec, [$existing['proctor1'], $existing['proctor2'], $existing['proctor3']], true)) {
                        if (empty($existing['proctor1'])) {
                            $existing['proctor1'] = $nextLec;
                        } elseif (empty($existing['proctor2'])) {
                            $existing['proctor2'] = $nextLec;
                        } elseif (empty($existing['proctor3'])) {
                            $existing['proctor3'] = $nextLec;
                        }
                    }
                    $existing['lecturer'] = '';
                } elseif ($lecturer && ! str_contains((string) $existing['lecturer'], $lecturer)) {
                    $existing['lecturer'] = trim(($existing['lecturer'] ?? '').', '.$lecturer, ', ');
                }
            } elseif ($status === 'Phòng thi') {
                $globalMergedMap[$key] = array_merge($item, [
                    'proctor1' => $lecturer ?: ($item['proctor1'] ?? ''),
                    'proctor2' => $item['proctor2'] ?? '',
                    'proctor3' => $item['proctor3'] ?? '',
                    'lecturer' => '',
                ]);
            } else {
                $globalMergedMap[$key] = $item;
            }
        }

        return array_values($globalMergedMap);
    }
METHOD;

$content = preg_replace('/private function mergeImportRows\(array \$rawRows\): array\s*\{.*?\n    \}/s', $newMethod, $content);
@unlink($file);
@file_put_contents($file, $content);

echo "SUCCESS";
