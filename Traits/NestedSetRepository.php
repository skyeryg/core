<?php


namespace Apiato\Core\Traits;


trait NestedSetRepository
{
    public function getTree($id = null)
    {
        $this->applyCriteria();
        $this->applyScope();

        if ($id) {
            $result = $this->model->descendantsOf($id)->toTree();
        } else {
            $result = $this->model->get()->toTree();
        }

        $this->resetModel();
        $this->resetScope();

        return $this->parserResult($result);
    }

}
