<?php namespace Semknox\Core\Services\Search\Filters;

class CollectionFilter extends AbstractFilter {
    public function getType()
    {
        return 'COLLECTION';
    }

    /**
     * Get all available options for this filter.
     */
    public function getOptions()
    {
        $values = $this->filterData['values'];
        $result = [];

        foreach($values as $value) {
            // convert key => value to concept format
            $result[] = new Option([
               'key'      => $value['key'],
               'name'     => $value['name'],
               'viewName' => $value['name'],
               'count'    => $value['count'],
               'children' => []
           ], $this->getActiveOptions());
        }

        return $result;
    }


}