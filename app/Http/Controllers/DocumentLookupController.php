<?php

namespace App\Http\Controllers;

use App\Models\DocumentRecord;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use App\Services\DocumentRecordQueryService;
use App\Services\EvidenceStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentLookupController extends Controller
{
  /** @var array<int, string|null> */
  private static array $postedByCache = [];

  public function __construct(
    private DocumentRecordQueryService $query,
    private EvidenceStorageService $storage,
  ) {}

  public function index(): View
  {
    return view('monitoring.document-lookup.index', [
      'docTypes' => DocumentType::orderBy('name')->get(['id', 'name']),
    ]);
  }

  public function search(Request $request): JsonResponse
  {
    $items = $this->query->lookupSearch($request)
      ->map(fn (DocumentRecord $item) => $this->formatLookupItem($item));

    return response()->json(['items' => $items]);
  }

  public function show(DocumentRecord $document_record): JsonResponse
  {
    return response()->json(['item' => $this->formatLookupItem($document_record, true)]);
  }

  public function verifyPassword(Request $request, DocumentRecord $document_record): JsonResponse|RedirectResponse
  {
    $request->validate(['password' => 'required|string']);

    if ($document_record->file_password && $document_record->file_password !== $request->password) {
      if ($request->expectsJson()) {
        return response()->json(['message' => 'Mật khẩu không đúng.'], 422);
      }

      return back()->with('error', 'Mật khẩu không đúng.');
    }

    session(["doc_unlocked.{$document_record->id}" => true]);

    if ($request->expectsJson()) {
      return response()->json([
        'success' => true,
        'message' => 'Đã mở khóa tài liệu.',
        'item' => $this->formatLookupItem($document_record->fresh(), true),
      ]);
    }

    return back()->with('success', 'Đã mở khóa tệp văn bản.');
  }

  /** @return array<string, mixed> */
  private function formatLookupItem(DocumentRecord $item, bool $includeExtracted = false): array
  {
    $files = $this->storage->parse($item->original_file);
    $firstUrl = $files[0]['url'] ?? null;
    $firstName = $files[0]['name'] ?? null;
    $isConfidential = in_array($item->confidentiality, ['Mật', 'Tối mật'], true);
    $requiresPassword = $isConfidential && filled($item->file_password) && ! session("doc_unlocked.{$item->id}");

    $data = [
      'id' => $item->id,
      'doc_code' => $item->doc_code,
      'doc_number' => $item->doc_number,
      'title' => $item->title,
      'abstract' => $item->abstract,
      'doc_type' => $this->displayDocType($item->doc_type),
      'issue_date' => $item->issue_date,
      'received_date' => $item->received_date,
      'issuing_body' => $item->issuing_body,
      'signer' => $item->signer,
      'department' => $item->department,
      'assignee' => $item->assignee,
      'posted_by' => $this->resolvePostedBy($item),
      'confidentiality' => $item->confidentiality,
      'status' => $item->status,
      'original_file' => $firstUrl ?? $item->original_file,
      'file_name' => $firstName,
      'files' => $files,
      'requires_password' => $requiresPassword,
      'created_at_display' => $item->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
    ];

    if ($includeExtracted) {
      $data['extracted_text'] = $item->extracted_text;
      $data['ai_summary'] = $item->ai_summary;
    }

    return $data;
  }

  private function resolvePostedBy(DocumentRecord $item): ?string
  {
    $userId = $item->created_by_user_id;
    if (! $userId) {
      return null;
    }

    if (! array_key_exists($userId, self::$postedByCache)) {
      $employeeName = Employee::query()->where('user_id', $userId)->value('name');
      self::$postedByCache[$userId] = $employeeName
        ?: $item->createdByUser?->name
        ?: User::query()->whereKey($userId)->value('name');
    }

    return self::$postedByCache[$userId];
  }

  private function displayDocType(?string $docType): ?string
  {
    if ($docType === null || trim($docType) === '') {
      return null;
    }

    $trimmed = trim($docType);
    $clean = preg_replace('/\s*\([^)]+\)\s*$/', '', $trimmed) ?: $trimmed;

    if ($clean !== $trimmed) {
      return $clean;
    }

    $name = DocumentType::query()->whereKey($trimmed)->value('name');

    return $name ?: $clean;
  }
}
