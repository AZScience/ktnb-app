<?php

namespace App\Http\Controllers;

use App\Models\ShiftFeedback;
use App\Services\EvidenceStorageService;
use App\Services\GoogleSheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    private const PROOF_FILE_RULE = 'file|max:20480|mimes:jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp4,webm,mov,avi,mkv';

    public function __construct(
        private EvidenceStorageService $storage,
        private GoogleSheetService $sheets,
    ) {}

    public function index(): View
    {
        return view('feedback.index', [
            'email' => auth()->user()->email,
            'employeeName' => auth()->user()->name,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'employee_name' => 'required|string|max:255',
            'shift_date' => 'required|date',
            'proof_printed' => 'nullable|array',
            'proof_printed.*' => self::PROOF_FILE_RULE,
            'proof_online' => 'nullable|array',
            'proof_online.*' => self::PROOF_FILE_RULE,
            'proof_incident' => 'nullable|array',
            'proof_incident.*' => self::PROOF_FILE_RULE,
            'proof_facility' => 'nullable|array',
            'proof_facility.*' => self::PROOF_FILE_RULE,
        ]);

        $uploadGroup = function (?array $files) {
            if (! $files) {
                return null;
            }
            $urls = [];
            foreach ($files as $file) {
                $url = $this->sheets->uploadFeedbackFile($file);
                if ($url === null) {
                    foreach ($this->storage->parse($this->storage->storeFiles([$file])) as $stored) {
                        $url = $stored['url'];
                    }
                }
                if ($url) {
                    $urls[] = $url;
                }
            }

            return $urls ? implode("\n", $urls) : null;
        };

        $proofPrinted = $uploadGroup($request->file('proof_printed'));
        $proofOnline = $uploadGroup($request->file('proof_online'));
        $proofIncident = $uploadGroup($request->file('proof_incident'));
        $proofFacility = $uploadGroup($request->file('proof_facility'));

        ShiftFeedback::create([
            'id' => (string) Str::uuid(),
            'email' => $data['email'],
            'employee_name' => $data['employee_name'],
            'shift_date' => $data['shift_date'],
            'proof_printed' => $proofPrinted,
            'proof_online' => $proofOnline,
            'proof_incident' => $proofIncident,
            'proof_facility' => $proofFacility,
        ]);

        $sheetMessage = null;
        $sheetResult = $this->sheets->pushFeedbackRow([
            'timestamp' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'employeeName' => $data['employee_name'],
            'proofPrinted' => $proofPrinted ?: ' ',
            'proofOnline' => $proofOnline ?: ' ',
            'proofIncident' => $proofIncident ?: ' ',
            'proofFacility' => $proofFacility ?: ' ',
        ]);

        if (! empty($sheetResult['success'])) {
            $sheetMessage = $sheetResult['message'];
        } elseif (empty($sheetResult['skipped'])) {
            $sheetMessage = 'Lưu DB thành công nhưng Google Sheet: '.($sheetResult['message'] ?? 'lỗi không xác định');
        }

        $message = 'Đã gửi minh chứng ca trực thành công.';
        if ($sheetMessage) {
            $message .= ' '.$sheetMessage;
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'sheet' => $sheetResult]);
        }

        return redirect()->route('feedback.index')->with('success', $message);
    }
}
