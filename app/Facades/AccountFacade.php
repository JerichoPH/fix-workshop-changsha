<?php

namespace App\Facades;

use App\Services\AccountService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * Class AccountFacade
 * @package App\Facades
 * @method static getWorkArea()
 * @method static workAreaWithDb(Builder $db, string $work_area = ''): Builder
 * @method static downloadUploadCreateAccountBySceneExcelTemplate()
 * @method static uploadCreateAccountByScene(Request $request)
 * @method static downloadUploadCreateAccountByParagraphExcelTemplate()
 * @method static uploadCreateAccountByParagraph(Request $request)
 */
class  AccountFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AccountService::class;
    }
}
