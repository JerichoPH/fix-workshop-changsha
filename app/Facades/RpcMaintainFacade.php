<?php
namespace App\Facades;
use App\Services\RpcMaintainService;
use Illuminate\Support\Facades\Facade;

/**
 * Class RpcMaintainFacade
 * @package App\Facades
 * @method static init()
 * @method static total()
<<<<<<< HEAD
 * @method static sceneWorkshopWithAllCategory(string $sceneWorkshopUniqueCode)
=======
 * @method static sceneWorkshopWithAllCategory(string $sceneWorkshopUniqueCode, string $status = null)
>>>>>>> b36730d46746183d0f7ee7c44cde984da7d9e3e0
 * @method static sceneWorkshop(string $sceneWorkshopUniqueCode)
 */
class RpcMaintainFacade extends Facade{
    protected static function getFacadeAccessor()
    {
        return RpcMaintainService::class;
    }
}
