<?php namespace Semknox\Core\Services\Search\Filters;

class RangeFilter extends AbstractFilter {
    /**
     * Currently active min range value
     * @var flat
     */
    protected $activeMin;

    /**
     * Currently active max range value
     * @var float
     */
    protected $activeMax;

    /**
     * Get all available options for this filter.
     */
    public function getOptions()
    {
        $counts = $this->filterData['counts'];
        $result = [];

        foreach($counts as $value => $count) {
            $active = is_numeric($value)
                       && ($value >= $this->activeMin)
                       && ($value <= $this->activeMax)
            ;

            // convert key => value to concept format
            $result[] = new Option([
                'key'      => $value,
                'name'     => $value,
                'viewName' => $value,
                'count'    => $count,
                'active'   => $active,
                'children' => []
            ]);
        }

        return $result;
    }

    /**
     * Set the currently active min and max range values.
     *
     * @param array $values
     */
    public function setActiveOptions($values)
    {
        $this->activeMin = min($values);

        $this->activeMax = max($values);
    }

    /**
     * Get the range minimum
     * @return mixed
     */
    public function getMin()
    {
        return $this->filterData['min'];
    }

    /**
     * Get the range maximum
     * @return mixed
     */
    public function getMax()
    {
        return $this->filterData['max'];
    }
}