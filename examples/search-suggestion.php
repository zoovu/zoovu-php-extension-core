<?php

require __DIR__ . '/_config.php';

$sxCore = makeSxCore();


$search = $sxCore->getSearchSuggestions();

$result = $search->query('ding')->search();

var_dump($result->getProducts());
