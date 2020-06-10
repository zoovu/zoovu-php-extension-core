<?php namespace Semknox\Core\Services\Search\Filters;

class RangeFilter extends AbstractFilter {


    /**
     * Get all available options for this filter.
     */
    public function getOptions()
    {
        $values = $this->filterData['counts'];
        $result = [];

        foreach($values as $key => $value) {
            // convert key => value to concept format
            $result[] = new Option([
                'key'      => $key,
                'name'     => $key,
                'viewName' => $key,
                'count'    => $value,
                'children' => []
            ]);
        }

        return $result;
    }
}