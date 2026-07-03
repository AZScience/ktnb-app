<?php

namespace App\Services;

use App\Models\Lecturer;
use App\Support\HttpSslConfigurator;
use Google\Client as GoogleClient;

class LecturerPortalAuthService
{
    public const SESSION_KEY = 'lecturer_portal.google';

    public function __construct(private SystemParameterService $parameters) {}

    public function clientId(): ?string
    {
        foreach ([
            trim((string) $this->parameters->get('googleClientId', '')),
            trim((string) config('services.google.client_id', '')),
        ] as $id) {
            if ($id !== '') {
                return $id;
            }
        }

        return null;
    }

    /** @return array{email: string, name: string, picture: string|null, sub: string}|null */
    public function currentUser(): ?array
    {
        $user = session(self::SESSION_KEY);

        return is_array($user) && ! empty($user['email']) ? $user : null;
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }

    /** @return array{email: string, name: string, picture: string|null, sub: string} */
    public function requireUser(): array
    {
        $user = $this->currentUser();
        if (! $user) {
            abort(401, 'Vui lòng đăng nhập bằng tài khoản Google trước.');
        }

        return $user;
    }

    /** @return array{email: string, name: string, picture: string|null, sub: string} */
    public function verifyIdToken(string $credential): array
    {
        $clientId = $this->clientId();
        if (! $clientId) {
            throw new \RuntimeException('Chưa cấu hình Google Client ID. Quản trị viên vào Tham số hệ thống → Cổng Giảng viên.');
        }

        $client = new GoogleClient(['client_id' => $clientId]);
        $client->setHttpClient(HttpSslConfigurator::guzzleClient());
        $payload = $client->verifyIdToken($credential);

        if (! is_array($payload) || empty($payload['email'])) {
            throw new \RuntimeException('Token Google không hợp lệ hoặc đã hết hạn.');
        }

        if (($payload['email_verified'] ?? false) !== true) {
            throw new \RuntimeException('Email Google chưa được xác minh.');
        }

        $email = strtolower((string) $payload['email']);
        $this->assertAllowedEmail($email);

        return [
            'email' => $email,
            'name' => (string) ($payload['name'] ?? $payload['email']),
            'picture' => isset($payload['picture']) ? (string) $payload['picture'] : null,
            'sub' => (string) ($payload['sub'] ?? ''),
        ];
    }

    /** @param array{email: string, name: string, picture: string|null, sub: string} $user */
    public function login(array $user): void
    {
        session([self::SESSION_KEY => $user]);
    }

    public function logout(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function assertAllowedEmail(string $email): void
    {
        $rawDomains = trim((string) $this->parameters->get('lecturerPortalEmailDomains', ''));
        if ($rawDomains === '') {
            $rawDomains = (string) config('nttu.lecturer_portal.email_domains', 'nttu.edu.vn');
        }

        $domains = array_filter(array_map(
            'trim',
            explode(',', $rawDomains),
        ));

        if ($domains === []) {
            return;
        }

        $host = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        if ($host !== '' && in_array($host, $domains, true)) {
            return;
        }

        if (Lecturer::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return;
        }

        throw new \RuntimeException(
            'Email Google không thuộc miền được phép ('.implode(', ', $domains).') hoặc chưa có trong danh sách giảng viên.',
        );
    }

    /** @return array<string, mixed> */
    public function publicPayload(?array $user = null): ?array
    {
        $user ??= $this->currentUser();
        if (! $user) {
            return null;
        }

        return [
            'email' => $user['email'],
            'name' => $user['name'],
            'picture' => $user['picture'] ?? null,
        ];
    }
}
