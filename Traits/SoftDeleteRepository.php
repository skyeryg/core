<?php

namespace Apiato\Core\Traits;


trait SoftDeleteRepository {

    public function withTrashed()
    {
        $this->model = $this->model->withTrashed();
        return $this;
    }

    public function onlyTrashed()
    {
        $this->model = $this->model->onlyTrashed();
        return $this;
    }

    /**
     * Restore a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function restore($id)
    {
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $model = $this->withTrashed()->find($id);

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        $restored = $model->restore();

        return $restored;
    }
}
