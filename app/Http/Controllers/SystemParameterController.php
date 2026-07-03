<?php

namespace App\Http\Controllers;

use App\Services\ParameterVerificationService;
use App\Services\SystemParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemParameterController extends Controller
{
    public function __construct(
        private SystemParameterService $parameters,
        private ParameterVerificationService $verification,
    ) {}

    public function index(): View
    {
        return view('settings.parameters.index', [
            'params' => $this->parameters->all(false),
            'saveUrl' => route('parameters.bulk'),
            'verifyUrls' => [
                'googleSheet' => route('parameters.verify.google-sheet'),
                'summaryGoogleSheet' => route('parameters.verify.summary-google-sheet'),
                'evidence' => route('parameters.verify.evidence'),
                'ai' => route('parameters.verify.ai'),
                'email' => route('parameters.verify.email'),
                'lecturerPortal' => route('parameters.verify.lecturer-portal'),
            ],
            'lecturerPortalUrl' => route('lecturer-portal.index'),
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $payload = $request->validate([
                'params' => ['required', 'array'],
            ]);

            $this->parameters->updateMany($payload['params']);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật tham số hệ thống.',
            'params' => $this->parameters->all(false),
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Dữ liệu không hợp lệ.',
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi lưu trữ: '.$e->getMessage(),
            ], 500);
        }
    }

    public function verifyGoogleSheet(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->verification->verifyGoogleSheet(
            (string) ($data['googleSheetId'] ?? ''),
            (string) ($data['googleServiceAccountEmail'] ?? ''),
            (string) ($data['googlePrivateKey'] ?? ''),
            $data['faqSheetTabName'] ?? null,
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function verifySummaryGoogleSheet(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->verification->verifyGoogleSheet(
            (string) ($data['summaryReportGoogleSheetId'] ?? ''),
            (string) ($data['googleServiceAccountEmail'] ?? ''),
            (string) ($data['googlePrivateKey'] ?? ''),
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function verifyEvidence(Request $request): JsonResponse
    {
        $data = $request->all();

        $sheetId = trim((string) ($data['feedbackSheetId'] ?? '')) ?: trim((string) ($data['googleSheetId'] ?? ''));
        $sheetEmail = trim((string) ($data['evidenceServiceAccountEmail'] ?? '')) ?: trim((string) ($data['googleServiceAccountEmail'] ?? ''));
        $sheetKey = (string) ($data['evidencePrivateKey'] ?? '') ?: (string) ($data['googlePrivateKey'] ?? '');

        $result = $this->verification->verifyEvidence(
            $sheetId,
            $sheetEmail,
            $sheetKey,
            $data['feedbackTabName'] ?? null,
            (string) ($data['googleDriveFolderId'] ?? ''),
            $sheetEmail,
            $sheetKey,
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function verifyAi(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->verification->verifyAi(
            (string) ($data['aiApiKey'] ?? ''),
            (string) ($data['aiModel'] ?? 'gemini-1.5-flash'),
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->verification->verifySmtp(
            (string) ($data['smtpHost'] ?? ''),
            (string) ($data['smtpPort'] ?? '587'),
            (string) ($data['smtpUser'] ?? ''),
            (string) ($data['smtpPass'] ?? ''),
            (string) ($data['smtpFromName'] ?? 'Phòng Kiểm tra nội bộ'),
            true,
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function verifyLecturerPortal(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->verification->verifyLecturerPortal(
            (string) ($data['googleClientId'] ?? ''),
            (string) ($data['lecturerPortalEmailDomains'] ?? ''),
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
