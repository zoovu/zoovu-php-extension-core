<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$search = $sxCore->getSearch();

$search = $search->query('*')
                 ->setLimit(100)
                 ->addFilter('Kategorie', ['Wakeboarding'])
//                 ->addFilter('Farbe', ['Black'])
//                 ->addFilter('Preis', [0, 10])
                 ->setUserGroup('1-de')
;

echo 'Url: ' . $search->getRequestUrl() . "<br>";

$result   = $search->search();
$products = $result->getProducts();
var_dump($result->getTotalResults());
//var_dump(count($products));die();

echo 'Number of products: ' . $result->getTotalProductResults() . "<br>";
//
foreach($products as $product) {
    echo $product->getName();
    //var_dump($product);
    echo "<br>\n\n";
}

echo '<hr><br><hr><br>';

echo 'active filters<br>';
print_r($result->getActiveFilters());

echo '<hr><br><hr><br>';
// multiselect , range

echo 'available filters<br>';
$filters = $result->getAvailableFilters();
var_dump($filters[0]);

foreach($filters as $filter) {
    echo $filter->getName() . ' - ' . ($filter->isActive() ? 'aktiv' : 'inaktiv') . '<br>';

    foreach($filter->getOptions() as $option) {
        $active = $option->isActive() ? 'active' : 'inactive';
        echo sprintf(' - %s (%d) [%s]<br>', $option->getName(), $option->getNumberOfResults(), $active);

        if($option->hasChildren()) {
            foreach($option->getChildren() as $option) {
                $active = $option->isActive() ? 'active' : 'inactive';

                echo sprintf('&nbsp;&nbsp; - %s (%d) [%s]<br>', $option->getName(), $option->getNumberOfResults(), $active);
            }
        }
    }
}

