<?php

namespace App\Services;

use App\Models\DocumentRecord;
use App\Services\EvidenceStorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DocumentRecordQueryService
{
  public function __construct(private EvidenceStorageService $storage) {}

  public function lookupHasActiveFilters(Request $request): bool
  {
    $q = trim((string) $request->get('q', ''));
    $types = array_filter((array) $request->input('doc_types', []));

    return $q !== '' || $types !== [] || filled($request->get('issue_date'));
  }

  /** @return Collection<int, DocumentRecord> */
  public function lookupSearch(Request $request): Collection
  {
    if (! $this->lookupHasActiveFilters($request)) {
      return collect();
    }

    $query = DocumentRecord::query()->forLookupList();
    $types = array_filter((array) $request->input('doc_types', []));

    if ($types) {
      $query->whereIn('doc_type', $types);
    }

    if ($date = $request->get('issue_date')) {
      $query->where('issue_date', $date);
    }

    $q = trim((string) $request->get('q', ''));
    $scopes = $this->resolveLookupScopes($request);
    $mode = $request->get('mode', 'partial') === 'exact' ? 'exact' : 'partial';

    if ($q !== '' && ! str_contains($q, '|')) {
      $query->where(function (Builder $b) use ($q, $scopes, $mode) {
        foreach ($scopes as $i => $scope) {
          $this->applyLookupScope($b, $scope, $q, $mode, $i > 0);
        }
      });
    }

    $items = $query->orderByDesc('issue_date')->limit(500)->get()->load('createdByUser');

    if ($q !== '' && str_contains($q, '|')) {
      $items = $this->filterLookupPipeQuery($items, $q, $scopes, $mode, $request->get('logic', 'and'));
    }

    return $items->values();
  }

  public function filter(Request $request): Builder
  {
    $query = DocumentRecord::query();

    if ($q = trim((string) $request->get('q'))) {
      $query->where(function (Builder $b) use ($q) {
        $b->where('title', 'like', "%{$q}%")
          ->orWhere('doc_number', 'like', "%{$q}%")
          ->orWhere('doc_code', 'like', "%{$q}%")
          ->orWhere('abstract', 'like', "%{$q}%")
          ->orWhere('signer', 'like', "%{$q}%")
          ->orWhere('issuing_body', 'like', "%{$q}%")
          ->orWhere('extracted_text', 'like', "%{$q}%")
          ->orWhere('keywords', 'like', "%{$q}%");
      });
    }

    if ($type = $request->get('doc_type')) {
      $query->where('doc_type', $type);
    }

    if ($status = $request->get('status')) {
      $query->where('status', $status);
    }

    if ($date = $request->get('issue_date')) {
      $query->where('issue_date', $date);
    }

    $sort = $request->get('sort', 'issue_date');
    $dir = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';
    $allowed = ['issue_date', 'doc_number', 'title', 'status', 'created_at'];
    if (in_array($sort, $allowed, true)) {
      $query->orderBy($sort, $dir);
    } else {
      $query->orderByDesc('created_at');
    }

    return $query;
  }

  /** @return list<string> */
  private function resolveLookupScopes(Request $request): array
  {
    $scopes = array_values(array_filter((array) $request->input('scopes', [])));
    $allowed = ['doc_number', 'title', 'abstract', 'extracted_text', 'signer', 'issuing_body', 'original_file'];

    $scopes = array_values(array_intersect($scopes, $allowed));

    return $scopes !== [] ? $scopes : ['title'];
  }

  private function applyLookupScope(Builder $b, string $scope, string $q, string $mode, bool $or): void
  {
    $method = $or ? 'orWhere' : 'where';

    if ($scope === 'original_file') {
      $b->{$method}('original_file', 'like', '%'.$q.'%');

      return;
    }

    if ($scope === 'extracted_text') {
      if ($mode === 'exact') {
        $b->{$method.'Raw'}('LOWER(COALESCE(extracted_text, "")) = ?', [mb_strtolower($q)]);
      } else {
        $b->{$method}('extracted_text', 'like', '%'.$q.'%');
      }

      return;
    }

    if ($mode === 'exact') {
      $b->{$method}($scope, '=', $q);
    } else {
      $b->{$method}($scope, 'like', '%'.$q.'%');
    }
  }

  /** @param  Collection<int, DocumentRecord>  $items */
  private function filterLookupPipeQuery(Collection $items, string $q, array $scopes, string $mode, string $logic): Collection
  {
    $parts = array_map('trim', explode('|', mb_strtolower($q)));
    $ordered = ['doc_number', 'title', 'abstract', 'extracted_text', 'signer', 'issuing_body', 'original_file'];
    $active = array_values(array_intersect($ordered, $scopes));
    $useOr = $logic === 'or';

    return $items->filter(function (DocumentRecord $item) use ($parts, $active, $mode, $useOr) {
      $check = function (string $scope, int $index) use ($item, $parts, $mode, $useOr) {
        $part = $parts[$index] ?? '';
        if ($part === '') {
          return ! $useOr;
        }

        $value = mb_strtolower($this->lookupScopeValue($item, $scope));

        return $mode === 'exact' ? $value === $part : str_contains($value, $part);
      };

      if ($useOr) {
        foreach ($active as $i => $scope) {
          if ($check($scope, $i)) {
            return true;
          }
        }

        return false;
      }

      foreach ($active as $i => $scope) {
        if (! $check($scope, $i)) {
          return false;
        }
      }

      return true;
    })->values();
  }

  private function lookupScopeValue(DocumentRecord $item, string $scope): string
  {
    if ($scope === 'original_file') {
      $files = $this->storage->parse($item->original_file);

      return $files[0]['name'] ?? (string) $item->original_file;
    }

    if ($scope === 'extracted_text') {
      return (string) ($item->extracted_text ?? '');
    }

    return (string) ($item->{$scope} ?? '');
  }
}
