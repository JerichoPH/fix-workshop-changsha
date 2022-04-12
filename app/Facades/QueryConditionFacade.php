<?php

namespace App\Facades;

use App\Services\QueryConditionService;
use Illuminate\Support\Facades\Facade;

/**
 * Class QueryFacade
 * @package App\Facades
 * @method static init(string $root_dir): self
 * @method static setCategories(array $categories): self
 * @method static setCategoriesWithFile(array $filename): self
 * @method static setCategoriesWithDB(): self
 * @method static setCategory(string $category_unique_code): self
 * @method static setEntireModels(array $entireModels): self
 * @method static setEntireModelsWithFile(array $filename): self
 * @method static setEntireModelsWithDB(): self
 * @method static setEntireModel(string $entire_model_unique_code): self
 * @method static setSubModels(array $sub_models): self
 * @method static setSubModelsWithFile(array $filename): self
 * @method static setSubModelsWithDB(): self
 * @method static setStatus(array $status = []): self
 * @method static setSubModel(string $sub_model_unique_code): self
 * @method static setFactories(array $factories): self
 * @method static setFactoriesWithFile(array $filename): self
 * @method static setStorehouses(): self
 * @method static setAreas(): self
 * @method static setPlatoons(): self
 * @method static setShelves(): self
 * @method static setTiers(): self
 * @method static make(string $category_unique_code = "", string $entire_model_unique_code = "", string $sub_model_unique_code = "", string $factory_name = "", string $factory_unique_code = "", string $scene_workshop_unique_code = "", string $station_name = "", string $status_unique_code = "", string $storehouse_unique_code = "", string $area_unique_code = "", string $platoon_unique_code = "", string $shelf_unique_code = "", string $tier_unique_code = "", string $position_unique_code = "", string $line_unique_code=""): self
 * @method static get(string $name = null)
 * @method static toJson(string $name = null, $option = 256): string
 */
class QueryConditionFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return QueryConditionService::class;
    }
}
