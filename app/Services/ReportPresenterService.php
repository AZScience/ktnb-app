<?php

namespace App\Services;

use App\Models\AssetReception;
use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\Petition;
use App\Models\ServiceRequest;
use App\Models\StudentViolation;
use Illuminate\Support\Collection;

class ReportPresenterService
{
    public function __construct(
        private ServiceRequestStatusService $serviceRequestStatus,
        private BuildingBlockOptionService $buildingBlocks,
    ) {}

    private function dash(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '---' : $value;
    }

    private function staff(?string $value): string
    {
        return $this->dash(Employee::nicknameFor($value));
    }

    private function boolMark(?bool $value): string
    {
        return $value ? '✓' : '';
    }

    /** @return list<array<string, mixed>> */
    public function comprehensiveRows(Collection $items): array
    {
        return $items->map(function (DailySchedule $item) {
            $lecturer = $this->dash($item->lecturer);
            $isExam = in_array($item->status, ['Phòng thi', 'Thi cuối kỳ'], true)
                || str_contains(strtolower((string) $item->content), 'thi');
            if ($isExam) {
                $proctors = array_values(array_filter([
                    $item->proctor1,
                    $item->proctor2,
                    $item->proctor3,
                ]));
                if ($proctors !== []) {
                    $lecturer = 'CBCT: '.implode(', ', $proctors);
                }
            }

            return [
                'id' => $item->id,
                'employee' => $this->dash($item->employee),
                'date' => $this->dash($item->date),
                'room' => $this->dash($item->room),
                'period' => $this->dash($item->period),
                'type' => $this->dash($item->type),
                'department' => $this->dash($item->department),
                'class' => $this->dash($item->class),
                'studentCount' => $this->dash((string) $item->student_count),
                'lecturer' => $lecturer,
                'content' => $this->dash($item->content),
                'incident' => $this->dash($item->incident),
                'incidentDetail' => $this->dash($item->incident_detail),
                '_building' => $this->dash($item->building),
                '_periodNum' => $this->parsePeriodStart($item->period),
                '_periodRaw' => $this->dash($item->period),
                '_lecturerRaw' => $this->dash($item->lecturer),
                '_proctors' => array_values(array_filter([
                    $item->proctor1,
                    $item->proctor2,
                    $item->proctor3,
                ])),
                '_recognition' => $this->dash(DailySchedule::resolveRecognitionName($item) ?? ''),
            ];
        })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    public function violationRows(Collection $items): array
    {
        return $items->map(fn (StudentViolation $item) => [
            'id' => $item->id,
            'fullName' => $this->dash($item->full_name),
            'class' => $this->dash($item->class),
            'studentId' => $this->dash($item->student_id),
            'violationDate' => $this->dash($item->violation_date),
            'violationType' => $this->dash($item->violation_type),
            'signature' => $item->signature_base64
                ? (str_starts_with($item->signature_base64, 'data:') ? $item->signature_base64 : 'data:image/png;base64,'.$item->signature_base64)
                : '',
            'signed' => $item->signed ? 'Đã ký' : 'Chưa ký',
            'officer' => $this->dash($item->officer),
            'note' => $this->dash($item->note),
            '_building' => $this->dash($item->building),
        ])->values()->all();
    }

    /** @return list<array<string, mixed>> */
    public function goodDeedsPropertyRows(Collection $items): array
    {
        return $items->map(function (AssetReception $item) {
            $recipient = Employee::nicknameFor($item->receiving_staff);
            $returner = Employee::nicknameFor($item->return_staff);
            $campus = $this->dash($this->buildingBlocks->reportBuildingLabel($item->building_block));

            return [
            'id' => $item->id,
            'code' => $this->dash($item->entry_number),
            'campus' => $campus,
            'receptionDate' => $this->dash($item->reception_date),
            'recipient' => $this->dash($recipient),
            'finderName' => $this->dash($item->giver_name),
            'finderId' => $this->dash($item->giver_id ?: $item->giver_phone),
            'finderDept' => $this->dash($item->giver_unit),
            'property' => $this->dash($item->content),
            'returnDate' => $this->dash($item->resolution_date),
            'returner' => $this->dash($returner),
            'ownerName' => $this->dash($item->receiver_name),
            'ownerId' => $this->dash($item->receiver_id),
            'ownerClass' => $this->dash($item->receiver_class),
            'ownerDept' => $this->dash($item->receiver_unit),
            'ownerPhone' => $this->dash($item->receiver_phone),
            '_building' => $campus,
            '_recipient' => $this->dash($recipient),
            ];
        })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    public function goodDeedsGratitudeRows(Collection $items): array
    {
        return $items
            ->filter(fn (AssetReception $item) => (bool) $item->is_gratitude)
            ->map(fn (AssetReception $item) => [
                'id' => $item->id,
                'receptionDate' => $this->dash($item->reception_date),
                'returnDate' => $this->dash($item->resolution_date),
                'appreciationCode' => $this->dash($item->gratitude_number),
                'appreciationCampus' => $this->dash($this->buildingBlocks->reportBuildingLabel($item->building_block)),
                'appreciationName' => $this->dash($item->giver_name),
                'appreciationRecDate' => $this->dash($item->reception_date),
                'appreciationGiveDate' => $this->dash($item->gratitude_date),
                'gift' => $this->dash($item->gratitude_gift),
                'appreciationId' => $this->dash($item->giver_id ?: $item->giver_phone),
                'appreciationDept' => $this->dash($item->giver_unit),
                'property' => $this->dash($item->content),
                'refCode' => $this->dash($item->entry_number),
                'note' => $this->dash($item->receiver_feedback ?: $item->note),
                '_building' => $this->dash($this->buildingBlocks->reportBuildingLabel($item->building_block)),
                '_recipient' => $this->staff($item->receiving_staff),
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function requestRows(Collection $items): array
    {
        return $items->map(function (ServiceRequest $item) {
            $recipient = Employee::nicknameFor($item->recipient);

            return [
                'id' => $item->id,
                'code' => $this->dash($item->ticket_number),
                'recipient' => $this->dash($recipient),
                'receptionDate' => $this->dash($item->reception_date),
                'building' => $this->dash($this->buildingBlocks->reportBuildingLabel($item->building_block)),
                'studentName' => $this->dash($item->student_name),
                'studentId' => $this->dash($item->student_id),
                'class' => $this->dash($item->class),
                'department' => $this->dash($item->department),
                'requestType' => $this->dash($item->request_type ?: 'Phiếu yêu cầu hỗ trợ'),
                'content' => $this->dash($item->content),
                'status' => $this->serviceRequestStatus->outcomeLabel($item),
                '_building' => $this->dash($this->buildingBlocks->reportBuildingLabel($item->building_block)),
                '_recipient' => $this->dash($recipient),
            ];
        })->values()->all();
    }

    /** @return list<array<string, mixed>> */
        public function incidentRecordsRows(Collection $records): array
    {
        return $records->map(function ($record) {
            return [
                '_rowId' => $record->id,
                'id' => 'BB' . str_pad($record->id, 5, '0', STR_PAD_LEFT),
                'incident_time' => $record->incident_time->format('d/m/Y H:i'),
                'location' => $record->location,
                'creator_name' => $record->creator_name,
                'witness_name' => $record->witness_name,
                'creator_signature' => $record->creator_signature,
                'witness_signature' => $record->witness_signature,
            ];
        })->values()->toArray();
    }

    public function petitionRows(Collection $items): array
    {
        return $items->map(function (Petition $item) {
            $details = $this->dash($item->summary);
            $followUp = trim((string) $item->resolution_follow_up);
            $recipient = Employee::nicknameFor($item->recipient);

            return [
                'id' => $item->id,
                'receptionDate' => $this->dash($item->reception_date),
                'senderName' => $this->dash($item->citizen_name),
                'senderId' => $this->dash($item->citizen_id),
                'address' => $this->dash($item->citizen_address),
                'phone' => $this->dash($item->citizen_phone),
                'content' => $this->dash($item->summary),
                'petitionType' => $this->dash($item->petition_type),
                'peopleCount' => $this->dash((string) $item->number_of_people),
                'previousResolver' => $this->dash($item->previous_authority),
                'accept' => $item->is_accepted ? $details : '',
                'returnAndGuide' => $item->is_returned ? $details : '',
                'transfer' => $item->is_forwarded ? $details : '',
                'result' => $followUp !== '' ? $followUp : 'Đang chờ xử lý',
                'note' => $this->dash($item->note),
                'recipient' => $this->dash($recipient),
                '_building' => $this->dash($item->building_block),
                '_recipient' => $this->dash($recipient),
            ];
        })->values()->all();
    }

    private function parsePeriodStart(?string $period): int
    {
        if (! $period) {
            return 0;
        }
        if (preg_match('/(\d+)/', $period, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
