<?php

namespace Modules\Crud\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CrudController extends Controller
{
    protected $crudMiddleware = [];

    public function __construct()
    {
        $this->middleware($this->crudMiddleware);
    }

    /**
     * models
     * @param
     * @return list of models in code
     * @var
     */
    public function models()
    {
        return modelesName();
    }

    /**
     * store
     * @param model params
     * validate data from model Validate
     * @return instance
     * @var
     */
    public function store($model, Request $r)
    {
        $model = $this->getModel($model);
        $r->validate($model->validate);
        if (is_null($model)) return response()->json([
            'message' => $model . __('messages.not_exist')
        ], 400);
        $data = $r->only($model->getFillable());
        $result = $model::create($data);
        return Response()->json($result, 200);
    }

    /**
     * @param name of model , filters
     * validate data from model Validate
     * @return paginated data
     * @var
     */
    public function list($modelName)
    {

        $filters = \request()->has("filters") ? \request("filters") : null;
        $relations = \request()->has("relations") ? \request("relations") : null;
        $page_number = \request()->has("page_number") ? \request("page_number") : 1;
        $page_limit = \request()->has("page_limit") ? \request("page_limit") : 10;

        $model = $this->getModel($modelName);
        $filters = json_decode($filters, true);
        $relations = json_decode($relations, true);

        if (!empty($filters) && !empty($relations)) {
            $result = $this->checkFilter($filters, $model, $relations);
            if (!is_null($result)) {
                if (\request()->has("page_limit") || \request()->has("page_number")) {
                    return Response()->json(['data' => $result->paginate($page_limit, $page_number)], 200);
                } else {
                    return Response()->json(['data' => $result], 200);
                }
            } else {
                return Response()->json(null, 400);
            }
        } elseif ($filters) {
            $result = $this->checkFilter($filters, $model, $relations);
            if (!is_null($result)) {
                if (\request()->has("page_limit") || \request()->has("page_number")) {
                    return Response()->json(['data' => $result->paginate($page_limit, $page_number)], 200);
                } else {
                    return Response()->json(['data' => $result], 200);
                }
            } else {
                return Response()->json(null, 400);
            }
        } elseif ($relations) {
            if (\request()->has("page_limit") || \request()->has("page_number")) {
                return Response()->json([
                    'data' => $model::with($relations)->paginate()
                ], 200);
            } else {
                return Response()->json([
                    'data' => $model::with($relations)->get()
                ], 200);
            }

        } else {
            if (\request()->has("page_limit") || \request()->has("page_number")) {
                return Response()->json([
                    'data' => $model->paginate($page_limit, ["*"], 'page', $page_number)
                ], 200);
            } else {
                return Response()->json([
                    'data' => $model->get()
                ], 200);
            }
        }
    }

    /**
     * show
     * @param name of model , id
     * validate data from model Validate
     * @return data
     * @var
     */
    public function show($modelName, $id, Request $r)
    {
        $result = $this->getModel($modelName)->where('id', $id)->get();
        if (is_null($result)) {
            return response()->json([
                'message' => $modelName . __('messages.not_exist')
            ], 400);
        }
        return Response()->json(['data' => $result], 200);
    }

    /**
     * delete
     * @param name of model , id in url
     * @return 204 response
     * @var
     */
    public function destroy($modelName, $id)
    {
        $model = $this->getModel($modelName);
        $response = $model->find($id);
        if (is_null($response)) return response()->json(['id (' . $id . ') dose not exist']);
        $response->delete();
        return response()->json([
            'message' => $id . __('messages.delete')
        ], 204);
    }

    /**
     * update
     * @param name of model , id in url
     * @return 201 response
     * @var
     */
    public function update($modelName, $id, Request $r)
    {
        $model = $this->getModel($modelName);
        $data = $r->only($model->getFillable());
        $response = $model->find($id);
        if (is_null($response)) return response()->json('id (' . $id . ') dose not exist', 400);
        $response->update($data);
        return response()->json([
            'message' => $id . __('messages.update')
        ], 201);

    }

    /**
     * @param array $filters
     * @param $model
     * @param null $relations
     * @return \Illuminate\Support\Collection
     */
    public function checkFilter(array $filters, $model, $relations = null)
    {
        $result = [];

        $page_limit = \request()->has("page_limit") ? \request("page_limit") : 10;
        $page_number = \request()->has("page_number") ? \request("page_number") : 1;

        foreach ($filters as $key) {
            array_push($result, $this->doFilters($key, $model, $relations));
        }
        return collect($result);


    }

    /**
     * do filter
     * do multi filter on query(model)
     * @param filters(array), model, request (for paginate) , relations (if exist in request)
     * @return paginated data
     * @var
     */
    public function doFilters(array $filter, $model, $relations = null)
    {
        $rels = [$relations];
        foreach ($filter as $key) {
            if (strpos($key[0], ".")) {
                $exploded = explode('.', $key[0]);
                $relFilter = [last($exploded), $key[1], $key[2]];
                unset($exploded[array_key_last($exploded)]);
                $relModel = implode($exploded, ".");
                if (is_null($rels[0][0])) {
                    $relation = [$relModel];
                } else {
                    $relation = array_merge($rels[0], [$relModel]);
                }

                $model = $model->whereHas($relModel, function ($q) use ($relFilter) {
                    $q->where($relFilter[0], $relFilter[1], $relFilter[2]);
                })->with($relation);
            } elseif (!strpos($key[0], ".")) {

                if ($rels[0] == null) {
                    $model = $model->where($key[0], $key[1], $key[2]);
                } else {
                    $model = $model->where($key[0], $key[1], $key[2])->with($rels[0]);
                }
            }
        }
        return $model->get();
    }

    /**
     * check model exist
     * @param name of model
     * @return builder of model if exist
     * @var
     */
    public function getModel($model)
    {
        $model = uc($model);
        if (!is_model($model)) return null;
        $module = getNameSpaceWithModelName($model);
        $abs = $module ? "Modules\\$module\Entities\\$model" : "App\\$model";
        return app()->make($abs);
    }

    /**
     * check relation in filter
     * @param $filter row
     * @return boolean
     * @var
     */
    public function hasRelation($filter): bool
    {
        $rell = explode('.', $filter[0]);
        if (count($rell) == 2) {
            return true;
        } else {
            return false;
        }
    }

}
