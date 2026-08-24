<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDisplayDateColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRecord extends Model
{
    use NormalizesDisplayDateColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'doc_code', 'doc_number', 'title', 'abstract', 'doc_type',
        'issue_date', 'received_date', 'issuing_body', 'signer', 'department',
        'assignee', 'urgency', 'confidentiality', 'status', 'original_file',
        'extracted_text', 'ai_summary', 'keywords', 'file_password', 'created_by_user_id',
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    protected static function displayDateIsoColumns(): array
    {
        return [
            'issue_date' => 'issue_date_iso',
            'received_date' => 'received_date_iso',
        ];
    }

    /** Columns for lookup — includes file ref, excludes long extracted text. */
    public const LOOKUP_LIST_COLUMNS = [
        'id', 'doc_code', 'doc_number', 'title', 'abstract', 'doc_type',
        'issue_date', 'received_date', 'issuing_body', 'signer', 'department',
        'assignee', 'urgency', 'confidentiality', 'status', 'original_file', 'file_password',
        'created_by_user_id', 'created_at',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeForLookupList(Builder $query): Builder
    {
        return $query->select(self::LOOKUP_LIST_COLUMNS);
    }

    public static function urgencyOptions(): array
    {
        return ['Thường', 'Khẩn', 'Hỏa tốc'];
    }

    public static function confidentialityOptions(): array
    {
        return ['Thường', 'Mật', 'Tối mật'];
    }

    public static function statusOptions(): array
    {
        return ['Mới', 'Chờ duyệt', 'Đã duyệt', 'Cần bổ sung', 'Ban hành'];
    }
}
