<?php


namespace Apiato\Core\Traits;


use Kalnoy\Nestedset\NodeTrait;

trait NestedSetModel
{
    use NodeTrait;

    protected $breadcrumbsKey = 'name';

    public function getBreadcrumbsAttribute()
    {
        return $this->ancestors->pluck($this->breadcrumbsKey)->push($this->{$this->breadcrumbsKey})->toArray();
    }

}
