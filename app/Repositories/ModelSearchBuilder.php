<?php

namespace App\Repositories;

use App\Interfaces\ModelSearchBuilder as ModelSearchBuilderInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModelSearchBuilder implements ModelSearchBuilderInterface
{
    protected Request $request;

    protected Builder $query;

    protected array $searchableColumns = [];

    protected bool $orderByIdOnPaginate = true;

    public function init($class, Request $request, $orderByIdOnPaginate = true): self
    {
        // $instance = new static;

        $this->request = $request;

        $this->query = $class::query();

        $this->orderByIdOnPaginate = $orderByIdOnPaginate;

        return $this;
    }

    public function withQuery(callable $cp): self
    {
        $cp($this->query);

        return $this;
    }

    public function inColumn(string $column): self
    {
        return $this->inColumns([$column]);
    }

    public function inColumns($searchableColumns =  ['name']): self
    {
        $this->searchableColumns = array_merge($this->searchableColumns, $searchableColumns);

        return $this;
    }

    public function paginate()
    {
        if ($this->orderByIdOnPaginate)
            $this->query->orderBy('id', 'desc');

        $paginated = $this->query->paginate(10);

        if (!empty($this->request->path)) {
            // Because intelephense doesn't like the function $paginated->withPath
            call_user_func_array([$paginated, 'withPath'], [$this->request->path]);
        }

        return $paginated;
    }

    public function search(): self
    {
        if (empty($this->request->keyword)) {
            return $this;
        }

        foreach ($this->searchableColumns as $i => $column) {

            $key = DB::raw("lower($column)");

            $value = '%' . strtolower($this->request->keyword) . '%';

            if (preg_match('/\./', $column)) {
                $parts = explode('.', $column);
                $relation = implode('.', array_slice($parts, 0, count($parts) - 1));
                $field = $parts[count($parts) - 1];
                $key = DB::raw("lower($field)");

                if ($i === 0)
                    $this->query->whereHas($relation, function ($query) use ($key, $value) {
                        $query->where($key, 'like', $value);
                    });
                else
                    $this->query->orWhereHas($relation, function ($query) use ($key, $value) {
                        $query->where($key, 'like', $value);
                    });
            } else {

                if ($i === 0)
                    $this->query->where($key, 'like', $value);
                else
                    $this->query->orWhere($key, 'like', $value);
            }
        }

        return $this;
    }

    public function query(): Builder
    {
        return $this->query;
    }
}
