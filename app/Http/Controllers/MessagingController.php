<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\ActivityLogService;
use App\Services\EvidenceStorageService;
use App\Services\InternalMailNotificationService;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MessagingController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private EvidenceStorageService $storage,
        private MessagingService $messaging,
        private InternalMailNotificationService $mailNotify,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $folder = $request->get('folder', 'inbox');
        $employeeByUserId = $this->messaging->employeeByUserId();
        $positionNames = $this->messaging->positionNames();

        $messages = $this->messaging->messagesForUser($user, $folder)
            ->map(fn (Message $m) => $this->messaging->formatMessage($m, $employeeByUserId, $positionNames))
            ->values()
            ->all();

        $selected = null;
        if ($id = $request->get('id')) {
            $model = Message::with('sender')->find($id);
            if ($model) {
                $selected = $this->messaging->formatMessage($model, $employeeByUserId, $positionNames);
            }
        }

        return view('messaging.index', [
            'messages' => $messages,
            'folder' => $folder,
            'selected' => $selected,
            'recipients' => $this->messaging->composeRecipients((int) $user->id),
            'unreadCount' => $this->messaging->unreadInboxCount($user),
            'currentUserId' => (int) $user->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_user_ids' => 'required|array|min:1',
            'recipient_user_ids.*' => 'integer|exists:users,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,svg,bmp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,csv,zip,rar,7z,mp4,mov,avi,webm,mp3,wav,ogg,m4a',
            ],
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            $attachments = $this->storage->parse(
                $this->storage->storeFiles($request->file('attachments'))
            );
        }

        Message::create([
            'id' => (string) Str::uuid(),
            'sender_user_id' => $request->user()->id,
            'recipient_user_ids' => array_map('intval', $data['recipient_user_ids']),
            'subject' => $data['subject'],
            'body' => $data['body'],
            'attachments' => $attachments,
            'is_read' => false,
            'trash_by_user_ids' => [],
            'sent_at' => now(),
        ]);

        $this->mailNotify->sendMessageNotification(
            array_map('intval', $data['recipient_user_ids']),
            $data['subject'],
            $data['body'],
            $attachments,
        );

        $this->activityLog->log('Gửi tin nhắn', 'Message', $data['subject']);

        return redirect()->route('messaging.index', ['folder' => 'sent'])
            ->with('success', 'Đã gửi tin nhắn.');
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $employeeByUserId = $this->messaging->employeeByUserId();
        $positionNames = $this->messaging->positionNames();

        $items = $this->messaging->unreadNotificationsForUser($user)
            ->map(function (Message $message) use ($employeeByUserId, $positionNames) {
                $formatted = $this->messaging->formatMessage($message, $employeeByUserId, $positionNames);

                return [
                    'id' => $formatted['id'],
                    'subject' => $formatted['subject'],
                    'body_preview' => $formatted['body_preview'],
                    'sender_name' => $formatted['sender_name'],
                    'sender_avatar' => $formatted['sender_avatar'],
                    'sent_at' => $formatted['sent_at'],
                    'read_url' => route('messaging.read', ['message' => $message->id]),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'unread_count' => $this->messaging->unreadInboxCount($user),
            'items' => $items,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->messaging->messagesForUser($user, 'inbox')
            ->filter(fn (Message $message) => ! $message->is_read)
            ->each(fn (Message $message) => $message->update(['is_read' => true]));

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }

    public function read(Request $request, Message $message): RedirectResponse
    {
        if (in_array(auth()->id(), $message->recipient_user_ids ?? [], true) && ! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return redirect()->route('messaging.index', [
            'folder' => $request->get('folder', 'inbox'),
            'id' => $message->id,
        ]);
    }

    public function trash(Request $request, Message $message): RedirectResponse
    {
        $ids = $message->trash_by_user_ids ?? [];
        $userId = (int) auth()->id();
        if (! in_array($userId, $ids, true)) {
            $ids[] = $userId;
            $message->update(['trash_by_user_ids' => $ids]);
        }

        return redirect()->route('messaging.index', [
            'folder' => $request->get('folder', 'inbox'),
        ])->with('success', 'Đã chuyển vào thùng rác.');
    }

    public function restore(Message $message): RedirectResponse
    {
        $userId = (int) auth()->id();
        $ids = array_values(array_filter(
            $message->trash_by_user_ids ?? [],
            fn ($id) => (int) $id !== $userId,
        ));
        $message->update(['trash_by_user_ids' => $ids]);

        return redirect()->route('messaging.index', [
            'folder' => 'inbox',
            'id' => $message->id,
        ])->with('success', 'Đã khôi phục tin nhắn.');
    }

    public function destroy(Request $request, Message $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('messaging.index', [
            'folder' => $request->get('folder', 'trash'),
        ])->with('success', 'Đã xóa vĩnh viễn.');
    }
}
