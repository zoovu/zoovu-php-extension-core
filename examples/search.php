<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$search = $sxCore->getSearch();

$result = $search->query('Ding')->search();

foreach($result->getResults() as $product) {
    var_dump($product);echo "<br><br>";
}
