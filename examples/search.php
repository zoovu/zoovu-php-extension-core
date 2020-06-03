<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$search = $sxCore->getSearch();

$result = $search->query('ding')->search();

var_dump($result->getAvailableResults());

$i=0;
foreach($result->getResults() as $product) {
    $i++;
    var_dump($product->getProductType());echo "<br><br>";
}

echo '['.$i.']';

echo '<hr><br><hr><br>';

var_dump($result->getAvailableFilters());
