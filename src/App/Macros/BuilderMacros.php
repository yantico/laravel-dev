<?php

namespace LaravelDev\App\Macros;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use LaravelDev\App\Exceptions\ErrConst;

class BuilderMacros
{
    /**
     * @return void
     */
    public static function boot(): void
    {
        $_ifWhere = fn(array $params, string $key, ?string $field = null) => $this->when(array_key_exists($key, $params) && $params[$key] !== null && $params[$key] !== '', fn($q) => $q->where($field ?? $key, $params[$key]));

        $_ifWhereLike = fn(array $params, string $key, ?string $field = null) => $this->when(array_key_exists($key, $params) && $params[$key] !== null && $params[$key] !== '', fn($q) => $q->where($field ?? $key, 'like', "%$params[$key]%"));

        $_ifWhereLikeKeyword = fn(array $params, string $key, array $fields) => $this->when(array_key_exists($key, $params) && $params[$key] !== null && $params[$key] !== '',
            fn() => $this->where(function ($q) use ($params, $key, $fields) {
                foreach ($fields as $field)
                    $q->orWhere($field, 'like', "%$params[$key]%");
            }));

        $_ifWhereNumberRange = function (array $params, string $key, ?string $field = null) {
            if (!isset($params[$key]))
                return $this;

            $dataRange = $params[$key];
            if (count($dataRange) != 2)
                ee("{$key}参数必须是两个值");

            $start = $dataRange[0] ?? null;
            $end = $dataRange[1] ?? null;

            if ($start && !$end)
                return $this->where($field ?? $key, '>=', $start);
            if (!$start && $end)
                return $this->where($field ?? $key, '<=', $end);
            else
                return $this->whereBetween($field ?? $key, [$start, $end]);
        };

        $_ifWhereDateRange = function (array $params, string $key, ?string $field = null, ?string $type = 'datetime') {
            if (!isset($params[$key]))
                return $this;

            if (count($params[$key]) == 0)
                return $this;

            $range = $params[$key];
            if (count($range) != 2)
                ee("{$key}参数必须是两个值");

            $start = $range[0] == '' || $range[0] == null ? null : Carbon::parse($range[0]);
            $end = $range[1] == '' || $range[1] == null ? null : Carbon::parse($range[1]);

            $start = $start ? ($type == 'date' ? $start->toDateString() : $start->startOfDay()->toDateTimeString()) : null;
            $end = $end ? ($type == 'date' ? $end->toDateString() : $end->endOfDay()->toDateTimeString()) : null;

            $field = $field ?? $key;
            if ($start && !$end)
                return $this->where($field, '>=', $start);
            if (!$start && $end)
                return $this->where($field, '<=', $end);
            else
                return $this->whereBetween($field, [$start, $end]);
        };

        $_ifHasWhereLike = function (array $params, string $key, string $relation, ?string $field = null) {
            return $this->when(array_key_exists($key, $params) && $params[$key] !== '', function (QueryBuilder|EloquentBuilder $q) use ($params, $key, $relation, $field) {
                return $q->whereHas($relation, function ($q1) use ($params, $key, $field) {
                    return $q1->where($field ?? $key, 'like', "%$params[$key]%");
                });
            });
        };

        $_order = function (?string $key = 'sorter', ?string $defaultField = 'id') {
            $params = request()->validate([$key => 'nullable|array']);
            if ($params[$key] ?? false) {
                $orderBy = $params[$key];
                if (count($orderBy) == 2) {
                    $field = $orderBy[0];
                    $sort = $orderBy[1] == 'descend' ? 'desc' : 'asc';
                    return $this->orderBy($field, $sort);
                }
            }
            return $this->orderByDesc($defaultField);
        };

        $_page = function () {
            $perPage = request()->validate(['perPage' => 'nullable|integer',])['perPage'] ?? 10;
            $allow = config('project.perPageAllow', [10, 20, 50, 100]);
            if (!in_array($perPage, $allow))
                ee(...ErrConst::PerPageIsNotAllow);
            return $this->paginate($perPage);
        };

        $_forSelect = fn(?string $key1 = 'id', ?string $key2 = 'name', ?string $orderByDesc = 'id') => $this->selectRaw("$key1, $key2")->orderByDesc($orderByDesc)->get();

        $_unique = function (array $params, array $keys, string $label = null, string $field = 'id', ?int $keyIndex = 0) {
            $model = $this->where(Arr::only($params, $keys))->first();
            if ($model && $label != null) {
                if (!isset($params[$field]) || $model->$field != $params[$field])
                    ee("{$label}【{$params[$keys[$keyIndex]]}】已存在，请重试");
            }
            return $this;
        };

        $_getById = function (int $id, ?bool $throw = true, ?bool $lock = false, ?string $msg = null) {
            $model = $this->when($lock, fn($q) => $q->lockForUpdate())->find($id);
            if (!$model && $throw) {
                $comment = $this->comment ?? '数据';
                ee($msg ?? "{$comment}不存在（{$id}）");
            }
            return $model;
//            return $this->when($lock, fn($q) => $q->lockForUpdate())->when($throw, fn($q) => $q->findOrFail($id), fn($q) => $q->find($id));
        };

        $_ifIsNull = function (array $params, string $key, ?string $field = null) {
            $field = $field ?? $key;
            return $this->when(array_key_exists($key, $params), fn($q) => $q->when($params[$key], fn($q) => $q->whereNull($field), fn($q) => $q->whereNotNull($field)));
        };

        $_ifIsNotNull = function (array $params, string $key, ?string $field = null) {
            $field = $field ?? $key;
            return $this->when(array_key_exists($key, $params), fn($q) => $q->when($params[$key], fn($q) => $q->whereNotNull($field), fn($q) => $q->whereNull($field)));
        };

        QueryBuilder::macro('ifWhere', $_ifWhere);
        QueryBuilder::macro('ifWhereLike', $_ifWhereLike);
        QueryBuilder::macro('ifWhereLikeKeyword', $_ifWhereLikeKeyword);
        QueryBuilder::macro('ifWhereNumberRange', $_ifWhereNumberRange);
        QueryBuilder::macro('ifWhereDateRange', $_ifWhereDateRange);
        QueryBuilder::macro('order', $_order);
        QueryBuilder::macro('unique', $_unique);
        QueryBuilder::macro('ifIsNull', $_ifIsNull);
        QueryBuilder::macro('ifIsNotNull', $_ifIsNotNull);

        EloquentBuilder::macro('forSelect', $_forSelect);
        EloquentBuilder::macro('page', $_page);
        EloquentBuilder::macro('getById', $_getById);
        EloquentBuilder::macro('ifHasWhereLike', $_ifHasWhereLike);
    }
}
