<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$search = $sxCore->getSearch();

$search = $search->query('*')
                 ->sortBy('Preis aufsteigend')
;

echo 'Url: ' . $search->getRequestUrl() . "<br>";

$result   = $search->search();

//--------------------------------------------------------------
// Sorting options
//--------------------------------------------------------------
$sortingOptions = $result->getAvailableSortingOptions();

foreach($sortingOptions as $option) {
    echo $option->getName() . ' - ' . $option->getKey() . ' - ' . $option->getSort() . "<br>";
}

echo '<br><br><hr><br>';



//--------------------------------------------------------------
// Product results
//--------------------------------------------------------------

$products = $result->getProducts();

foreach($products as $product) {
    echo $product->getName() . "<br>";
}
