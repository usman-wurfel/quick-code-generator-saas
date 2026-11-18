<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface ModelSearchBuilder
{
    public function init($class, Request $request, $orderByIdOnPaginate = true): self;

    public function withQuery(callable $cp): self;

    public function inColumn(string $column): self;

    public function inColumns(array $searchableColumns = ['name']): self;

    public function search(): self;

    public function query(): Builder;

    public function paginate();
}
