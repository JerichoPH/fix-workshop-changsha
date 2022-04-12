<?php

namespace App\Services;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryBuilderService
{
    private $_filter_excepts = [];
    private $_request = null;
    private $_builder = null;

    /**
     * 初始化
     * @param Request $request
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array $filterExcepts
     * @return ModelBuilderService
     */
    public static function init(Request $request, Builder $builder, array $filterExcepts = []): ModelBuilderService
    {
        $ins = new self();
        $ins->_request = $request;
        $ins->_builder = $builder;
        $ins->_filter_excepts = array_merge(['id', 'nonce', 'files', 'file', 'limit', 'page', 'size', 'ordering', 'timestamp'], $filterExcepts);
        $ins->filter();  # 筛选
        $ins->ordering();  # 排序
        return $ins;
    }

    /**
     * 根据规则自动生成筛选条件
     */
    final private function filter()
    {
        $params = array_filter($this->_request->except($this->_filter_excepts), function ($val) {
            return !empty($val);
        });
        if ($params) {
            foreach ($params as $fieldName => $condition) {
                $this->_builder->when($fieldName, function ($query) use ($fieldName, $condition) {
                    return self::condition($query, $fieldName, $condition);
                });
            }
        }
        $this->_builder->when($this->_request->get('limit'), function ($query) {
            return $query->limit($this->_request->get('limit'));
        });
    }

    /**
     * 生成条件
     * @param $query
     * @param $fieldName
     * @param $condition
     * @return mixed
     */
    final private function condition($query, $fieldName, $condition)
    {
        if (is_array($condition)) {
            switch (strtolower($condition['operator'])) {
                case 'in':
                    return $query->whereIn($fieldName, $condition['value']);
                case 'or':
                    return $query->orWhere($fieldName, $condition['value']);
                case 'between':
                    return $query->whereBetween($fieldName, $condition['value']);
                case 'like_l':
                    return $query->where($fieldName, 'like', "%{$condition['value']}");
                case 'like_r':
                    return $query->where($fieldName, 'like', "{$condition['value']}%");
                case 'like_b':
                    return $query->where($fieldName, 'like', "%{$condition['value']}%");
                default:
                    return $query->where($fieldName, $condition['operator'], $condition['value']);
            }
        } else {
            return $query->where($fieldName, $condition);
        }
    }

    /**
     * 排序
     */
    final private function ordering()
    {
        if ($this->_request->get('ordering'))
            $this->_builder->orderByRaw($this->_request->get('ordering'));
    }

    /**
     * 额外参数
     * @param \Closure $extension_func
     * @return $this
     */
    final public function extension(\Closure $extension_func): self
    {
        if ($extension_func)
            $this->_builder = $extension_func($this->_builder) ?: $this->_builder;
        return $this;
    }

    /**
     * 获取多条数据
     * @return mixed
     */
    final public function all()
    {
        return $this->_request->get('page') ?
            $this->_builder->paginate($this->_request->get('size', env('PAGE_SIZE'))) :
            $this->_builder->get();
    }

    /**
     * 获取分页
     * @return mixed
     */
    final public function pagination()
    {
        return $this->_builder->paginate($this->_request->get('size', env('PAGE_SIZE')));
    }

    /**
     * 获取一条数据
     * @return mixed
     */
    final public function first()
    {
        return $this->_builder->first();
    }

    /**
     * 获取一条数据（抛出异常）
     * @return mixed
     */
    final public function firstOrFail()
    {
        return $this->_builder->firstOrFail();
    }

    /**
     * 获取SQL语句
     * @return mixed
     */
    final public function toSql()
    {
        DB::connection()->enableQueryLog();
        $this->_builder->get();
        return DB::getQueryLog();
    }

    /**
     * 获取单条数据的SQL日志
     * @return mixed
     */
    final public function firstSql()
    {
        DB::connection()->enableQueryLog();
        $this->_builder->first();
        return DB::getQueryLog();
    }

    /**
     *  获取SQL语句
     * @param Closure $closure
     * @return array
     */
    final public static function sqlLanguage(Closure $closure): array
    {
        DB::connection()->enableQueryLog();
        $closure();
        return DB::getQueryLog();
    }

    /**
     * 联合查询
     * @param Builder $builder1
     * @param Builder $builder2
     * @return Builder
     */
    final public static function unionAll(Builder $builder1, Builder $builder2): Builder
    {
        return DB::table(DB::raw("({$builder1->unionAll($builder2)->toSql()}) as tmp_union_all"))->mergeBindings($builder1);
    }

    /**
     * 获取联合查询语句
     * @param Builder $builder1
     * @param Builder $builder2
     * @return array
     */
    final public static function unionAllToSql(Builder $builder1, Builder $builder2): array
    {
        DB::connection()->enableQueryLog();
        DB::table(DB::raw("({$builder1->unionAll($builder2)->toSql()}) as tmp_union_all"))->mergeBindings($builder1)->get();
        return DB::getQueryLog();
    }

}
