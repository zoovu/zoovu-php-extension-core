<?php namespace Semknox\Core\Services\Search\Filters;

class CollectionFilter extends AbstractFilter {

    /**
     * Get all available options for this filter.
     */
    public function getOptions()
    {
        $values = $this->filterData['values'];
        $counts = $this->filterData['counts'];
        $result = [];

        foreach($values as $value) {
            // convert key => value to concept format
            $result[] = new Option([
               'key'      => $value['key'],
               'name'     => $value['name'],
               'viewName' => $value['name'],
               'count'    => $counts[$value['key']],
               'children' => []
           ]);
        }

        return $result;
    }
}