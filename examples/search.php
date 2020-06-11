<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();

$search = $sxCore->getSearch();

$search = $search->query('*');

echo 'Url: ' . $search->getRequestUrl() . "<br>";

$result   = $search->search();
$products = $result->getProducts();

echo 'Number of products: ' . $result->getTotalProductResults() . "<br>";


foreach($products as $product) {
    echo $product->getId() . ' - ' . $product->getName();
    //var_dump($product);
    echo "<br>\n\n";
}