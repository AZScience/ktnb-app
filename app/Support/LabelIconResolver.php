<?php

namespace App\Support;

class LabelIconResolver
{
    /** @return array{icon: string, tone: string} */
    public static function resolve(string $text, ?string $icon = null, ?string $tone = null): array
    {
        if ($icon !== null && $icon !== '') {
            return [
                'icon' => $icon,
                'tone' => $tone ?? 'primary',
            ];
        }

        return match (true) {
            str_contains($text, 'Email') || str_contains($text, 'email') => ['icon' => 'mail', 'tone' => 'blue'],
            str_contains($text, 'Mật khẩu') || str_contains($text, 'mật khẩu') => ['icon' => 'lock', 'tone' => 'orange'],
            str_contains($text, 'Từ ngày') => ['icon' => 'calendar', 'tone' => 'orange'],
            str_contains($text, 'Đến ngày') => ['icon' => 'calendar', 'tone' => 'orange'],
            str_contains($text, 'Ngày lọc') || (str_contains($text, 'Ngày') && ! str_contains($text, 'Sinh')) => ['icon' => 'calendar', 'tone' => 'orange'],
            str_contains($text, 'Ca trực') || str_contains($text, 'Ca học') => ['icon' => 'clock', 'tone' => 'amber'],
            str_contains($text, 'Từ tiết') || str_contains($text, 'Đến tiết') => ['icon' => 'hash', 'tone' => 'indigo'],
            str_contains($text, 'Họ tên') || str_contains($text, 'Họ và tên') || str_contains($text, 'Công dân') => ['icon' => 'user', 'tone' => 'blue'],
            str_contains($text, 'Cán bộ') => ['icon' => 'user-cog', 'tone' => 'blue'],
            str_contains($text, 'SV có mặt') || str_contains($text, 'Sĩ số') => ['icon' => 'users', 'tone' => 'blue'],
            str_contains($text, 'phát sinh') => ['icon' => 'bell', 'tone' => 'orange'],
            str_contains($text, 'GPS') || str_contains($text, 'Vị trí') => ['icon' => 'map-pin', 'tone' => 'rose'],
            str_contains($text, 'thông báo') => ['icon' => 'megaphone', 'tone' => 'amber'],
            str_contains($text, 'Mã lớp') => ['icon' => 'id-card', 'tone' => 'indigo'],
            str_contains($text, 'MSSV') || str_contains($text, 'Mã ') || str_contains($text, 'CCCD') => ['icon' => 'id-card', 'tone' => 'indigo'],
            str_contains($text, 'Môn') => ['icon' => 'book-user', 'tone' => 'teal'],
            str_contains($text, 'Điện thoại') || str_contains($text, 'SĐT') => ['icon' => 'phone', 'tone' => 'green'],
            str_contains($text, 'Địa chỉ') => ['icon' => 'map-pin', 'tone' => 'rose'],
            str_contains($text, 'Phòng') && ! str_contains($text, 'Kiểm tra') => ['icon' => 'door', 'tone' => 'yellow'],
            str_contains($text, 'Dãy') => ['icon' => 'building', 'tone' => 'amber'],
            str_contains($text, 'Đơn vị') || str_contains($text, 'Khoa') => ['icon' => 'landmark', 'tone' => 'rose'],
            str_contains($text, 'Chức vụ') => ['icon' => 'briefcase', 'tone' => 'amber'],
            str_contains($text, 'Vai trò') || str_contains($text, 'Tên vai trò') => ['icon' => 'shield', 'tone' => 'purple'],
            str_contains($text, 'Ghi chú') || str_contains($text, 'Nội dung') || str_contains($text, 'Tóm tắt') => ['icon' => 'note', 'tone' => 'teal'],
            str_contains($text, 'Trạng thái') => ['icon' => 'activity', 'tone' => 'cyan'],
            str_contains($text, 'Thao tác') || str_contains($text, 'Hành động') => ['icon' => 'activity', 'tone' => 'orange'],
            str_contains($text, 'Thời gian') => ['icon' => 'clock', 'tone' => 'gray'],
            str_contains($text, 'Người dùng') || str_contains($text, 'Bí danh') => ['icon' => 'user', 'tone' => 'blue'],
            str_contains($text, 'IP Address') || str_contains($text, 'IP') => ['icon' => 'hash', 'tone' => 'gray'],
            str_contains($text, 'Chức năng') || str_contains($text, 'Module') => ['icon' => 'folder', 'tone' => 'teal'],
            str_contains($text, 'Chi tiết') => ['icon' => 'note', 'tone' => 'teal'],
            str_contains($text, 'Tên mới') => ['icon' => 'edit', 'tone' => 'blue'],
            str_contains($text, 'Nhân viên') => ['icon' => 'users', 'tone' => 'fuchsia'],
            str_contains($text, 'Giảng viên') || str_contains($text, 'CBCT') => ['icon' => 'graduation', 'tone' => 'cyan'],
            str_contains($text, 'CB ghi nhận') => ['icon' => 'user-cog', 'tone' => 'teal'],
            str_contains($text, 'vi phạm') || str_contains($text, 'Vi phạm') => ['icon' => 'ban', 'tone' => 'rose'],
            str_contains($text, 'Loại') || str_contains($text, 'Tab') || str_contains($text, 'Sheet') => ['icon' => 'tag', 'tone' => 'primary'],
            str_contains($text, 'Tệp') || str_contains($text, 'File') || str_contains($text, 'minh chứng') => ['icon' => 'file-text', 'tone' => 'blue'],
            str_contains($text, 'Giới tính') => ['icon' => 'users', 'tone' => 'rose'],
            str_contains($text, 'Đường dẫn') || str_contains($text, 'hình ảnh') || str_contains($text, 'Hình nền') || str_contains($text, 'Logo') => ['icon' => 'upload', 'tone' => 'blue'],
            str_contains($text, 'Nguồn') => ['icon' => 'folder', 'tone' => 'cyan'],
            str_contains($text, 'Client ID') || str_contains($text, 'API Key') => ['icon' => 'key', 'tone' => 'amber'],
            str_contains($text, 'Website') => ['icon' => 'tag', 'tone' => 'sky'],
            default => ['icon' => 'tag', 'tone' => 'primary'],
        };
    }
}
