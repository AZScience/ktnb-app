<?php

namespace App\Models;

use App\Services\DiscussionParticipantService;
use Illuminate\Database\Eloquent\Model;

class DiscussionSection extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'title', 'student_content', 'author_email', 'author_name',
        'moderator_email', 'class_id', 'moderator_token_hash', 'comments',
    ];

    protected $casts = [
        'comments' => 'array',
    ];

    /** @return array<int, array<string, mixed>> */
    public function commentsList(): array
    {
        return DiscussionParticipantService::normalizeComments($this->comments);
    }

    public function toApiFormat(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'studentContent' => $this->student_content,
            'authorEmail' => $this->author_email,
            'authorName' => $this->author_name,
            'moderatorEmail' => $this->moderator_email,
            'classId' => $this->class_id,
            'comments' => $this->commentsList(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
