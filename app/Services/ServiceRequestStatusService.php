<?php

namespace App\Services;

use App\Models\ServiceRequest;

class ServiceRequestStatusService
{
    public function intakeCode(ServiceRequest $item): string
    {
        if ($item->is_processed_immediately) {
            return 'immediate';
        }

        if (trim((string) $item->appointment_date) !== '') {
            return 'appointment';
        }

        if (trim((string) $item->note) !== '') {
            return 'other';
        }

        return '';
    }

    public function intakeLabel(ServiceRequest $item): string
    {
        return match ($this->intakeCode($item)) {
            'immediate' => 'Hỗ trợ ngay',
            'appointment' => 'Hẹn ngày trả lời',
            'other' => 'Lý do khác',
            default => '---',
        };
    }

    public function isResolved(ServiceRequest $item): bool
    {
        if ($item->status === 'resolved') {
            return true;
        }

        if (trim((string) $item->resolution_date) !== '') {
            return true;
        }

        return trim((string) $item->feedback) !== '';
    }

    public function outcomeLabel(ServiceRequest $item): string
    {
        return $this->isResolved($item) ? 'Đã giải quyết' : 'Đang xử lý';
    }
}
