<?php

namespace App\Facades;

use App\Services\QueryBuilderService;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class QueryBuilderFacade
 * @package App\Facades
 * @method static init(Request $request, Builder $builder, array $filterExcepts = [])
 * @method all()
 * @method pagination()
 * @method first()
 * @method firstOrFail()
 * @method toSql()
 * @method firstSql()
 * @method extension(Closure $extension_func): self
 * @method sqlLanguage(Closure $closure): array
 * @method static unionAll(Builder $builder1, Builder $builder2): Builder
 * @method static unionAllToSql(Builder $builder1, Builder $builder2): array
 */
class QueryBuilderFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QueryBuilderService::class;
    }

}
