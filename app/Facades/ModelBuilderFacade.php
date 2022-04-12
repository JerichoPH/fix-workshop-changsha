<?php

namespace App\Facades;

use App\Services\ModelBuilderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class SqlFacade
 * @package App\Facades
 * @method static init(Request $request, Builder $builder, array $filterExcepts = [])
 * @method all()
 * @method pagination()
 * @method first()
 * @method firstOrFail()
 * @method toSql()
 * @method getManySql()
 * @method firstSql()
 * @method extension(\Closure $extension_func): self
 * @method static unionAll(\Illuminate\Database\Query\Builder $builder1, \Illuminate\Database\Query\Builder $builder2): \Illuminate\Database\Query\Builder
 */
class ModelBuilderFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ModelBuilderService::class;
    }
}
