<?php

namespace App\Services\SmartValidation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SmartValidationService
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @return array{blocking: array<string, string>, warnings: array<int, string>}
     */
    public function validate(array $data, array $rules, ?Model $currentRecord = null): array
    {
        $blocking = [];
        $warnings = [];

        foreach (Arr::get($rules, 'blocking', []) as $rule) {
            if ($this->matchesRule($rule, $data, $currentRecord)) {
                $field = $rule['field'] ?? 'general';
                $blocking[$field] = $rule['message'] ?? 'Pronađen je duplikat.';
            }
        }

        foreach (Arr::get($rules, 'warnings', []) as $rule) {
            if ($this->matchesRule($rule, $data, $currentRecord)) {
                $warnings[] = $rule['message'] ?? 'Pronađen je mogući duplikat.';
            }
        }

        return [
            'blocking' => $blocking,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param array<string, mixed> $rule
     * @param array<string, mixed> $data
     */
    protected function matchesRule(array $rule, array $data, ?Model $currentRecord = null): bool
    {
        $modelClass = $rule['model'] ?? null;

        if (! $modelClass || ! class_exists($modelClass)) {
            return false;
        }

        if (isset($rule['when']) && is_callable($rule['when'])) {
            $shouldRun = $rule['when']($data, $currentRecord);

            if (! $shouldRun) {
                return false;
            }
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $modelClass::query();

        if (($rule['without_trashed'] ?? true) && $this->usesSoftDeletes($modelClass)) {
            $deletedAtColumn = (new $modelClass())->getDeletedAtColumn();
            $query->whereNull($deletedAtColumn);
        }

        if (($rule['scope_to_user'] ?? false) && ! Auth::user()?->isAdmin()) {
            $userColumn = $rule['user_column'] ?? 'user_id';

            if ($this->hasColumn($modelClass, $userColumn)) {
                $query->where($userColumn, Auth::id());
            }
        }

        if ($currentRecord && $currentRecord::class === $modelClass) {
            $query->whereKeyNot($currentRecord->getKey());
        }

        if (isset($rule['query']) && is_callable($rule['query'])) {
            $rule['query']($query, $data, $currentRecord);

            return $query->exists();
        }

        if (isset($rule['column'])) {
            $field = $rule['field'] ?? $rule['column'];
            $column = $rule['column'];
            $value = data_get($data, $field);

            if (blank($value)) {
                return false;
            }

            $query->where($column, $value);

            return $query->exists();
        }

        if (isset($rule['pairs']) && is_array($rule['pairs'])) {
            foreach ($rule['pairs'] as $field => $column) {
                $value = data_get($data, $field);

                if (blank($value)) {
                    return false;
                }

                $query->where($column, $value);
            }

            return $query->exists();
        }

        if (isset($rule['fields']) && is_array($rule['fields'])) {
            foreach ($rule['fields'] as $field) {
                $value = data_get($data, $field);

                if (blank($value)) {
                    return false;
                }

                $query->where($field, $value);
            }

            return $query->exists();
        }

        return false;
    }

    protected function hasColumn(string $modelClass, string $column): bool
    {
        $model = new $modelClass();

        return Schema::hasColumn($model->getTable(), $column);
    }

    protected function usesSoftDeletes(string $modelClass): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass), true);
    }
}