<?php namespace Semknox\Core\Services\Search\Filters;

use Semknox\Core\Interfaces\FilterInterface;

class TreeFilter extends AbstractFilter implements FilterInterface {
    /**
     * @inheritDoc
     */
    public function getType()
    {
        return 'TREE';
    }

    /**
     * Get all available options for this filter.
     */
    public function getOptions()
    {
        $concepts = $this->filterData['categories'];
        $result = [];

        foreach($concepts as $concept) {
            $result[] = new Option($concept, $this->getActiveOptions());
        }

        return $result;
    }
}