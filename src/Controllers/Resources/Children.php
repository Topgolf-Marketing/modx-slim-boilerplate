<?php

namespace MODXSlim\Api\Controllers\Resources;

use MODX\Revolution\modResource;
use MODXSlim\Api\Exceptions\RestfulException;
use MODXSlim\Api\Traits\TemplateVariables;
use MODXSlim\Api\Transformers\ResourceTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MODXSlim\Api\Controllers\Restful;

class Children extends Restful
{
    use TemplateVariables;
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws RestfulException
     */
    public function get(ServerRequestInterface $request): ResponseInterface
    {
        $defaultParams = ['tvs' => null, 'ignoreMenu' => false, 'sortBy' => 'menuindex', 'sortDir' => 'ASC', 'page' => 1, 'limit' => 10];
        $paramsCast = ['ignoreMenu' => 'boolean'];
        $paramLimits = [
            'limit' => [
                'min' => 1,
                'max' => 10,
            ],
            'page' => [
                'min' => 1,
            ],
        ];
        $params = $this->getParams($request, $defaultParams, $paramsCast, $paramLimits);
        $tvs = explode(',', $params['tvs']);
        $condition = ['parent' => $request->getAttribute('id'), 'published' => true, 'deleted' => false];
        if ($params['ignoreMenu']) {
            $condition['hidemenu'] = 0;
        }

        /** @var modResource $resource */

        $query = $this->modx->newQuery(modResource::class);
        $query->select($this->modx->getSelectColumns(modResource::class, 'modResource'));
        $query->where($condition);
        $total = $this->modx->getCount(modResource::class, $query);
        if($tvs) {
            $this->joinTVs($query, $tvs);
        }
        $query->limit($params['limit'], ($params['page'] - 1) * $params['limit']);
        $query->sortby($params['sortBy'], $params['sortDir']);
        $resources = $this->modx->getIterator(modResource::class, $query);
        if (!$resources) {
            throw RestfulException::notFound();
        }
        $data = [];
        foreach($resources as $resource) {
            $arr = $resource->toArray();
            $arr['content'] = $resource->parseContent();
            $data[] = $arr;
        }
        return $this->respondWithCollection($request, $data, ['total' => $total], $params);
    }
}
