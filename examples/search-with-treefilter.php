<?php
//--------------------------------------------------------------
// Output a tree filter
//--------------------------------------------------------------


require __DIR__ . '/_config.php';

$sxCore = makeSxCore();
$search = $sxCore->getSearch();
$search = $search->query('Ding')
                 ->addFilter('Kategorie', 'ROOT/Verbrauchsmaterial/Laborbedarf/Abformung & Prothetik/Hilfsmittel für Abformung & Prothetik')
;

$result   = $search->search();

$category = $result->getAvailableFilters()[0];
$options = $category->getOptions();

echo '<pre>';
iterateThroughOptions($options);
echo '</pre>';


/**
 * Loop through options
 * @param $options
 * @param int $level
 */
function iterateThroughOptions($options, $level=0)
{
    foreach ($options as $option) {
        /* @var $option \Semknox\Core\Services\Search\Filters\Option */
        echo sprintf("%s %s %s (%d) \n",
                str_repeat('—', $level), // level indicator
                $option->isActive() ? ' ✔✔✔ ' : '',
                $option->getName(),
                $option->getNumberOfResults()
        );

        // iterate through children
        if($option->hasChildren()) {
            iterateThroughOptions($option->getChildren(), $level+1);
        }
    }
}