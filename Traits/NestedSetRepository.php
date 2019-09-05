<?php


namespace Apiato\Core\Traits;


trait NestedSetRepository
{
    public function getTree($id = null)
    {
        if ($id) {
            return $this->model->descendantsOf($id)->toTree();
        }
        return $this->model->get()->toTree();
    }

}
