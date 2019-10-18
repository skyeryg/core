<?php
namespace Apiato\Core\Criteria;

use App\Ship\Parents\Criterias\Criteria;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Prettus\Repository\Contracts\RepositoryInterface;

class RequestCriteria extends Criteria
{
    /**
     * @var Request
     */
    protected $request;
    protected $wheres = ['in', 'date', 'between'];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    /**
     * Apply criteria in query repository
     *
     * @param         Builder|Model     $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     * @throws Exception
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $fieldsSearchable = $repository->getFieldsSearchable();
        $search = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $orderBy = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);

        $filter = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $with = $this->request->get(config('repository.criteria.params.with', 'with'), null);

        if ($search && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            list($fields, $scopes) = $this->parserFieldsSearch($fieldsSearchable, $search);

            foreach ($scopes as $field => $value) {
                $scope = Str::camel($field);
                $model = $model->$scope($value);
            }

            $model = $model->where(function ($query) use ($fields) {
                /** @var Builder $query */
                foreach ($fields as $field => ['value' => $value, 'condition' => $condition]) {
                    $value = ($condition === 'like' || $condition === 'ilike') ? "%{$value}%" : $value;
                    $relation = null;
                    if(stripos($field, '.')) {
                        $explode = explode('.', $field);
                        $field = array_pop($explode);
                        $relation = implode('.', $explode);
                    }
                    $modelTableName = $query->getModel()->getTable();
                    if(!is_null($relation)) {
                        $query->whereHas($relation, function($query) use($field,$condition,$value) {
                            if (in_array($condition, $this->wheres)) {
                                $where = 'where' . ucfirst($condition);
                                $query->$where($field, $value);
                            } else {
                                $query->where($field,$condition,$value);
                            }
                        });
                    } else {
                        if (in_array($condition, $this->wheres)) {
                            $where = 'where' . ucfirst($condition);
                            $query->$where($modelTableName.'.'.$field,$value);
                        } else {
                            $query->where($modelTableName.'.'.$field,$condition,$value);
                        }
                    }
                }
            });
        }

        if (isset($orderBy) && !empty($orderBy) && Str::startsWith($orderBy, ['+', '-'])) {
            $sortedBy = Str::startsWith($orderBy, '+') ? 'asc' : 'desc';
            $column = substr($orderBy, 1);
            $model = $model->orderBy($column, $sortedBy);
        }

        if (isset($filter) && !empty($filter)) {
            if (is_string($filter)) {
                $filter = explode(',', $filter);
            }

            $model = $model->select($filter);
        }

        if ($with) {
            $with = explode(',', $with);
            $model = $model->with($with);
        }

        return $model;
    }

    protected function parserFieldsSearch(array $fieldSearchable = [], array $search = null)
    {
        $fields = $scopes = [];

        if (!is_null($search) && count($search)) {
            $searchFields = [];
            foreach ($search as $field => $value) {
                $values = explode(',', $value);
                if (count($values) == 1) {
                    $searchFields[$field] = $value;
                } else {
                    $searchFields[$field] = $values;
                }
            }

            foreach ($fieldSearchable as $field => $condition) {
                if (is_numeric($field)) {
                    $field = $condition;
                    $condition = false;
                }
                if (!key_exists($field, $searchFields)) {
                    continue;
                }
                if (!$condition && is_array($searchFields[$field])) {
                    $condition = 'in';
                } else {
                    $condition = '=';
                }
                if ($condition === 'scope') {
                    $scopes[$field] = $searchFields[$field];
                } else {
                    $fields[$field] = [
                        'condition' => $condition,
                        'value' => $searchFields[$field]
                    ];
                }
            }
        }

        return [
            $fields,
            $scopes,
        ];
    }
}
