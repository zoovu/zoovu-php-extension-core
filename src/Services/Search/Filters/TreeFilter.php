<?php namespace Semknox\Core\Services\Search\Filters;

class TreeFilter extends AbstractFilter {
    /**
     * @inheritDoc
     */
    public function getType()
    {
        return 'TREE';
    }
}