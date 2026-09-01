<?php

namespace App\Traits;

use Carbon\Carbon;

trait Filterable
{
    public function scopeFilterByDateRange($query, $startDate = null, $endDate = null, $column = 'created_at')
    {
        if (!$startDate && !$endDate) return $query;

        if ($startDate && $endDate) {
            return $query->whereBetween($column, [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        if ($startDate) {
            return $query->where($column, '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            return $query->where($column, '<=', Carbon::parse($endDate)->endOfDay());
        }

        return $query;
    }

    public function scopeFilterByMonth($query, $month = null, $year = null, $column = 'created_at')
    {
        if (!$month) return $query;

        $year = $year ?? date('Y');

        return $query->whereYear($column, $year)
                    ->whereMonth($column, $month);
    }

    public function scopeFilterByStatus($query, $status = null)
    {
        if (!$status) return $query;

        return $query->where('status', $status);
    }

    public function scopeFilterByUser($query, $userId = null)
    {
        if (!$userId) return $query;

        return $query->where('user_id', $userId);
    }

    public function scopeSearch($query, $search = null, $columns = ['name'])
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    public function scopeSearchWithRelation($query, $relation, $column, $search = null)
    {
        if (!$search) return $query;

        return $query->whereHas($relation, function ($q) use ($column, $search) {
            $q->where($column, 'LIKE', "%{$search}%");
        });
    }

    public function scopeAutocomplete($query, $search = null, $columns = ['name'], $limit = 10)
    {
        if (!$search) return $query->limit($limit);

        return $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        })->limit($limit);
    }

    public function scopeFilterByActive($query, $active = null)
    {
        if (is_null($active)) return $query;

        return $query->where('status', $active ? 'active' : 'inactive');
    }

    public function scopeApplyFilters($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            $method = 'filterBy' . str_replace('_', '', ucwords($key, '_'));

            if (method_exists($this, 'scope' . $method)) {
                $query->{$method}($value);
            }
        }

        return $query;
    }
}