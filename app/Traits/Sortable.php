<?php 

namespace App\Traits;

trait Sortable
{
    public function scopeSortBy($query, $column = null, $direction = 'desc')
    {
        if (!$column) return $query;

        $allowedColumns = $this->getSortableColumns();
        
        if (!in_array($column, $allowedColumns)) {
            return $query;
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        
        return $query->orderBy($column, $direction);
    }

    protected function getSortableColumns(): array
    {
        return $this->sortable ?? ['id', 'created_at', 'updated_at'];
    }
}