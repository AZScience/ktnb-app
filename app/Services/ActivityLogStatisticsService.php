<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ActivityLogStatisticsService
{
    private const CORE_ACTIONS = ['VIEW', 'CREATE', 'UPDATE', 'DELETE'];

    private const ACTION_LABELS = [
        'VIEW' => 'Xem',
        'CREATE' => 'Thêm',
        'UPDATE' => 'Sửa',
        'DELETE' => 'Xóa',
        'LOGIN' => 'Đăng nhập',
        'LOGOUT' => 'Đăng xuất',
    ];

    public function __construct(
        private ActivityLogMainFeatureResolver $features,
    ) {}

    /** @param  Collection<int, array<string, mixed>>  $logs */
    public function build(Collection $logs): array
    {
        $items = $logs->values()->all();
        $total = count($items);
        $mainFeatureStats = $this->buildMainFeatureStats($items, $total);

        return [
            'total' => $total,
            'overview' => $this->buildOverview($items, $total, $mainFeatureStats),
            'byModule' => $mainFeatureStats,
            'byMainFeature' => $mainFeatureStats,
            'byAction' => $this->buildByAction($items, $total),
            'byEmployee' => $this->buildByEmployee($items, $total),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{items: list<array<string, mixed>>, commentary: list<string>, recommendations: list<string>}
     */
    private function buildMainFeatureStats(array $items, int $total): array
    {
        $groups = $this->initFeatureGroups();

        foreach ($items as $item) {
            $feature = $this->features->resolve($item);
            $key = $feature['key'];
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'module' => $feature['label'],
                    'total' => 0,
                ] + array_fill_keys(self::CORE_ACTIONS, 0);
            }
            $action = (string) ($item['action'] ?? 'VIEW');
            $groups[$key]['total']++;
            if (in_array($action, self::CORE_ACTIONS, true)) {
                $groups[$key][$action]++;
            }
        }

        $rows = collect(ActivityLogMainFeatureResolver::FEATURE_ORDER)
            ->map(function (string $featureKey) use ($groups, $total) {
                $row = $groups[$featureKey];
                $row['percent'] = $total > 0 ? round($row['total'] / $total * 100, 1) : 0;

                return $row;
            })
            ->filter(fn (array $row) => $row['key'] !== ActivityLogMainFeatureResolver::OTHER_KEY || $row['total'] > 0)
            ->values()
            ->all();

        $mainRows = array_values(array_filter(
            $rows,
            fn (array $row) => $row['key'] !== ActivityLogMainFeatureResolver::OTHER_KEY,
        ));
        $otherRow = collect($rows)->firstWhere('key', ActivityLogMainFeatureResolver::OTHER_KEY);
        $mainTotal = array_sum(array_map(fn (array $row) => $row['total'], $mainRows));
        $activeCount = count(array_filter($mainRows, fn (array $row) => $row['total'] > 0));
        $inactive = collect($mainRows)->filter(fn (array $row) => $row['total'] === 0)->values()->all();
        $active = collect($mainRows)->filter(fn (array $row) => $row['total'] > 0)->sortByDesc('total')->values()->all();
        $top = $active[0] ?? null;

        $commentary = [];
        $recommendations = [];

        if ($total === 0) {
            $commentary[] = 'Trong phạm vi bộ lọc hiện tại, hệ thống chưa ghi nhận nhật ký truy cập nào. Điều này có thể do khoảng thời gian chọn quá hẹp, bộ lọc đang loại trừ toàn bộ dữ liệu, hoặc chưa phát sinh thao tác trong giai đoạn này.';
            $recommendations[] = 'Mở rộng khoảng thời gian (Từ ngày — Đến ngày) hoặc xóa bộ lọc cột/nâng cao, sau đó tải lại thống kê.';
            $recommendations[] = 'Kiểm tra xem nhân viên đã thực hiện thao tác tại các chức năng chính (Công cụ kiểm tra) trong khoảng thời gian cần báo cáo hay chưa.';
        } else {
            $commentary[] = sprintf(
                'Phạm vi phân tích gồm %s lượt ghi nhận. Trong 10 chức năng chính của Công cụ kiểm tra, có %s chức năng đã phát sinh hoạt động, tương ứng %s lượt (%s%% tổng).',
                number_format($total),
                $activeCount,
                number_format($mainTotal),
                $total > 0 ? round($mainTotal / $total * 100, 1) : 0,
            );

            if ($top) {
                $commentary[] = sprintf(
                    'Chức năng được sử dụng nhiều nhất là "%s" với %s lượt (%s%%), gồm Xem %s · Thêm %s · Sửa %s · Xóa %s lượt.',
                    $top['module'],
                    number_format($top['total']),
                    $top['percent'],
                    number_format($top['VIEW'] ?? 0),
                    number_format($top['CREATE'] ?? 0),
                    number_format($top['UPDATE'] ?? 0),
                    number_format($top['DELETE'] ?? 0),
                );
            }

            if (count($active) >= 2) {
                $commentary[] = 'Ba chức năng có mức sử dụng cao tiếp theo: '.$this->formatFeatureRanking(array_slice($active, 1, 3)).'.';
            }

            if ($inactive !== []) {
                $commentary[] = 'Các chức năng chưa phát sinh nhật ký trong phạm vi lọc: '.$this->formatFeatureNames($inactive).'. Cần xem xét đây là do chưa triển khai sử dụng, chưa có ca trực phụ trách, hay do nhật ký chưa được ghi nhận đầy đủ.';
            }

            if ($otherRow && ($otherRow['total'] ?? 0) > 0) {
                $commentary[] = sprintf(
                    'Ngoài 10 chức năng chính, còn %s lượt (%s%%) thuộc nhóm "%s" (đăng nhập, cài đặt, danh mục, hệ thống...).',
                    number_format($otherRow['total']),
                    $otherRow['percent'],
                    ActivityLogMainFeatureResolver::OTHER_LABEL,
                );
            }

            if ($inactive !== []) {
                $recommendations[] = 'Đối với các chức năng chưa có nhật ký ('.$this->formatFeatureNames($inactive).'): rà soát phân quyền truy cập, tổ chức đào tạo sử dụng, và kiểm tra quy trình ghi nhận thao tác tại từng module.';
            }

            if ($top && $top['percent'] >= 35) {
                $recommendations[] = sprintf(
                    'Mức tập trung cao tại "%s" (%s%%) — cân nhắc phân bổ ca trực, luân phiên nhân sự giám sát, và bổ sung hỗ trợ cho các chức năng ít được sử dụng hơn.',
                    $top['module'],
                    $top['percent'],
                );
            }

            foreach (collect($mainRows)->filter(fn (array $r) => ($r['DELETE'] ?? 0) >= 3)->sortByDesc('DELETE')->take(2) as $row) {
                $recommendations[] = sprintf(
                    '"%s" có %s thao tác Xóa — nên đối chiếu với quy trình phê duyệt, xác minh người thực hiện và lý do xóa trong chi tiết nhật ký.',
                    $row['module'],
                    $row['DELETE'],
                );
            }

            $lowInput = collect($mainRows)->filter(fn (array $r) => $r['total'] > 0 && ($r['CREATE'] ?? 0) + ($r['UPDATE'] ?? 0) === 0);
            if ($lowInput->isNotEmpty()) {
                $recommendations[] = 'Các chức năng chỉ có thao tác Xem, chưa có Thêm/Sửa: '.$this->formatFeatureNames($lowInput->all()).' — đánh giá xem nhân viên đã nhập liệu đúng quy trình hay chỉ tra cứu.';
            }

            if ($recommendations === []) {
                $recommendations[] = 'Phân bổ hoạt động giữa các chức năng chính tương đối cân bằng. Nên duy trì báo cáo định kỳ hàng tuần và so sánh với kỳ trước để phát hiện biến động bất thường.';
                $recommendations[] = 'Tiếp tục theo dõi các thao tác Xóa tại module giám sát và tiếp nhận để đảm bảo tuân thủ quy định nội bộ.';
            }
        }

        return [
            'items' => $rows,
            'commentary' => $commentary,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array{items: list<array<string, mixed>>}  $mainFeatureStats
     */
    private function buildOverview(array $items, int $total, array $mainFeatureStats): array
    {
        $counts = $this->actionCounts($items);
        $coreTotal = array_sum(array_map(fn (string $key) => $counts[$key] ?? 0, self::CORE_ACTIONS));

        $series = [];
        foreach (self::CORE_ACTIONS as $action) {
            $count = $counts[$action] ?? 0;
            $series[] = [
                'key' => $action,
                'label' => self::ACTION_LABELS[$action],
                'count' => $count,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }

        $mainFeatures = collect($mainFeatureStats['items'] ?? [])
            ->filter(fn (array $row) => $row['key'] !== ActivityLogMainFeatureResolver::OTHER_KEY)
            ->values()
            ->all();

        $commentary = [];
        $recommendations = [];

        if ($total === 0) {
            $commentary[] = 'Chưa có dữ liệu để đánh giá tổng quan. Vui lòng điều chỉnh bộ lọc hoặc chọn khoảng thời gian rộng hơn để hệ thống tổng hợp nhật ký truy cập.';
            $recommendations[] = 'Xóa bộ lọc nâng cao và bộ lọc cột, chọn khoảng 30 ngày gần nhất, rồi mở lại tab Thống kê tổng quan.';
        } else {
            $commentary[] = sprintf(
                'Tổng cộng %s lượt ghi nhận trong phạm vi lọc. Bốn nhóm thao tác nghiệp vụ chính (Xem, Thêm, Sửa, Xóa) chiếm %s lượt (%s%%); các thao tác khác (đăng nhập, đăng xuất...) chiếm phần còn lại.',
                number_format($total),
                number_format($coreTotal),
                $total > 0 ? round($coreTotal / $total * 100, 1) : 0,
            );

            $commentary[] = 'Cơ cấu hành động: '.$this->formatActionBreakdown($counts, $total).'.';

            $dominant = collect($series)->sortByDesc('count')->first();
            if ($dominant && ($dominant['count'] ?? 0) > 0) {
                $commentary[] = sprintf(
                    'Thao tác "%s" đang chiếm ưu thế với %s lượt (%s%%). %s',
                    $dominant['label'],
                    number_format($dominant['count']),
                    $dominant['percent'],
                    $this->interpretDominantAction($dominant['key'], $counts, $total),
                );
            }

            $activeFeatures = collect($mainFeatures)->filter(fn (array $r) => ($r['total'] ?? 0) > 0)->sortByDesc('total')->values();
            if ($activeFeatures->isNotEmpty()) {
                $commentary[] = 'Phân bổ theo chức năng chính: '.$this->formatFeatureRanking($activeFeatures->take(5)->all()).'.';
            }

            $viewCount = $counts['VIEW'] ?? 0;
            $mutateCount = ($counts['CREATE'] ?? 0) + ($counts['UPDATE'] ?? 0) + ($counts['DELETE'] ?? 0);
            if ($mutateCount > 0) {
                $ratio = round($viewCount / max(1, $mutateCount), 1);
                $commentary[] = sprintf(
                    'Tỷ lệ Xem so với thao tác thay đổi dữ liệu (Thêm + Sửa + Xóa) là %s : 1. %s',
                    number_format($ratio, 1),
                    $ratio >= 5
                        ? 'Người dùng chủ yếu tra cứu; cần đảm bảo báo cáo/tổng hợp đáp ứng nhu cầu tra cứu để giảm thao tác lặp.'
                        : ($ratio <= 1.5
                            ? 'Tỷ lệ nhập liệu/chỉnh sửa cao; cần chú trọng kiểm soát chất lượng dữ liệu và phê duyệt trước khi lưu.'
                            : 'Cân bằng hợp lý giữa tra cứu và cập nhật dữ liệu.'),
                );
            }

            $deletePct = $total > 0 ? round(($counts['DELETE'] ?? 0) / $total * 100, 1) : 0;
            if ($deletePct >= 10) {
                $recommendations[] = sprintf(
                    'Tỷ lệ thao tác Xóa đạt %s%% (%s lượt) — thiết lập quy trình phê duyệt trước khi xóa, lưu vết người thực hiện, và rà soát định kỳ các bản ghi đã xóa.',
                    $deletePct,
                    number_format($counts['DELETE'] ?? 0),
                );
            }

            $inactive = collect($mainFeatures)->filter(fn (array $r) => ($r['total'] ?? 0) === 0)->pluck('module')->all();
            if (count($inactive) >= 2) {
                $recommendations[] = 'Nhiều chức năng chính chưa có nhật ký: '.implode(', ', $inactive).'. Ưu tiên kiểm tra phân công ca trực, phân quyền và hướng dẫn sử dụng cho từng module.';
            }

            if (($counts['VIEW'] ?? 0) > (($counts['CREATE'] ?? 0) + ($counts['UPDATE'] ?? 0)) * 4) {
                $recommendations[] = 'Hoạt động tập trung vào tra cứu (Xem). Cân nhắc bổ sung báo cáo tổng hợp, dashboard hoặc xuất Excel để giảm số lần mở chi tiết từng bản ghi.';
            }

            if (($counts['CREATE'] ?? 0) + ($counts['UPDATE'] ?? 0) < $total * 0.05 && $total >= 20) {
                $recommendations[] = 'Tỷ lệ nhập liệu (Thêm/Sửa) thấp so với tổng lượt — xác minh nhân viên đã ghi nhận đầy đủ tại các module giám sát, tiếp nhận và quản lý hồ sơ.';
            }

            $loginCount = $counts['LOGIN'] ?? 0;
            $logoutCount = $counts['LOGOUT'] ?? 0;
            if ($loginCount > 0 && abs($loginCount - $logoutCount) > max(3, (int) ($loginCount * 0.15))) {
                $recommendations[] = sprintf(
                    'Chênh lệch Đăng nhập (%s) và Đăng xuất (%s) — nhắc nhân viên đăng xuất khi rời máy, rà soát phiên làm việc còn treo hoặc thiết bị dùng chung.',
                    number_format($loginCount),
                    number_format($logoutCount),
                );
            }

            if ($recommendations === []) {
                $recommendations[] = 'Mức độ hoạt động tổng thể ổn định. Nên lập lịch đối chiếu nhật ký hàng tuần với báo cáo nghiệp vụ của Phòng Kiểm tra Nội bộ.';
                $recommendations[] = 'Duy trì giám sát các chức năng: Cố vấn học tập, Lớp học online/trực tiếp, Thi kết thúc môn, Tiếp nhận yêu cầu/đơn thư và Quản lý hồ sơ.';
            }
        }

        return [
            'series' => $series,
            'mainFeatures' => $mainFeatures,
            'commentary' => $commentary,
            'recommendations' => $recommendations,
        ];
    }

    /** @param  list<array<string, mixed>>  $items */
    private function buildByAction(array $items, int $total): array
    {
        $counts = $this->actionCounts($items);
        $rows = collect($counts)
            ->map(fn (int $count, string $key) => [
                'key' => $key,
                'label' => self::ACTION_LABELS[$key] ?? $key,
                'count' => $count,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $commentary = [];
        $recommendations = [];

        if ($total === 0) {
            $commentary[] = 'Chưa có dữ liệu phân tích theo hành động trong phạm vi bộ lọc hiện tại.';
            $recommendations[] = 'Mở rộng phạm vi thời gian hoặc nới lỏng bộ lọc để thu thập đủ mẫu thống kê theo từng loại hành động.';
        } else {
            $commentary[] = sprintf(
                'Hệ thống ghi nhận %s loại hành động khác nhau, tổng %s lượt. Chi tiết: %s.',
                count($rows),
                number_format($total),
                $this->formatActionRowSummary($rows),
            );

            $coreRows = collect($rows)->filter(fn (array $r) => in_array($r['key'], self::CORE_ACTIONS, true))->values();
            if ($coreRows->isNotEmpty()) {
                $coreTotal = $coreRows->sum('count');
                $commentary[] = sprintf(
                    'Nhóm nghiệp vụ (Xem · Thêm · Sửa · Xóa) gồm %s lượt (%s%%). Trong đó, %s.',
                    number_format($coreTotal),
                    $total > 0 ? round($coreTotal / $total * 100, 1) : 0,
                    $this->formatActionRowSummary($coreRows->all()),
                );
            }

            $authRows = collect($rows)->filter(fn (array $r) => in_array($r['key'], ['LOGIN', 'LOGOUT'], true));
            if ($authRows->isNotEmpty()) {
                $login = $counts['LOGIN'] ?? 0;
                $logout = $counts['LOGOUT'] ?? 0;
                $commentary[] = sprintf(
                    'Nhóm xác thực: Đăng nhập %s lượt, Đăng xuất %s lượt. %s',
                    number_format($login),
                    number_format($logout),
                    $login > $logout
                        ? 'Số lần đăng nhập nhiều hơn đăng xuất — có thể do phiên chưa đóng hoặc thiết bị dùng chung.'
                        : ($logout > $login
                            ? 'Số lần đăng xuất nhiều hơn đăng nhập — kiểm tra ghi nhận đăng nhập có đầy đủ hay không.'
                            : 'Số lần đăng nhập và đăng xuất tương đối cân bằng.'),
                );
            }

            $top = $rows[0] ?? null;
            if ($top) {
                $commentary[] = sprintf(
                    'Hành động chiếm tỷ trọng lớn nhất là "%s" (%s lượt, %s%% tổng). %s',
                    $top['label'],
                    number_format($top['count']),
                    $top['percent'],
                    $this->interpretDominantAction($top['key'], $counts, $total),
                );
            }

            $deletePct = collect($rows)->firstWhere('key', 'DELETE')['percent'] ?? 0;
            if ($deletePct >= 8) {
                $recommendations[] = sprintf(
                    'Thao tác Xóa chiếm %s%% — bắt buộc đối chiếu với quy trình phê duyệt, kiểm tra quyền hạn người dùng, và lưu trữ bản sao trước khi xóa dữ liệu quan trọng.',
                    $deletePct,
                );
            }

            if (($counts['UPDATE'] ?? 0) > ($counts['CREATE'] ?? 0) * 2 && ($counts['UPDATE'] ?? 0) >= 5) {
                $recommendations[] = 'Sửa đổi nhiều hơn đáng kể so với Thêm mới — rà soát dữ liệu nhập ban đầu, đào tạo nhập liệu đúng ngay từ đầu để giảm chỉnh sửa hậu kỳ.';
            }

            if (($counts['VIEW'] ?? 0) >= $total * 0.6) {
                $recommendations[] = 'Hơn 60% thao tác là Xem — tối ưu màn hình danh sách, bộ lọc và xuất báo cáo để giảm thao tác tra cứu thủ công lặp lại.';
            }

            $loginCount = $counts['LOGIN'] ?? 0;
            $logoutCount = $counts['LOGOUT'] ?? 0;
            if ($loginCount > 0 && abs($loginCount - $logoutCount) > max(5, $loginCount * 0.2)) {
                $recommendations[] = 'Chênh lệch Đăng nhập/Đăng xuất lớn — rà soát chính sách phiên làm việc, nhắc đăng xuất khi kết thúc ca trực.';
            }

            if ($recommendations === []) {
                $recommendations[] = 'Cơ cấu hành động phù hợp với vận hành thông thường. Tiếp tục đối chiếu hàng tuần với quy trình nghiệp vụ từng chức năng chính.';
                $recommendations[] = 'Theo dõi biến động đột ngột của thao tác Xóa và Sửa — đây là nhóm rủi ro cao đối với tính toàn vẹn dữ liệu.';
            }
        }

        return [
            'items' => $rows,
            'commentary' => $commentary,
            'recommendations' => $recommendations,
        ];
    }

    /** @param  list<array<string, mixed>>  $items */
    private function buildByEmployee(array $items, int $total): array
    {
        $groups = [];
        foreach ($items as $item) {
            $name = trim((string) ($item['userName'] ?? 'Không rõ')) ?: 'Không rõ';
            $email = trim((string) ($item['userEmail'] ?? ''));
            $key = $email !== '' ? strtolower($email) : $name;
            $action = (string) ($item['action'] ?? 'VIEW');
            $feature = $this->features->resolve($item);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $name,
                    'email' => $email,
                    'total' => 0,
                    'topFeature' => '',
                    'topFeatureCount' => 0,
                    'featureCounts' => [],
                ] + array_fill_keys(self::CORE_ACTIONS, 0);
            }
            $groups[$key]['total']++;
            if (in_array($action, self::CORE_ACTIONS, true)) {
                $groups[$key][$action]++;
            }
            $featureLabel = $feature['label'];
            $groups[$key]['featureCounts'][$featureLabel] = ($groups[$key]['featureCounts'][$featureLabel] ?? 0) + 1;
        }

        $rows = collect($groups)
            ->map(function (array $row) use ($total) {
                $topFeature = collect($row['featureCounts'] ?? [])->sortDesc()->keys()->first();
                $row['topFeature'] = $topFeature ?: '—';
                $row['topFeatureCount'] = $topFeature ? ($row['featureCounts'][$topFeature] ?? 0) : 0;
                unset($row['featureCounts']);
                $row['percent'] = $total > 0 ? round($row['total'] / $total * 100, 1) : 0;

                return $row;
            })
            ->sortByDesc('total')
            ->values()
            ->take(15)
            ->all();

        $commentary = [];
        $recommendations = [];
        $distinctEmployees = count($groups);

        if ($total === 0) {
            $commentary[] = 'Chưa có dữ liệu phân tích theo nhân viên trong phạm vi bộ lọc.';
            $recommendations[] = 'Mở rộng khoảng thời gian hoặc bỏ lọc theo người dùng để xem phân bổ hoạt động theo từng cán bộ.';
        } else {
            $commentary[] = sprintf(
                'Có %s nhân viên/người dùng phát sinh thao tác, tổng %s lượt ghi nhận. Bảng thống kê hiển thị tối đa 15 người có số lượt cao nhất.',
                number_format($distinctEmployees),
                number_format($total),
            );

            $top = $rows[0] ?? null;
            if ($top) {
                $commentary[] = sprintf(
                    '"%s" (%s) dẫn đầu với %s lượt (%s%% tổng), gồm Xem %s · Thêm %s · Sửa %s · Xóa %s. Chức năng sử dụng nhiều nhất: "%s" (%s lượt).',
                    $top['name'],
                    $top['email'] ?: 'không có email',
                    number_format($top['total']),
                    $top['percent'],
                    number_format($top['VIEW'] ?? 0),
                    number_format($top['CREATE'] ?? 0),
                    number_format($top['UPDATE'] ?? 0),
                    number_format($top['DELETE'] ?? 0),
                    $top['topFeature'],
                    number_format($top['topFeatureCount'] ?? 0),
                );
            }

            if (count($rows) >= 2) {
                $others = array_slice($rows, 1, 3);
                $parts = array_map(fn (array $r) => sprintf(
                    '"%s" %s lượt (%s%%)',
                    $r['name'],
                    number_format($r['total']),
                    $r['percent'],
                ), $others);
                $commentary[] = 'Các nhân viên có mức hoạt động cao tiếp theo: '.implode('; ', $parts).'.';
            }

            $topThreeTotal = collect(array_slice($rows, 0, 3))->sum('total');
            $topThreePct = $total > 0 ? round($topThreeTotal / $total * 100, 1) : 0;
            if ($topThreePct >= 50 && count($rows) >= 3) {
                $commentary[] = sprintf(
                    'Ba nhân viên hàng đầu đảm nhiệm %s%% tổng lượt thao tác — mức tập trung cao, cần đánh giá phân bổ tải và kế hoạch dự phòng nhân sự.',
                    $topThreePct,
                );
            } elseif ($distinctEmployees >= 5) {
                $commentary[] = 'Hoạt động phân tán trên nhiều nhân viên — thể hiện sự tham gia rộng của đội ngũ trong các chức năng kiểm tra.';
            }

            $deleteUsers = collect($rows)->filter(fn (array $r) => ($r['DELETE'] ?? 0) > 0)->sortByDesc('DELETE');
            if ($deleteUsers->isNotEmpty()) {
                $leader = $deleteUsers->first();
                $commentary[] = sprintf(
                    'Có %s nhân viên thực hiện thao tác Xóa; nhiều nhất là "%s" với %s lượt xóa.',
                    $deleteUsers->count(),
                    $leader['name'],
                    $leader['DELETE'],
                );
            }

            if ($top && $top['percent'] >= 30) {
                $recommendations[] = sprintf(
                    '"%s" chiếm %s%% tổng hoạt động — cân nhắc phân công thêm cho đồng nghiệp, luân phiên ca trực module "%s", hoặc hỗ trợ nhập liệu tập trung.',
                    $top['name'],
                    $top['percent'],
                    $top['topFeature'] !== '—' ? $top['topFeature'] : 'chính',
                );
            }

            foreach (collect($rows)->filter(fn (array $r) => ($r['DELETE'] ?? 0) >= 3)->sortByDesc('DELETE')->take(2) as $row) {
                $recommendations[] = sprintf(
                    '"%s" có %s thao tác Xóa — xác minh đúng phạm vi quyền, đối chiếu với quy trình phê duyệt và lưu nhật ký chi tiết khi xóa.',
                    $row['name'],
                    $row['DELETE'],
                );
            }

            $viewOnly = collect($rows)->filter(fn (array $r) => $r['total'] >= 5 && ($r['CREATE'] ?? 0) + ($r['UPDATE'] ?? 0) + ($r['DELETE'] ?? 0) === 0);
            if ($viewOnly->isNotEmpty()) {
                $names = $viewOnly->take(3)->pluck('name')->implode(', ');
                $recommendations[] = 'Một số nhân viên chỉ có thao tác Xem ('.$names.') — kiểm tra phân quyền ghi nhận hoặc hướng dẫn nhập liệu đầy đủ theo quy trình.';
            }

            if ($distinctEmployees <= 2 && $total >= 10) {
                $recommendations[] = 'Chỉ có '.number_format($distinctEmployees).' người dùng phát sinh nhật ký — mở rộng đào tạo và phân quyền để nhiều cán bộ tham gia vận hành hệ thống.';
            }

            if ($recommendations === []) {
                $recommendations[] = 'Phân bổ hoạt động nhân viên hợp lý. Duy trì rà soát định kỳ theo từng chức năng chính và đối chiếu với kế hoạch ca trực.';
                $recommendations[] = 'Khuyến khích ghi nhận đầy đủ thao tác Thêm/Sửa tại module giám sát và tiếp nhận để nhật ký phản ánh đúng công việc thực tế.';
            }
        }

        return [
            'items' => $rows,
            'commentary' => $commentary,
            'recommendations' => $recommendations,
        ];
    }

    /** @return array<string, array<string, int|string>> */
    private function initFeatureGroups(): array
    {
        $groups = [];
        foreach (ActivityLogMainFeatureResolver::FEATURE_ORDER as $featureKey) {
            $label = $featureKey === ActivityLogMainFeatureResolver::OTHER_KEY
                ? ActivityLogMainFeatureResolver::OTHER_LABEL
                : ActivityLogMainFeatureResolver::FEATURES[$featureKey];
            $groups[$featureKey] = [
                'key' => $featureKey,
                'module' => $label,
                'total' => 0,
            ] + array_fill_keys(self::CORE_ACTIONS, 0);
        }

        return $groups;
    }

    /** @param  array<string, int>  $counts */
    private function formatActionBreakdown(array $counts, int $total): string
    {
        $parts = [];
        foreach (self::CORE_ACTIONS as $action) {
            $count = $counts[$action] ?? 0;
            if ($count === 0) {
                continue;
            }
            $pct = $total > 0 ? round($count / $total * 100, 1) : 0;
            $parts[] = sprintf('%s %s (%s%%)', self::ACTION_LABELS[$action], number_format($count), $pct);
        }
        foreach (['LOGIN', 'LOGOUT'] as $action) {
            $count = $counts[$action] ?? 0;
            if ($count === 0) {
                continue;
            }
            $pct = $total > 0 ? round($count / $total * 100, 1) : 0;
            $parts[] = sprintf('%s %s (%s%%)', self::ACTION_LABELS[$action], number_format($count), $pct);
        }

        return $parts !== [] ? implode(' · ', $parts) : 'chưa có thao tác';
    }

    /** @param  list<array{label: string, count: int, percent: float|int}>  $rows */
    private function formatActionRowSummary(array $rows): string
    {
        return collect($rows)
            ->map(fn (array $r) => sprintf('%s %s lượt (%s%%)', $r['label'], number_format($r['count']), $r['percent']))
            ->implode(' · ');
    }

    /** @param  list<array{module: string, total: int, percent: float|int}>  $rows */
    private function formatFeatureRanking(array $rows): string
    {
        return collect($rows)
            ->map(fn (array $r) => sprintf('"%s" %s lượt (%s%%)', $r['module'], number_format($r['total']), $r['percent']))
            ->implode(' · ');
    }

    /** @param  list<array{module: string}>  $rows */
    private function formatFeatureNames(array $rows): string
    {
        return collect($rows)->pluck('module')->implode(', ');
    }

    /** @param  array<string, int>  $counts */
    private function interpretDominantAction(string $key, array $counts, int $total): string
    {
        return match ($key) {
            'VIEW' => 'Điều này cho thấy nhu cầu tra cứu, đối chiếu thông tin chiếm ưu thế trong giai đoạn này.',
            'CREATE' => 'Hệ thống đang trong giai đoạn nhập liệu, ghi nhận mới nhiều — cần kiểm soát chất lượng dữ liệu đầu vào.',
            'UPDATE' => 'Có nhiều chỉnh sửa bản ghi — nên rà soát tính chính xác trước khi lưu và hạn chế sửa lặp lại.',
            'DELETE' => 'Tỷ lệ xóa cao — cần đặc biệt chú ý quy trình phê duyệt và khả năng khôi phục dữ liệu.',
            'LOGIN' => 'Tần suất truy cập hệ thống cao — phù hợp với giai đoạn cao điểm kiểm tra hoặc nhiều ca trực.',
            'LOGOUT' => 'Nhiều lượt đăng xuất — người dùng tuân thủ kết thúc phiên; đối chiếu với số lần đăng nhập để đảm bảo nhất quán.',
            default => 'Cần theo dõi thêm để đánh giá xu hướng dài hạn.',
        };
    }

    /** @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function actionCounts(array $items): array
    {
        $counts = [];
        foreach ($items as $item) {
            $action = (string) ($item['action'] ?? 'VIEW');
            $counts[$action] = ($counts[$action] ?? 0) + 1;
        }

        return $counts;
    }
}
