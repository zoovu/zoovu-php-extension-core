<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$suggestions = $sxCore->getSearchSuggestions();

$result = $suggestions
            ->query('ding')
            ->search();

$products = $result->getProducts();

var_dump($products);

foreach($products as $product) {
    echo $product->getId();
}

