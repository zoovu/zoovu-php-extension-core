<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$search = $sxCore->getSearch();

$result = $search->query('ding')->search();

$products = $result->getProducts();

echo 'Number of products: ' . $result->getTotalResults() . "<br>";
foreach($products as $product) {
    echo $product->getId() . ' - ' . $product->getName();
    //var_dump($product);
    echo "<br><br>\n\n";
}

echo '<hr><br><hr><br>';

var_dump($result->getAvailableFilters());
