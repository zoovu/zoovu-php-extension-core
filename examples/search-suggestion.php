<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$suggestions = $sxCore->getSearchSuggestions()
                      ->query('ding');

echo sprintf('url: %s <br>', $suggestions->getRequestUrl());

$result = $suggestions->search();
$products = $result->getProducts();

foreach($products as $product) {
    echo $product->getId() . '<br>';
}

