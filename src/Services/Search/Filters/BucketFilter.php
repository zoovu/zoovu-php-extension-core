<?php namespace Semknox\Core\Services\Search\Filters;

class BucketFilter extends AbstractFilter {
    public function getType()
    {
        return 'BUCKET';
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
               'key'      => $value['value'],
               'name'     => $value['value'],
               'viewName' => $value['value'],
               'count'    => $value['count'],
               'children' => []
           ], $this->getActiveOptions());
        }

        return $result;
    }

    /**
     * Return data for active options
     * @return array
     */
    public function getActiveOptions()
    {
        if(isset($this->filterData['activeOptions'])){

            foreach($this->filterData['activeOptions'] as &$option){
                $option['key'] = $option['value'];
            }

            return $this->filterData['activeOptions'];
        }
        
        return [];
    }


}