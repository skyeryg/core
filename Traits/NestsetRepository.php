<?php


namespace Apiato\Core\Traits;


trait NestsetRepository
{
    public function getTree($id = null)
    {
        if ($id) {
            return $this->model->descendantsOf($id)->toTree();
        }
        return $this->model->get()->toTree();
    }

}
