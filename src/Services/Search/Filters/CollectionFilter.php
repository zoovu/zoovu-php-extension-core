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

            if(!isset($value['value']) || !isset($value['name']) || !isset($value['count'])) continue;

            // convert key => value to concept format
            $result[] = new Option([
               'key'      => $value['value'],
               'name'     => $value['name'],
               'value'    => $value['value'],
               'count'    => $value['count'],
               'children' => []
           ], $this->getActiveOptions());
        }

        return $result;
    }


}