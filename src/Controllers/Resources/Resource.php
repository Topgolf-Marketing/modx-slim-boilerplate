<?php

namespace MODXSlim\Api\Controllers\Resources;

use MODX\Revolution\modResource;
use MODXSlim\Api\Exceptions\RestfulException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use MODXSlim\Api\Controllers\Restful;
use MODXSlim\Api\Traits\TemplateVariables;

class Resource extends Restful
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
        $defaultParams = ['tvs' => null, 'parent' => 0, 'context' => 'web'];
        $paramsCast = ['parent' => 'int'];
        $params = $this->getParams($request, $defaultParams, $paramsCast);
        $tvs = explode(',', $params['tvs']);
        $id = $request->getAttribute('id');
        $alias = $request->getAttribute('alias');
        $condition = ['published' => true, 'deleted' => false];
        if ($id) {
            $condition[] = ['id' => $id];
        } elseif ($alias) {
            $condition[] = ['alias' => $alias, 'parent' => $params['parent'], 'context_key' => $params['context']];
        } else {
            throw RestfulException::notFound();
        }

        /** @var modResource $resource */

        $query = $this->modx->newQuery(modResource::class);
        $query->select($this->modx->getSelectColumns(modResource::class, 'modResource'));
        $query->where($condition);
        if($tvs) {
            $this->joinTVs($query, $tvs);
        }
        $resource = $this->modx->getObject(modResource::class, $query);
        if (!$resource) {
            throw RestfulException::notFound();
        }
        $data = $resource->toArray();
        $data['content'] = $resource->parseContent();
        return $this->respondWithItem($request, $data);
    }
}

