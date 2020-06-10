<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$search = $sxCore->getSearch();

$search = $search->query('*')
                 ->setLimit(10)
//                 ->addFilter('Kategorie', ['Gear'])
;

echo 'Url: ' . $search->getRequestUrl() . "<br>";

$result   = $search->search();
var_dump($result);die();
$products = $result->getProducts();

echo 'Number of products: ' . $result->getTotalProductResults() . "<br>";
//
foreach($products as $product) {
    echo $product->getId() . ' - ' . $product->getName();
    //var_dump($product);
    echo "<br>\n\n";
}

echo '<hr><br><hr><br>';

echo 'active filters<br>';
var_dump($result->getActiveFilters());

echo '<hr><br><hr><br>';
// multiselect , range

$filters = $result->getAvailableFilters();

foreach($filters as $filter) {
    echo $filter->getName() . ' ' . '<br>';

    foreach($filter->getOptions() as $option) {
        echo sprintf(' - %s (%d)<br>', $option->getName(), $option->getNumberOfResults());

        if($option->hasChildren()) {
            foreach($option->getChildren() as $option) {
                echo sprintf('&nbsp;&nbsp; - %s (%d)<br>', $option->getName(), $option->getNumberOfResults());
            }
        }
    }
}

