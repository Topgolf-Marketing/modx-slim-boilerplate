<?php

namespace MODXSlim\Api\Controllers\Resources;

use MODX\Revolution\modResource;
use MODXSlim\Api\Exceptions\RestfulException;
use MODXSlim\Api\Traits\TemplateVariables;
use MODXSlim\Api\Transformers\ResourceTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MODXSlim\Api\Controllers\Restful;

class Listing extends Restful
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
        $defaultParams = ['tvs' => null, 'ignoreMenu' => false, 'start' => 0, 'context' => 'web', 'sortBy' => 'menuindex'];
        $paramsCast = ['ignoreMenu' => 'bool', 'start' => 'int'];
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
        $condition = ['parent' => $params['start'], 'context' => $params['context'], 'published' => true, 'deleted' => false];
        if ($params['ignoreMenu']) {
            $condition['hidemenu'] = 0;
        }

        /** @var modResource $resource */

        $query = $this->modx->newQuery(modResource::class);
        $query->select($this->modx->getSelectColumns(modResource::class, 'modResource'));
        $query->where($condition);
        if($tvs) {
            $this->joinTVs($query, explode(',', $tvs));
        }
        $query->limit($params['limit'], ($params['page'] - 1) * $params['limit']);
        $query->sortby($params['sortBy'], 'ASC');
        $resources = $this->modx->getIterator(modResource::class, $query);
        if (!$resources) {
            throw RestfulException::notFound();
        }
        $data = [];
        foreach($resources as $resource) {
            $data[] = $resource->toArray();
            $data['content'] = $resource->parseContent();
        }
        return $this->respondWithCollection($request, $data);
    }
}
