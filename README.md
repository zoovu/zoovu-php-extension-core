# Semknox Core

This package simplifies communication with Semknox API. It provides the following features:

* account creation // TODO
* initial product upload
* product search & filtering

## Configuration

Before you can use any of the features you have to configure the core.


~~~php
$configValues = [
    // required options
    'apiKey'    => '<your api key>',
    'projectId' => '<your projectId>',  
    
    // optional options
    'apiUrl' => 'https://dev-api-v3.semknox.com/',
    'productTransformer' => MagentoSemknoxProductTransformer::class,
    'storagePath'        => '/path/to/writable/directory',
    'initialUploadBatchSize' => 200,
    'initialUploadDirectoryIdentifier' => 'semknox-upload',
    'requestTimeout' => 15
];

$sxConfig = new \Semknox\Core\SxConfig($configValues);
$sxCore = new \Semknox\Core\SxCore($sxConfig);
~~~

TODO: Tabelle mit Infos zu Config-Einstellungen

## Initial product upload

Before you can start searching, you need to upload some of your products to the Semknox backend. Products uploaded will then be analyzed by Semknox for an enhanced search experience.

~~~php
/* @var $uploader \Semknox\Core\Services\InitialUploadService */
$uploader = $sxCore->getInitialUploader();

// this method signals the beginning of a new initial product upload
// it has additional configuration parameters. See the implementation for details. 
$uploader->startCollecting();

// collect products from your shop system. $products is an array of your products
// as an array that contains the required semknox information.
// Alternatively when you set the `productTransformer` configuration, you can pass
// an instance of you shop systems product model to ->addProduct(). 
foreach($products as $product) {
    // transforms a product and adds it to the initial upload
    // you can optionally pass additional parameters as second argument
    $uploader->addProduct($product);
}

// when all products were collected: start uploading all collected products 
$uploader->startUploading();
  
~~~


### Product transformer

Each online shop software stores its products a little different. To generate a unified output to upload to Semknox we have to transform every product first. To do so, generate a custom product transformer that extends `Semknox\Core\Transformer\AbstractProductTransformer`.
That class needs a `__construct` method and `transform` method. 

```php
<?php

use Semknox\Core\Transformer\AbstractProductTransformer;

class MagentoSemknoxProductTransformer extends AbstractProductTransformer {

    private $product;

    public function __construct(\My\Shop\Product $product)
    {
        $this->product = $product;
    }   

    /**
     * Transform a \My\Shop\Product to a Semknox compatible format.
     * @param array $parameters Optional parameters to give 
     * @return array
     */
    public function transform($parameters=[]) {
        return [
            'identifier' => $this->product->getId(),
            'groupIdentifier' => $this->product->getCategoryId(),
            'name' => $this->product->getTitle()
        ];        
    } 
}
```
 
## Product search

~~~php
$search = $sxCore->getSearch();

// search() returns a SearchResponse
$response = $search->setQuery('ding')
                   ->search();

$totalResults   = $response->getTotalResults();
$products       = $response->getResults();
$interpretation = $response->getInterpretedQuery();

~~~