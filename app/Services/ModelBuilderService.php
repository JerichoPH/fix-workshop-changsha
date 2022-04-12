<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModelBuilderService
{
    private $_filterExcepts = [];
    private $_request = null;
    private $_builder = null;

    public static function init(Request $request, Builder $builder, array $filterExcepts = [])
    {
        $ins = new self();
        $ins->_request = $request;
        $ins->_builder = $builder;
        $ins->_filterExcepts = array_merge(['id', 'nonce', 'files', 'file', 'limit', 'page', 'size', 'ordering', 'timestamp'], $filterExcepts);
        $ins->filter();  # 筛选
        $ins->ordering();  # 排序
        return $ins;
    }

    /**
     * 根据规则自动生成筛选条件
     */
    final private function filter()
    {
        if ($this->_request->except($this->_filterExcepts)) {
            foreach ($this->_request->except($this->_filterExcepts) as $fieldName => $condition) {
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
     * 联合查询
     * @param \Illuminate\Database\Query\Builder $builder1
     * @param \Illuminate\Database\Query\Builder $builder2
     * @return \Illuminate\Database\Query\Builder
     */
    final public static function unionAll(\Illuminate\Database\Query\Builder $builder1, \Illuminate\Database\Query\Builder $builder2): \Illuminate\Database\Query\Builder
    {
        return DB::table(DB::raw("({$builder1->unionAll($builder2)->toSql()}) as tmp_union_all"))->mergeBindings($builder1);
    }

    /**
     * 额外参数
     * @param \Closure $extension_func
     * @return $this
     */
    final public function extension(\Closure $extension_func): self
    {
        if ($extension_func) $this->_builder = $extension_func($this->_builder);
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
    public function first()
    {
        return $this->_builder->first();
    }

    /**
     * 获取一条数据（抛出异常）
     * @return mixed
     */
    public function firstOrFail()
    {
        return $this->_builder->firstOrFail();
    }

    /**
     * 获取SQL语句
     * @return mixed
     */
    public function toSql()
    {
        return $this->_builder->toSql();
    }

    /**
     * 获取多条数据的SQL日志
     * @return mixed
     */
    public function getManySql()
    {
        DB::connection()->enableQueryLog();
        $this->_builder->get();
        return DB::getQueryLog();
    }

    /**
     * 获取单条数据的SQL日志
     * @return mixed
     */
    public function firstSql()
    {
        DB::connection()->enableQueryLog();
        $this->_builder->first();
        return DB::getQueryLog();
    }
}
