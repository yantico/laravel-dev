<?php

namespace LaravelDev\App\Helpers;

use Closure;
use Exception;
use Generator;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use LaravelDev\App\Exceptions\Err;
use Rap2hpoutre\FastExcel\FastExcel;
use SplFileInfo;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FastExcelHelper
{
    /**
     * @param QueryBuilder|EloquentBuilder $query
     * @param string $filename
     * @param Closure|null $callback
     * @param string $ext
     * @return string|StreamedResponse|void
     * @throws Err
     */
    public static function Export(QueryBuilder|EloquentBuilder $query, string $filename, Closure $callback = null, $ext = 'csv')
    {
        try {
            function generator($q): Generator
            {
                foreach ($q->cursor() as $item) {
                    yield $item;
                }
            }

            $id = uniqid();
            return (new FastExcel(generator($query)))->download("{$filename}_$id.$ext", $callback);
        } catch (Exception $exception) {
            ee('导出失败：' . $exception->getMessage());
        }
    }

    /**
     * @param SplFileInfo $file
     * @param Closure $closure
     * @return void
     * @throws Err
     */
    public static function Import(SplFileInfo $file, Closure $closure): void
    {
        $i = 2;

        try {
            DB::beginTransaction();

            (new FastExcel)->import($file->getRealPath(), function ($row) use ($closure) {
                $closure($row);
            });

            $i++;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            ee("第{$i}行数据处理错误，导入全部撤回，错误信息:" . $e->getMessage());
        }
    }
}
