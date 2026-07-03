<?php

namespace App\Console\Commands;

use App\Models\AssetReception;
use App\Models\BuildingBlock;
use App\Models\Classroom;
use App\Models\DailySchedule;
use App\Models\Department;
use App\Models\Employee;
use App\Models\DocumentRecord;
use App\Models\DiscussionSection;
use App\Models\Exam;
use App\Models\ExternalCheckin;
use App\Models\Message;
use App\Models\Poll;
use App\Models\ShiftFeedback;
use App\Models\Gift;
use App\Models\IncidentCategory;
use App\Models\Lecturer;
use App\Models\OnlineCheckin;
use App\Models\Petition;
use App\Models\Position;
use App\Models\Recognition;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\Student;
use App\Models\StudentViolation;
use App\Models\SystemParameter;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportFirestoreCommand extends Command
{
    protected $signature = 'nttu:import-firestore {path : Thư mục hoặc file JSON đã export} {--only= : Chỉ import collection (phân cách dấu phẩy)}';

    protected $description = 'Import dữ liệu Firestore (JSON) vào database';

    /** Thứ tự import để tránh lỗi phụ thuộc dữ liệu. */
    private const IMPORT_ORDER = [
        'roles', 'positions', 'departments', 'document_types', 'document-types',
        'building-blocks', 'building_blocks', 'classrooms', 'lecturers', 'students',
        'gifts', 'employees', 'incident-categories', 'incident_categories',
        'recognitions', 'schedules', 'daily_schedules',
        'student-violations', 'requests', 'service_requests', 'petitions',
        'asset-receptions', 'asset_receptions', 'document_records',
        'external_checkins', 'online_checkins', 'online-checkins',
        'shift_feedbacks', 'polls', 'exams', 'discussion_sections',
        'messages', 'system-parameters', 'system_parameters', 'users',
    ];

    /** @var array<string, class-string> */
    private array $mappers = [
        'roles' => Role::class,
        'positions' => Position::class,
        'departments' => Department::class,
        'employees' => Employee::class,
        'lecturers' => Lecturer::class,
        'students' => Student::class,
        'building-blocks' => BuildingBlock::class,
        'classrooms' => Classroom::class,
        'gifts' => Gift::class,
        'recognitions' => Recognition::class,
        'incident-categories' => IncidentCategory::class,
        'incident_categories' => IncidentCategory::class,
        'document_types' => \App\Models\DocumentType::class,
        'document-types' => \App\Models\DocumentType::class,
        'schedules' => DailySchedule::class,
        'daily_schedules' => DailySchedule::class,
        'student-violations' => StudentViolation::class,
        'requests' => ServiceRequest::class,
        'service_requests' => ServiceRequest::class,
        'petitions' => Petition::class,
        'asset-receptions' => AssetReception::class,
        'online_checkins' => OnlineCheckin::class,
        'online-checkins' => OnlineCheckin::class,
        'document_records' => DocumentRecord::class,
        'document-records' => DocumentRecord::class,
        'external_checkins' => ExternalCheckin::class,
        'shift_feedbacks' => ShiftFeedback::class,
        'polls' => Poll::class,
        'exams' => Exam::class,
        'discussion_sections' => DiscussionSection::class,
        'activity-logs' => null, // skip — dùng activity log Laravel
        'online_presence' => null,
        'visits' => null,
        'messages' => Message::class,
        'asset_receptions' => AssetReception::class,
        'building_blocks' => BuildingBlock::class,
        'daily_schedules' => DailySchedule::class,
        'service_requests' => ServiceRequest::class,
        'system-parameters' => SystemParameter::class,
        'system_parameters' => SystemParameter::class,
        'users' => User::class,
    ];

    /** @var array<string, int|string|null>|null */
    private ?array $employeeUserIdByLegacyId = null;

    public function handle(): int
    {
        $path = $this->argument('path');
        if (File::isFile($path)) {
            return $this->importMonolith($path) ? self::SUCCESS : self::FAILURE;
        }

        if (! File::isDirectory($path)) {
            $this->error("Không tìm thấy: {$path}");

            return self::FAILURE;
        }

        $files = $this->orderedJsonFiles($path);
        if ($files === []) {
            $this->error('Không có file .json nào trong thư mục.');

            return self::FAILURE;
        }

        foreach ($files as $file) {
            $collection = pathinfo($file, PATHINFO_FILENAME);
            if ($this->shouldSkipCollection($collection)) {
                continue;
            }
            $this->importFile($collection, $file);
        }

        $this->info('Import hoàn tất.');

        return self::SUCCESS;
    }

    private function shouldSkipCollection(string $collection): bool
    {
        $only = trim((string) $this->option('only'));
        if ($only === '') {
            return false;
        }

        $allowed = array_map('trim', explode(',', $only));

        return ! in_array($collection, $allowed, true);
    }

    /** @return list<string> */
    private function orderedJsonFiles(string $directory): array
    {
        $all = File::glob($directory.'/*.json') ?: [];
        $byName = [];
        foreach ($all as $file) {
            $byName[pathinfo($file, PATHINFO_FILENAME)] = $file;
        }

        $ordered = [];
        foreach (self::IMPORT_ORDER as $name) {
            if (isset($byName[$name])) {
                $ordered[] = $byName[$name];
                unset($byName[$name]);
            }
        }

        foreach ($byName as $file) {
            $ordered[] = $file;
        }

        return $ordered;
    }

    private function importMonolith(string $file): bool
    {
        $raw = json_decode(File::get($file), true);
        if (! is_array($raw)) {
            $this->error('File JSON không hợp lệ.');

            return false;
        }

        $keys = array_keys($raw);
        $isMonolith = $keys !== [] && $keys !== range(0, count($raw) - 1)
            && ! isset($raw[0]);

        if (! $isMonolith) {
            $collection = pathinfo($file, PATHINFO_FILENAME);
            $this->importFile($collection, $file);
            $this->info('Import hoàn tất.');

            return true;
        }

        $names = self::IMPORT_ORDER;
        foreach (array_keys($raw) as $collection) {
            if (! in_array($collection, $names, true)) {
                $names[] = $collection;
            }
        }

        foreach ($names as $collection) {
            if (! isset($raw[$collection]) || $this->shouldSkipCollection($collection)) {
                continue;
            }
            $temp = storage_path('app/import-temp-'.Str::uuid().'.json');
            File::put($temp, json_encode($raw[$collection], JSON_UNESCAPED_UNICODE));
            $this->importFile($collection, $temp);
            File::delete($temp);
        }

        $this->info('Import hoàn tất.');

        return true;
    }

    private function importFile(string $collection, string $file): void
    {
        $raw = json_decode(File::get($file), true);
        if (! is_array($raw)) {
            $this->warn("Bỏ qua {$collection}: JSON không hợp lệ");

            return;
        }

        $rows = $this->parseRows($raw);
        $modelClass = $this->mappers[$collection] ?? null;

        if (! $modelClass) {
            $this->warn("Bỏ qua {$collection}: chưa có mapper");

            return;
        }

        $count = 0;
        if ($collection === 'messages') {
            $this->ensureEmployeeUserIdMap();
            $batch = [];

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $mapped = $this->mapRow($collection, $row);
                if ($mapped === null) {
                    continue;
                }
                $mapped['id'] = $mapped['id'] ?? (string) Str::uuid();
                $batch[] = $mapped;
                if (count($batch) >= 500) {
                    $this->flushImportBatch(Message::class, $batch);
                    $count += count($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $this->flushImportBatch(Message::class, $batch);
                $count += count($batch);
            }

            $this->line("✓ {$collection}: {$count} bản ghi");

            return;
        }

        $employeeIdMap = $collection === 'employees'
            ? $modelClass::query()->pluck('id', 'employee_id')->all()
            : [];
        $batch = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $mapped = $this->mapRow($collection, $row);
            if ($mapped === null) {
                continue;
            }
            if ($collection === 'employees' && ! empty($mapped['employee_id'])) {
                $existingId = $employeeIdMap[$mapped['employee_id']] ?? null;
                if ($existingId) {
                    $mapped['id'] = $existingId;
                }
            }
            $mapped['id'] = $mapped['id'] ?? (string) Str::uuid();
            if ($collection === 'employees' && ! empty($mapped['employee_id'])) {
                $employeeIdMap[$mapped['employee_id']] = $mapped['id'];
            }
            $batch[] = $mapped;
            if (count($batch) >= 500) {
                $this->flushImportBatch($modelClass, $batch);
                $count += count($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->flushImportBatch($modelClass, $batch);
            $count += count($batch);
        }

        $this->line("✓ {$collection}: {$count} bản ghi");
    }

    /** @param  list<array<string, mixed>>  $batch */
    private function flushImportBatch(string $modelClass, array $batch): void
    {
        if ($batch === []) {
            return;
        }

        $batch = array_map(fn (array $row) => $this->prepareUpsertRow($modelClass, $row), $batch);
        $updateColumns = array_values(array_diff(array_keys($batch[0]), ['id', 'created_at']));
        $modelClass::upsert($batch, ['id'], $updateColumns);
    }

    private function ensureEmployeeUserIdMap(): void
    {
        if ($this->employeeUserIdByLegacyId !== null) {
            return;
        }

        $this->employeeUserIdByLegacyId = Employee::query()
            ->pluck('user_id', 'id')
            ->all();
    }

    /** @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function prepareUpsertRow(string $modelClass, array $row): array
    {
        if ($modelClass === Message::class) {
            foreach (['recipient_user_ids', 'recipient_legacy_ids', 'attachments', 'trash_by_user_ids'] as $key) {
                if (isset($row[$key]) && is_array($row[$key])) {
                    $row[$key] = json_encode($row[$key], JSON_UNESCAPED_UNICODE);
                }
            }

            if (isset($row['sent_at']) && $row['sent_at'] instanceof \DateTimeInterface) {
                $row['sent_at'] = $row['sent_at']->format('Y-m-d H:i:s');
            }
        }

        return $row;
    }

    private function parseRows(array $raw): array
    {
        if (isset($raw[0]) && is_array($raw[0])) {
            return $raw;
        }

        $keys = array_keys($raw);
        $isDocMap = $keys !== [] && $keys !== range(0, count($raw) - 1);

        if ($isDocMap) {
            $rows = [];
            foreach ($raw as $id => $data) {
                if (! is_array($data)) {
                    continue;
                }
                $rows[] = array_merge($this->normalizeFirestore($data), ['id' => (string) $id]);
            }

            return $rows;
        }

        return array_values($raw);
    }

    private function normalizeFirestore(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (($value['type'] ?? null) === 'firestore/timestamp/1.0' && isset($value['seconds'])) {
            return date('c', (int) $value['seconds']);
        }

        if ($this->isList($value)) {
            return array_map(fn ($v) => $this->normalizeFirestore($v), $value);
        }

        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = $this->normalizeFirestore($v);
        }

        return $out;
    }

    private function mapRow(string $collection, array $row): ?array
    {
        $row = $this->normalizeFirestore($row);
        $row = $this->snakeKeys($row);

        if (empty($row['id'])) {
            $row['id'] = (string) Str::uuid();
        }

        return match ($collection) {
            'schedules', 'daily_schedules' => [
                'id' => $row['id'],
                'date' => $row['date'] ?? null,
                'building' => $row['building'] ?? null,
                'room' => $row['room'] ?? null,
                'period' => $row['period'] ?? null,
                'type' => $row['type'] ?? null,
                'department' => $row['department'] ?? null,
                'class' => $row['class'] ?? null,
                'student_count' => isset($row['student_count']) ? (string) $row['student_count'] : null,
                'lecturer' => $row['lecturer'] ?? null,
                'content' => $row['content'] ?? null,
                'status' => $row['status'] ?? null,
                'employee' => $row['employee'] ?? null,
                'recognition_date' => $row['recognition_date'] ?? null,
                'attending_students' => $row['attending_students'] ?? null,
                'incident' => $row['incident'] ?? null,
                'incident_detail' => $row['incident_detail'] ?? null,
                'evidence' => $row['evidence'] ?? null,
                'note' => $row['note'] ?? null,
                // Exam proctors
                'proctor1' => $row['proctor1'] ?? null,
                'proctor2' => $row['proctor2'] ?? null,
                'proctor3' => $row['proctor3'] ?? null,
                'is_notification' => (bool) ($row['is_notification'] ?? false),
            ],
            'student-violations' => [
                'id' => $row['id'],
                'full_name' => $row['full_name'] ?? 'N/A',
                'class' => $row['class'] ?? null,
                'student_id' => $row['student_id'] ?? null,
                'violation_date' => $row['violation_date'] ?? null,
                'violation_type' => $row['violation_type'] ?? null,
                'building' => $row['building'] ?? null,
                'department' => $row['department'] ?? null,
                'signed' => $row['signed'] ?? null,
                'officer' => $row['officer'] ?? null,
                'note' => $row['note'] ?? null,
                'signature_base64' => $row['signature_base64'] ?? null,
                'portrait_photo' => $row['portrait_photo'] ?? null,
                'document_photo' => $row['document_photo'] ?? null,
            ],
            'online_checkins', 'online-checkins' => [
                'id' => $row['id'] ?? (string) Str::uuid(),
                'payload' => $row,
                'server_timestamp' => now(),
            ],
            'document_records', 'document-records' => $this->mapDocumentRecord($row),
            'external_checkins' => [
                'id' => $row['id'],
                'class_id' => $row['class_id'] ?? null,
                'class_name' => $row['class_name'] ?? null,
                'class' => $row['class'] ?? null,
                'schedule_date' => $row['schedule_date'] ?? null,
                'lecturer' => $row['lecturer'] ?? null,
                'building' => $row['building'] ?? null,
                'room' => $row['room'] ?? null,
                'period' => $row['period'] ?? null,
                'student_count' => isset($row['student_count']) ? (string) $row['student_count'] : null,
                'actual_student_count' => isset($row['actual_student_count']) ? (string) $row['actual_student_count'] : null,
                'photo_urls' => $row['photo_urls'] ?? null,
                'location' => $row['location'] ?? null,
                'incident' => $row['incident'] ?? null,
                'incident_detail' => $row['incident_detail'] ?? null,
                'is_notification' => (bool) ($row['is_notification'] ?? false),
                'status' => $row['status'] ?? 'pending_review',
                'submitted_by' => $row['submitted_by'] ?? null,
            ],
            'polls' => [
                'id' => $row['id'],
                'question' => $row['question'] ?? '',
                'options' => $row['options'] ?? [],
                'duration' => (int) ($row['duration'] ?? 10),
                'attendance_list' => $row['attendance_list'] ?? [],
                'class_id' => $row['class_id'] ?? null,
                'lecturer' => $row['lecturer'] ?? null,
                'voters' => $row['voters'] ?? [],
                'status' => $row['status'] ?? 'active',
                'end_time' => isset($row['end_time']) ? $row['end_time'] : (isset($row['created_at']) ? now()->parse($row['created_at'])->addMinutes((int) ($row['duration'] ?? 10)) : now()->addHour()),
            ],
            'exams' => [
                'id' => $row['id'],
                'title' => $row['title'] ?? 'Bài kiểm tra',
                'class_id' => $row['class_id'] ?? null,
                'course_name' => $row['course_name'] ?? null,
                'type' => $row['type'] ?? null,
                'duration' => (int) ($row['duration'] ?? 15),
                'questions' => $row['questions'] ?? [],
                'source' => $row['source'] ?? null,
                'active' => (bool) ($row['active'] ?? true),
            ],
            'discussion_sections' => [
                'id' => $row['id'],
                'title' => $row['title'] ?? 'Thảo luận',
                'student_content' => $row['student_content'] ?? '',
                'author_email' => $row['author_email'] ?? '',
                'author_name' => $row['author_name'] ?? '',
                'comments' => $row['comments'] ?? [],
            ],
            'employees' => [
                'id'          => $row['id'],
                'employee_id' => $row['employee_id'] ?? null,
                'name'        => $row['name'] ?? null,
                'nickname'    => $row['nickname'] ?? null,
                'position'    => $row['position'] ?? null,
                'birth_date'  => $row['birth_date'] ?? null,
                'address'     => $row['address'] ?? null,
                'phone'       => $row['phone'] ?? null,
                // Firestore stores role as doc ID; map directly as role_id
                'role_id'     => $row['role_id'] ?? $row['role'] ?? null,
                'email'       => $row['email'] ?? null,
                'note'        => $row['note'] ?? null,
                'avatar_url'  => $row['avatar_url'] ?? null,
            ],
            'students' => [
                'id' => $row['id'],
                'name' => $row['name'] ?? '',
                'avatar_url' => $row['avatar_url'] ?? null,
                'gender' => in_array($row['gender'] ?? '', ['Nam', 'Nữ'], true) ? $row['gender'] : 'Nam',
                'birth_date' => $row['birth_date'] ?? null,
                'birth_place' => $row['birth_place'] ?? null,
                'hometown' => $row['hometown'] ?? null,
                'ethnicity' => $row['ethnicity'] ?? null,
                'religion' => $row['religion'] ?? null,
                'class' => $row['class'] ?? null,
                'major' => $row['major'] ?? null,
                'department' => $row['department'] ?? $row['major'] ?? null,
                'permanent_address' => $row['permanent_address'] ?? null,
                'temporary_address' => $row['temporary_address'] ?? null,
                'contact_address' => $row['contact_address'] ?? null,
                'region' => $row['region'] ?? null,
                'address' => $row['address'] ?? null,
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
                'citizen_id' => $row['citizen_id'] ?? null,
                'father_name' => $row['father_name'] ?? null,
                'father_occupation' => $row['father_occupation'] ?? null,
                'mother_name' => $row['mother_name'] ?? null,
                'mother_occupation' => $row['mother_occupation'] ?? null,
                'parent_phone' => $row['parent_phone'] ?? null,
                'note' => $row['note'] ?? null,
            ],
            'asset-receptions', 'asset_receptions' => [
                'id'               => $row['id'],
                'entry_number'     => $row['entry_number'] ?? null,
                'reception_date'   => $row['reception_date'] ?? null,
                'building_block'   => $row['building_block'] ?? null,
                'giver_name'       => $row['giver_name'] ?? null,
                'giver_id'         => $row['giver_id'] ?? null,
                'giver_class'      => $row['giver_class'] ?? null,
                'giver_unit'       => $row['giver_unit'] ?? null,
                'giver_phone'      => $row['giver_phone'] ?? null,
                'content'          => $row['content'] ?? null,
                'evidence'         => $row['evidence'] ?? null,
                'asset_state'      => $row['asset_state'] ?? null,
                'return_status'    => $row['return_status'] ?? 'Chưa trả',
                'is_gratitude'     => (bool) ($row['is_gratitude'] ?? false),
                'receiving_staff'  => $row['receiving_staff'] ?? null,
                'witness'          => $row['witness'] ?? null,
                'resolution_date'  => $row['resolution_date'] ?? null,
                'return_staff'     => $row['return_staff'] ?? null,
                'receiver_name'    => $row['receiver_name'] ?? null,
                'receiver_id'      => $row['receiver_id'] ?? null,
                'receiver_class'   => $row['receiver_class'] ?? null,
                'receiver_unit'    => $row['receiver_unit'] ?? null,
                'receiver_phone'   => $row['receiver_phone'] ?? null,
                'return_asset_state' => $row['return_asset_state'] ?? null,
                'receiver_feedback'  => $row['receiver_feedback'] ?? null,
                'return_witness'     => $row['return_witness'] ?? null,
                'gratitude_number'   => $row['gratitude_number'] ?? null,
                'gratitude_gift'     => $row['gratitude_gift'] ?? null,
                'gratitude_date'     => $row['gratitude_date'] ?? null,
                'gratitude_staff'    => $row['gratitude_staff'] ?? null,
                'gratitude_status'   => $row['gratitude_status'] ?? null,
                'note'               => $row['note'] ?? null,
            ],
            'requests', 'service_requests' => [
                'id'                      => $row['id'],
                'ticket_number'           => $row['ticket_number'] ?? null,
                'request_type'            => $row['request_type'] ?? null,
                'building_block'          => $row['building_block'] ?? null,
                'student_name'            => $row['student_name'] ?? null,
                'student_id'              => $row['student_id'] ?? null,
                'class'                   => $row['class'] ?? null,
                'department'              => $row['department'] ?? null,
                'phone'                   => $row['phone'] ?? null,
                'content'                 => $row['content'] ?? null,
                'request_date'            => $row['request_date'] ?? null,
                'reception_date'          => $row['reception_date'] ?? null,
                'recipient'               => $row['recipient'] ?? null,
                'is_processed_immediately' => (bool) ($row['is_processed_immediately'] ?? false),
                'appointment_date'        => $row['appointment_date'] ?? null,
                'resolution_date'         => $row['resolution_date'] ?? null,
                'resolver_name'           => $row['resolver_name'] ?? null,
                'feedback'                => $row['feedback'] ?? null,
                'status'                  => $row['status'] ?? null,
                'note'                    => $row['note'] ?? null,
            ],
            'messages' => $this->mapMessage($row),
            'petitions' => [
                'id'                  => $row['id'],
                'reception_date'      => $row['reception_date'] ?? null,
                'building_block'      => $row['building_block'] ?? null,
                'recipient'           => $row['recipient'] ?? null,
                'citizen_name'        => $row['citizen_name'] ?? null,
                'citizen_id'          => $row['citizen_id'] ?? null,
                'citizen_address'     => $row['citizen_address'] ?? null,
                'citizen_phone'       => $row['citizen_phone'] ?? null,
                'summary'             => $row['summary'] ?? null,
                'petition_type'       => $row['petition_type'] ?? null,
                'number_of_people'    => $row['number_of_people'] ?? null,
                'previous_authority'  => $row['previous_authority'] ?? null,
                'is_accepted'         => (bool) ($row['is_accepted'] ?? false),
                'is_returned'         => (bool) ($row['is_returned'] ?? false),
                'is_forwarded'        => (bool) ($row['is_forwarded'] ?? false),
                'resolution_follow_up' => $row['resolution_follow_up'] ?? null,
                'note'                => $row['note'] ?? null,
            ],
            default => $row,
        };
    }

    /** @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function mapMessage(array $row): ?array
    {
        $senderLegacy = $row['sender_id'] ?? null;
        $recipientLegacy = $row['recipient_ids'] ?? [];
        $trashLegacy = $row['trash_by'] ?? [];

        if (! is_array($recipientLegacy)) {
            $recipientLegacy = [];
        }
        if (! is_array($trashLegacy)) {
            $trashLegacy = [];
        }

        $senderUserId = $this->legacyEmployeeToUserId($senderLegacy);
        $recipientUserIds = $this->legacyEmployeeToUserIds($recipientLegacy);
        $trashUserIds = $this->legacyEmployeeToUserIds($trashLegacy);

        if (! $senderUserId && $recipientUserIds === []) {
            return null;
        }

        $sentAt = $row['timestamp'] ?? $row['sent_at'] ?? now();

        return [
            'id' => $row['id'],
            'sender_user_id' => $senderUserId,
            'sender_legacy_id' => $senderLegacy,
            'recipient_user_ids' => $recipientUserIds,
            'recipient_legacy_ids' => array_values($recipientLegacy),
            'subject' => $row['subject'] ?? '',
            'body' => $row['body'] ?? '',
            'attachments' => $row['attachments'] ?? [],
            'is_read' => (bool) ($row['is_read'] ?? false),
            'trash_by_user_ids' => $trashUserIds,
            'sent_at' => $sentAt,
        ];
    }

    private function legacyEmployeeToUserId(mixed $legacyId): ?int
    {
        if ($legacyId === null || $legacyId === '') {
            return null;
        }

        $this->ensureEmployeeUserIdMap();
        $userId = $this->employeeUserIdByLegacyId[(string) $legacyId] ?? null;

        return $userId ? (int) $userId : null;
    }

    /** @param  list<mixed>  $legacyIds
     * @return list<int>
     */
    private function legacyEmployeeToUserIds(array $legacyIds): array
    {
        return collect($legacyIds)
            ->map(fn ($id) => $this->legacyEmployeeToUserId($id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapDocumentRecord(array $row): array
    {
        $keywords = $row['keywords'] ?? null;
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            $keywords = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $keywords)));
        }

        return [
            'id' => $row['id'],
            'doc_code' => $row['doc_code'] ?? null,
            'doc_number' => $row['doc_number'] ?? null,
            'title' => $row['title'] ?? 'Không có tiêu đề',
            'abstract' => $row['abstract'] ?? null,
            'doc_type' => $row['doc_type'] ?? null,
            'issue_date' => $this->normalizeLegacyDate($row['issue_date'] ?? null),
            'received_date' => $this->normalizeLegacyDate($row['received_date'] ?? null),
            'issuing_body' => $row['issuing_body'] ?? null,
            'signer' => $row['signer'] ?? null,
            'department' => $row['department'] ?? null,
            'assignee' => $row['assignee'] ?? null,
            'urgency' => $row['urgency'] ?? 'Thường',
            'confidentiality' => $row['confidentiality'] ?? 'Thường',
            'status' => $row['status'] ?? 'Mới',
            'original_file' => $row['original_file'] ?? null,
            'extracted_text' => $row['extracted_text'] ?? null,
            'ai_summary' => $row['ai_summary'] ?? null,
            'keywords' => $keywords,
            'file_password' => $row['file_password'] ?? null,
        ];
    }

    private function normalizeLegacyDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string) $value);
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $text)) {
            return $text;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $text, $m)) {
            return sprintf('%s/%s/%s', $m[3], $m[2], $m[1]);
        }

        $ts = strtotime($text);

        return $ts ? date('d/m/Y', $ts) : $text;
    }

    private function snakeKeys(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $snake = Str::snake($key);
            if (is_array($value) && ! $this->isList($value)) {
                $value = $this->snakeKeys($value);
            }
            $out[$snake] = $value;
        }

        return $out;
    }

    private function isList(array $arr): bool
    {
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }
}
