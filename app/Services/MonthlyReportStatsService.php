<?php

namespace App\Services;

use App\Models\BuildingBlock;
use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\Petition;
use App\Models\ServiceRequest;
use App\Models\StudentViolation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MonthlyReportStatsService
{
    /**
     * Incident name aliases per module/category. Matched via exact mb_strtolower trim.
     */
    private const INCIDENTS = [
        'in_person' => [
            'chuyen_phong'      => ['Chuyển phòng'],
            'day_thay'          => ['Dạy thay (đổi Giảng viên)', 'Dạy thay'],
            'bao_nghi'          => ['Báo nghỉ'],
            'day_ngoai_tkb'     => ['Ngoài lịch (Không đăng ký)'],
            'gv_khong_den'      => ['Không đến lớp, có SV chờ (số SV chờ)', 'GV bỏ lớp'],
            'sv_ra_ve'          => ['Sinh viên phải ra về'],
            'khong_gv_khong_sv' => ['Không GV, Không SV (đăng ký nhưng không dạy)', 'Không GV, Không SV'],
            'den_tre'           => ['Đi trễ'],
            've_som'            => ['Về sớm'],
            'co_gv_khong_sv'    => ['Có GV, Không SV'],
            'ghi_nhan_khac'     => ['Ghi nhận khác'],
        ],
        'exam' => [
            'coi_thi_thay'   => ['Coi thi thay'],
            'chuyen_phong'   => ['Chuyển phòng thi'],
            'cbct_khong_den' => ['CBCT không đến coi thi'],
            'loi_de'         => ['Lỗi đề thi (thiếu trang/thiếu đáp án/trùng đáp án)', 'Thiếu đề thi (số lượng)'],
            'sai_quy_che'    => ['CBCT thực hiện không đúng quy chế', 'Ghi nhận sinh viên vi phạm quy chế thi'],
            'ghi_nhan_khac'  => ['Ghi nhận khác'],
        ],
        'homeroom' => [
            'co_sh'         => ['Có SH'],
            'sh_ngoai'      => ['SH Ngoài lịch (Không đăng ký)', 'Không SH theo lịch'],
            'ghi_nhan_khac' => ['Ghi nhận khác'],
        ],
    ];

    /**
     * Ordered keyword sets for exam violation classification (first match wins).
     */
    private const EXAM_VIOLATION_TYPES = [
        'phone_bring' => ['mang điện thoại vào phòng thi', 'mang điện thoại', 'đưa điện thoại'],
        'use_phone'   => ['sử dụng điện thoại trong phòng thi', 'sử dụng điện thoại', 'dùng điện thoại'],
        'docs'        => ['mang tài liệu vào phòng thi', 'tài liệu vi phạm', 'mang tài liệu', 'tài liệu'],
        'talk'        => ['nói chuyện trong phòng thi', 'trao đổi bài', 'nói chuyện', 'trao đổi', 'quay cóp'],
    ];

    /** @var list<BuildingBlock>|null */
    private ?array $cachedBlocks = null;

    /** @var array<string, list<string>> */
    private array $aliasCache = [];

    public function __construct(private ReportQueryService $queries) {}

    /**
     * @param  list<string>  $employees
     * @param  list<string>  $departments
     * @return array{metrics: list<int>, redTexts: list<string>}
     */
    public function buildReportData(
        string $fromDisplay,
        string $toDisplay,
        string $campus = '',
        array $employees = [],
        array $departments = [],
    ): array {
        return $this->computeAll($fromDisplay, $toDisplay, $campus, $employees, $departments);
    }

    /**
     * @param  list<string>  $employees
     * @param  list<string>  $departments
     * @return list<int>
     */
    public function metricValues(
        string $fromDisplay,
        string $toDisplay,
        string $campus = '',
        array $employees = [],
        array $departments = [],
    ): array {
        return $this->computeAll($fromDisplay, $toDisplay, $campus, $employees, $departments)['metrics'];
    }

    /**
     * Filter appendix rows for Word table.
     * Online-location rows → department filter; physical → campus filter; always employee filter.
     *
     * @param  Collection<int, DailySchedule>  $items
     * @param  list<string>  $employees
     * @param  list<string>  $departments
     * @return Collection<int, DailySchedule>
     */
    public function filterSchedulesForReport(
        Collection $items,
        string $campus = '',
        array $employees = [],
        array $departments = [],
    ): Collection {
        $employeeAliases = Employee::scheduleEmployeeAliases($employees);

        return $items->filter(function (DailySchedule $s) use ($campus, $employeeAliases, $departments) {
            if ($employeeAliases !== [] && ! $this->scheduleMatchesEmployee($s, $employeeAliases)) {
                return false;
            }

            if ($this->isOnlineLocation($s)) {
                return $departments === [] || $this->matchesDepartment($s, $departments);
            }

            return $campus === '' || $this->matchesCampus($s, $campus);
        })->values();
    }

    // ─── Core computation ──────────────────────────────────────────────────────

    /**
     * @param  list<string>  $employees
     * @param  list<string>  $departments
     * @return array{metrics: list<int>, redTexts: list<string>}
     */
    private function computeAll(
        string $fromDisplay,
        string $toDisplay,
        string $campus,
        array $employees,
        array $departments,
    ): array {
        $from            = $this->queries->normalizeDate($fromDisplay);
        $to              = $this->queries->normalizeDate($toDisplay);
        $employeeAliases = Employee::scheduleEmployeeAliases($employees);

        // ── Support records (campus-filtered on building_block / building) ──────
        $petitions   = $this->filterByCampusBlock($this->queries->petitions($from, $to), $campus, 'building_block');
        $serviceReqs = $this->filterByCampusBlock($this->queries->serviceRequests($from, $to), $campus, 'building_block');
        $assets      = $this->filterByCampusBlock($this->queries->assetReceptions($from, $to), $campus, 'building_block');
        $violations  = $this->filterByCampusBlock($this->queries->violations($from, $to), $campus, 'building');

        if ($employeeAliases !== []) {
            $violations = $this->filterByEmployee($violations, $employeeAliases, 'officer');
        }

        // ── Schedule collections per module ────────────────────────────────────
        $inPersonAll = $this->schedulesForModule('in-person', $from, $to, $campus, $employeeAliases, $departments);
        $onlineAll   = $this->schedulesForModule('online',    $from, $to, $campus, $employeeAliases, $departments);
        $examsAll    = $this->schedulesForModule('exams',     $from, $to, $campus, $employeeAliases, $departments);
        $homeroomAll = $this->schedulesForModule('homeroom',  $from, $to, $campus, $employeeAliases, $departments);

        // ── Recorded / notable subsets ─────────────────────────────────────────
        $inPersonRecorded = $inPersonAll->filter(fn (DailySchedule $s) => DailySchedule::isRecorded($s));
        $inPersonNotable  = $inPersonRecorded->filter(fn (DailySchedule $s) => DailyReportService::isNotableIncident($s->incident));

        $onlineRecorded  = $onlineAll->filter(fn (DailySchedule $s) => DailySchedule::isRecorded($s));
        $examsRecorded   = $examsAll->filter(fn (DailySchedule $s) => DailySchedule::isRecorded($s));
        $examsNotable    = $examsRecorded->filter(fn (DailySchedule $s) => DailyReportService::isNotableIncident($s->incident));
        $homeroomRecorded = $homeroomAll->filter(fn (DailySchedule $s) => DailySchedule::isRecorded($s));

        // ── In-person incident counts ──────────────────────────────────────────
        $ipCounts = $this->countNamedIncidents($inPersonNotable, 'in_person');

        $inPersonOther = max(0,
            $inPersonNotable->count()
            - $ipCounts['chuyen_phong']
            - $ipCounts['day_thay']
            - $ipCounts['bao_nghi']
            - $ipCounts['day_ngoai_tkb']
            - $ipCounts['gv_khong_den']
            - $ipCounts['sv_ra_ve']
            - $ipCounts['khong_gv_khong_sv']
            - $ipCounts['den_tre']
            - $ipCounts['ve_som']
            - $ipCounts['co_gv_khong_sv']
        );

        // ── Online issue counts ────────────────────────────────────────────────
        $onlineNoLcms    = 0;
        $onlineNgoaiTkb  = 0;
        $onlineBaoNghi   = 0;
        $onlineNotOnline = 0;

        foreach ($onlineRecorded as $s) {
            match ($this->classifyOnlineIssue($s)) {
                'no_lcms'    => $onlineNoLcms++,
                'ngoai_tkb'  => $onlineNgoaiTkb++,
                'bao_nghi'   => $onlineBaoNghi++,
                'not_online' => $onlineNotOnline++,
                default      => null,
            };
        }

        $onlineTotal = $onlineRecorded->count();
        $onlineDone = $onlineRecorded->filter(function (DailySchedule $s) {
            $issue = $this->classifyOnlineIssue($s);

            return $issue !== 'bao_nghi' && $issue !== 'not_online';
        })->count();

        // ── Exam incident counts ───────────────────────────────────────────────
        $examCounts = $this->countNamedIncidents($examsNotable, 'exam');

        // ── Violation classification ───────────────────────────────────────────
        $vioPhoneBring = 0;
        $vioDocs       = 0;
        $vioUsePhone   = 0;
        $vioTalk       = 0;
        $vioOther      = 0;

        foreach ($violations as $v) {
            /** @var StudentViolation $v */
            match ($this->classifyViolationType((string) ($v->violation_type ?? '').' '.(string) ($v->note ?? ''))) {
                'phone_bring' => $vioPhoneBring++,
                'docs'        => $vioDocs++,
                'use_phone'   => $vioUsePhone++,
                'talk'        => $vioTalk++,
                default       => $vioOther++,
            };
        }

        // ── Homeroom counts ────────────────────────────────────────────────────
        $homeroomCounts  = $this->countNamedIncidents($homeroomRecorded, 'homeroom');
        $homeroomTotal   = $homeroomRecorded->count();
        $homeroomOnsite  = $homeroomRecorded->filter(fn (DailySchedule $s) => ! $this->isOnlineLocation($s))->count();
        $homeroomOnline  = $homeroomRecorded->filter(fn (DailySchedule $s) => $this->isOnlineLocation($s))->count();
        $homeroomDone = (int) ($homeroomCounts['co_sh'] ?? 0);
        if ($homeroomDone === 0) {
            $homeroomDone = $homeroomRecorded->reject(function (DailySchedule $s) {
                $normalized = mb_strtolower(trim((string) ($s->incident ?? '')));
                foreach (array_merge(
                    self::INCIDENTS['homeroom']['sh_ngoai'],
                    self::INCIDENTS['homeroom']['ghi_nhan_khac'],
                ) as $alias) {
                    if ($normalized === mb_strtolower(trim($alias))) {
                        return true;
                    }
                }

                return false;
            })->count();
        }

        // ── Petition classification ────────────────────────────────────────────
        $petitionComplaint = $petitions->filter(fn (Petition $p) => $this->isPetitionComplaint($p))->count();
        $petitionFeedback  = $petitions->filter(fn (Petition $p) => $this->isPetitionFeedback($p))->count();
        if ($petitionFeedback === 0 && $petitions->count() > $petitionComplaint) {
            $petitionFeedback = $petitions->count() - $petitionComplaint;
        }

        // ── Service request classification ─────────────────────────────────────
        $srAttendance = $serviceReqs->filter(fn (ServiceRequest $r) => $this->isAttendanceRequest($r))->count();
        $srLost       = $serviceReqs->filter(fn (ServiceRequest $r) => $this->isLostRequest($r))->count();
        $srOther      = max(0, $serviceReqs->count() - $srAttendance - $srLost);
        $supportTotal = $assets->count() + $serviceReqs->count();

        // ── Red texts ─────────────────────────────────────────────────────────
        $redTexts = [
            $this->redTextForIncident($inPersonNotable, 'in_person', 'chuyen_phong'),
            $this->redTextForIncident($inPersonNotable, 'in_person', 'day_thay'),
            $this->redTextForIncident($inPersonNotable, 'in_person', 'bao_nghi'),
            $this->redTextForIncident($inPersonNotable, 'in_person', 'ghi_nhan_khac'),
            $this->redTextForIncident($inPersonNotable, 'in_person', 'day_ngoai_tkb'),
            $this->redTextForOnlineIssue($onlineRecorded, 'ngoai_tkb'),
            $this->redTextForOnlineIssue($onlineRecorded, 'bao_nghi'),
            $this->redTextForIncident($homeroomRecorded, 'homeroom', 'sh_ngoai'),
            $this->redTextForIncident($homeroomRecorded, 'homeroom', 'ghi_nhan_khac'),
        ];

        // ── Assemble metrics (indices 0-52) ────────────────────────────────────
        $metrics = [
            // 0-1: petitions
            (int) $petitionComplaint,
            (int) $petitionFeedback,
            // 2-6: support / service requests / assets
            (int) $supportTotal,
            (int) $assets->count(),
            (int) $srAttendance,
            (int) $srOther,
            (int) $srLost,
            // 7-18: in-person
            (int) $inPersonNotable->count(),
            (int) $ipCounts['chuyen_phong'],
            (int) $ipCounts['day_thay'],
            (int) $ipCounts['bao_nghi'],
            (int) $inPersonOther,
            (int) $ipCounts['day_ngoai_tkb'],
            (int) $ipCounts['gv_khong_den'],
            (int) $ipCounts['sv_ra_ve'],
            (int) $ipCounts['khong_gv_khong_sv'],
            (int) $ipCounts['den_tre'],
            (int) $ipCounts['ve_som'],
            (int) $ipCounts['co_gv_khong_sv'],
            // 19-24: online (recorded)
            (int) $onlineTotal,
            (int) $onlineDone,
            (int) $onlineNoLcms,
            (int) $onlineNgoaiTkb,
            (int) $onlineBaoNghi,
            (int) $onlineNotOnline,
            // 25-32: exams (recorded)
            (int) $examsRecorded->count(),
            (int) $examsNotable->count(),
            (int) $examCounts['coi_thi_thay'],
            (int) $examCounts['chuyen_phong'],
            (int) $examCounts['cbct_khong_den'],
            (int) $examCounts['loi_de'],
            (int) $examCounts['sai_quy_che'],
            (int) $examCounts['ghi_nhan_khac'],
            // 33-38: exam violations
            (int) $violations->count(),
            (int) $vioPhoneBring,
            (int) $vioDocs,
            (int) $vioOther,
            (int) $vioUsePhone,
            (int) $vioTalk,
            // 39-44: homeroom (recorded)
            (int) $homeroomTotal,
            (int) $homeroomOnsite,
            (int) $homeroomOnline,
            (int) $homeroomDone,
            (int) $homeroomCounts['sh_ngoai'],
            (int) $homeroomCounts['ghi_nhan_khac'],
            // 45-52: reserved zeros
            0, 0, 0, 0, 0, 0, 0, 0,
        ];

        return ['metrics' => $metrics, 'redTexts' => $redTexts];
    }

    // ─── Schedule loading ──────────────────────────────────────────────────────

    /**
     * Load schedules for the given module and date range, applying appropriate filters:
     * - online:       employee + department (no campus)
     * - in-person / exams: employee + campus (no department)
     * - homeroom:     employee; online-location → department; offline → campus
     *
     * @param  list<string>  $employeeAliases  pre-expanded via Employee::scheduleEmployeeAliases
     * @param  list<string>  $departments
     * @return Collection<int, DailySchedule>
     */
    private function schedulesForModule(
        string $module,
        string $from,
        string $to,
        string $campus,
        array $employeeAliases,
        array $departments,
    ): Collection {
        /** @var Collection<int, DailySchedule> $all */
        $all = DailySchedule::query()
            ->forListTable()
            ->forModule($module)
            ->where(fn (Builder $q) => $this->queries->whereDisplayDateBetween($q, 'date', $from, $to))
            ->get();

        return $all->filter(function (DailySchedule $s) use ($module, $campus, $employeeAliases, $departments) {
            if ($employeeAliases !== [] && ! $this->scheduleMatchesEmployee($s, $employeeAliases)) {
                return false;
            }

            return match ($module) {
                'online'            => $departments === [] || $this->matchesDepartment($s, $departments),
                'in-person', 'exams' => $campus === '' || $this->matchesCampus($s, $campus),
                'homeroom'          => $this->isOnlineLocation($s)
                    ? ($departments === [] || $this->matchesDepartment($s, $departments))
                    : ($campus === '' || $this->matchesCampus($s, $campus)),
                default => true,
            };
        })->values();
    }

    // ─── Matching helpers ──────────────────────────────────────────────────────

    private function isOnlineLocation(DailySchedule $s): bool
    {
        return str_contains(mb_strtolower((string) ($s->building ?? '')), 'trực tuyến');
    }

    /** Exact case-insensitive trim match on schedule.department. */
    private function matchesDepartment(DailySchedule $s, array $departments): bool
    {
        $dept = mb_strtolower(trim((string) ($s->department ?? '')));

        foreach ($departments as $selected) {
            if (mb_strtolower(trim((string) $selected)) === $dept) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether schedule's building/room/note string matches the selected campus.
     * Uses expanded BuildingBlock aliases; short aliases (≤3 chars) use word-boundary regex.
     */
    private function matchesCampus(DailySchedule $s, string $campus): bool
    {
        if ($campus === '') {
            return true;
        }

        $aliases = $this->expandCampusAliases($campus);
        if ($aliases === []) {
            return true;
        }

        $hay = mb_strtolower(
            trim((string) ($s->building ?? '')) . ' ' .
            trim((string) ($s->room ?? '')) . ' ' .
            trim((string) ($s->note ?? ''))
        );

        return $this->matchesAnyAlias($hay, $aliases);
    }

    /** Match schedule.employee field against pre-expanded employee alias list. */
    private function scheduleMatchesEmployee(DailySchedule $s, array $employeeAliases): bool
    {
        $key = Employee::normalizeRecipientKey($s->employee);
        if ($key === '') {
            return false;
        }

        foreach ($employeeAliases as $alias) {
            if (Employee::normalizeRecipientKey($alias) === $key) {
                return true;
            }
        }

        return false;
    }

    // ─── Collection filters ────────────────────────────────────────────────────

    /**
     * Filter any collection by campus alias matching against a single string field.
     *
     * @template T
     * @param  Collection<int, T>  $items
     * @return Collection<int, T>
     */
    private function filterByCampusBlock(Collection $items, string $campus, string $field): Collection
    {
        if ($campus === '') {
            return $items;
        }

        $aliases = $this->expandCampusAliases($campus);
        if ($aliases === []) {
            return $items;
        }

        return $items->filter(function ($item) use ($aliases, $field) {
            $value = mb_strtolower(trim((string) ($item->$field ?? '')));

            return $this->matchesAnyAlias($value, $aliases);
        })->values();
    }

    /**
     * Filter a collection by normalised employee key on the given field.
     *
     * @template T
     * @param  Collection<int, T>  $items
     * @param  list<string>  $employeeAliases
     * @return Collection<int, T>
     */
    private function filterByEmployee(Collection $items, array $employeeAliases, string $field): Collection
    {
        if ($employeeAliases === []) {
            return $items;
        }

        $normalizedAliases = array_map(
            fn (string $a) => Employee::normalizeRecipientKey($a),
            $employeeAliases,
        );

        return $items->filter(function ($item) use ($normalizedAliases, $field) {
            $key = Employee::normalizeRecipientKey((string) ($item->$field ?? ''));

            return $key !== '' && in_array($key, $normalizedAliases, true);
        })->values();
    }

    // ─── Campus alias expansion ────────────────────────────────────────────────

    /**
     * Expand a campus filter string to a list of matching building aliases by searching
     * BuildingBlock code/name/note. Exact match always; contains if length ≥ 4 chars.
     * Results are cached per campus string for the service lifetime.
     *
     * @return list<string>
     */
    private function expandCampusAliases(string $campus): array
    {
        $campus = trim($campus);
        if ($campus === '') {
            return [];
        }

        if (isset($this->aliasCache[$campus])) {
            return $this->aliasCache[$campus];
        }

        $campusNorm = mb_strtolower($campus);
        $campusLen  = mb_strlen($campus);
        $aliases    = [$campus];

        foreach ($this->loadBlocks() as $block) {
            $blockFields = array_values(array_filter([
                trim((string) ($block->code ?? '')),
                trim((string) ($block->name ?? '')),
                trim((string) ($block->note ?? '')),
            ]));

            $matched = false;
            foreach ($blockFields as $bf) {
                $bfNorm = mb_strtolower($bf);
                $bfLen  = mb_strlen($bf);

                if ($bfNorm === $campusNorm) {
                    $matched = true;
                    break;
                }

                if ($campusLen >= 4 && str_contains($bfNorm, $campusNorm)) {
                    $matched = true;
                    break;
                }

                if ($bfLen >= 4 && str_contains($campusNorm, $bfNorm)) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                foreach ($blockFields as $bf) {
                    $aliases[] = $bf;
                }
            }
        }

        $result = array_values(array_unique(array_filter($aliases)));
        $this->aliasCache[$campus] = $result;

        return $result;
    }

    /**
     * Test whether $haystack matches any alias. Short aliases (≤3 chars) use a
     * Unicode word-boundary regex; longer aliases use a simple case-insensitive contains.
     *
     * @param  list<string>  $aliases  raw (non-normalised) alias strings
     */
    private function matchesAnyAlias(string $haystack, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            $aliasLower = mb_strtolower($alias);
            $aliasLen   = mb_strlen($alias);

            if ($aliasLen === 0) {
                continue;
            }

            if ($aliasLen <= 3) {
                $escaped = preg_quote($aliasLower, '/');
                if (preg_match('/(?<!\p{L}|\d)' . $escaped . '(?!\p{L}|\d)/ui', $haystack)) {
                    return true;
                }
            } else {
                if (str_contains($haystack, $aliasLower)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<BuildingBlock> */
    private function loadBlocks(): array
    {
        return $this->cachedBlocks ??= BuildingBlock::query()
            ->get(['code', 'name', 'note'])
            ->all();
    }

    // ─── Incident / violation classification ───────────────────────────────────

    /**
     * Count incidents in a schedule collection by named category for a given group
     * (in_person / exam / homeroom). Matches exact mb_strtolower trim of incident field.
     *
     * @param  Collection<int, DailySchedule>  $schedules
     * @return array<string, int>  key → count (all category keys initialised to 0)
     */
    private function countNamedIncidents(Collection $schedules, string $group): array
    {
        $counts = array_fill_keys(array_keys(self::INCIDENTS[$group] ?? []), 0);

        foreach ($schedules as $s) {
            $normalized = mb_strtolower(trim((string) ($s->incident ?? '')));
            if ($normalized === '') {
                continue;
            }

            foreach (self::INCIDENTS[$group] as $key => $aliases) {
                foreach ($aliases as $alias) {
                    if (mb_strtolower(trim($alias)) === $normalized) {
                        $counts[$key]++;
                        break 2;
                    }
                }
            }
        }

        return $counts;
    }

    /**
     * Classify an online recorded schedule into one issue bucket.
     * Priority: bao_nghi → ngoai_tkb → not_online → no_lcms → other/done.
     *
     * Returns 'done' when DailyReportService::isNotableIncident is false.
     */
    private function classifyOnlineIssue(DailySchedule $s): string
    {
        if (! DailyReportService::isNotableIncident($s->incident)) {
            return 'done';
        }

        $incident = mb_strtolower(trim((string) ($s->incident ?? '')));
        $detail   = mb_strtolower(trim((string) ($s->incident_detail ?? '')));
        $combined = $incident . ' ' . $detail;

        if (str_contains($incident, 'báo nghỉ')) {
            return 'bao_nghi';
        }

        if (str_contains($incident, 'ngoài lịch') || str_contains($incident, 'không đăng ký')) {
            return 'ngoai_tkb';
        }

        if (
            str_contains($combined, 'không vào') ||
            str_contains($incident, 'gv không') ||
            str_contains($incident, 'giảng viên không') ||
            str_contains($incident, 'không online') ||
            str_contains($incident, 'không lên lớp')
        ) {
            return 'not_online';
        }

        if (str_contains($combined, 'lcms') || str_contains($combined, 'không có link')) {
            return 'no_lcms';
        }

        return 'other';
    }

    /**
     * Classify a StudentViolation violation_type string into one of the exam violation
     * buckets (phone_bring / use_phone / docs / talk / other). First keyword match wins.
     */
    private function classifyViolationType(string $violationType): string
    {
        $lower = mb_strtolower(trim($violationType));

        foreach (self::EXAM_VIOLATION_TYPES as $key => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    return $key;
                }
            }
        }

        return 'other';
    }

    private function isPetitionComplaint(Petition $p): bool
    {
        return str_contains(mb_strtolower(trim((string) ($p->petition_type ?? ''))), 'khiếu nại');
    }

    private function isPetitionFeedback(Petition $p): bool
    {
        $type = mb_strtolower(trim((string) ($p->petition_type ?? '')));

        return str_contains($type, 'phản ánh') || str_contains($type, 'kiến nghị') || str_contains($type, 'góp ý');
    }

    private function isAttendanceRequest(ServiceRequest $r): bool
    {
        return str_contains(mb_strtolower(trim((string) ($r->request_type ?? ''))), 'điểm danh');
    }

    private function isLostRequest(ServiceRequest $r): bool
    {
        $type = mb_strtolower(trim((string) ($r->request_type ?? '')));

        return str_contains($type, 'mất') || str_contains($type, 'tìm') || str_contains($type, 'thất lạc');
    }

    // ─── Red text builders ─────────────────────────────────────────────────────

    /**
     * Build a newline-joined summary string for schedules matching the given named incident.
     *
     * @param  Collection<int, DailySchedule>  $schedules
     */
    private function redTextForIncident(Collection $schedules, string $group, string $incidentKey): string
    {
        $aliases = array_map(
            fn (string $a) => mb_strtolower(trim($a)),
            self::INCIDENTS[$group][$incidentKey] ?? [],
        );

        if ($aliases === []) {
            return '';
        }

        $lines = [];
        foreach ($schedules as $s) {
            $normalized = mb_strtolower(trim((string) ($s->incident ?? '')));
            if (in_array($normalized, $aliases, true)) {
                $lines[] = $this->formatScheduleLine($s);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Build a newline-joined summary string for online recorded schedules matching
     * a specific classifyOnlineIssue() bucket (e.g. 'ngoai_tkb', 'bao_nghi').
     *
     * @param  Collection<int, DailySchedule>  $schedules
     */
    private function redTextForOnlineIssue(Collection $schedules, string $issueKey): string
    {
        $lines = [];
        foreach ($schedules as $s) {
            if ($this->classifyOnlineIssue($s) === $issueKey) {
                $lines[] = $this->formatScheduleLine($s);
            }
        }

        return implode("\n", $lines);
    }

    /** Format one schedule row as "[class] ([department]) [date]: [incident_detail]". */
    private function formatScheduleLine(DailySchedule $s): string
    {
        $class  = trim((string) ($s->class ?? ''));
        $dept   = trim((string) ($s->department ?? ''));
        $date   = trim((string) ($s->date ?? ''));
        $detail = trim((string) ($s->incident_detail ?? ''));

        $label = $class !== '' ? $class : '---';

        if ($dept !== '') {
            $label .= ' (' . $dept . ')';
        }

        if ($date !== '') {
            $label .= ' ' . $date;
        }

        if ($detail !== '') {
            $label .= ': ' . $detail;
        }

        return $label;
    }
}
