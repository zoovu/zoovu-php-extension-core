<?php namespace Semknox\Core\Services\Search\Filters;

use Semknox\Core\Interfaces\FilterInterface;

class RangeFilter extends AbstractFilter implements FilterInterface {
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
     * @inheritDoc
     */
    public function getType()
    {
        return 'RANGE';
    }

    /**
     * Get all available options for this filter.
     */
    public function getOptions()
    {
        $unit = isset($this->filterData['unit']) ? $this->filterData['unit'] : '';

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
                'value'     => $value,
                'count'    => $count,
                'active'   => $active,
                'unit'     => $unit,
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
     * Get the currently set minimum for this filter. This is different from `getMin` if the filter is set. `getMin` will return the absolute range minimum, whereas `getActiveMin` will return the currently set minimum (which is greater or equal).
     * @return mixed|flat
     */
    public function getActiveMin()
    {
        return $this->activeMin ?: $this->getMin();
    }

    /**
     * Get the range maximum
     * @return mixed
     */
    public function getMax()
    {
        return $this->filterData['max'];
    }

    /**
     * Get the currently set maximum for this filter. This is different from `getMin` if the filter is set. `getMin` will return the absolute range minimum, whereas `getActiveMin` will return the currently set minimum (which is greater or equal).
     * @return mixed|flat
     */
    public function getActiveMax()
    {
        return $this->activeMax ?: $this->getMax();
    }


}