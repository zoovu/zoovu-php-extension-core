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
    'productTransformer' => \My\Shop\Semknox\ProductTransformer::class,
    'storagePath'        => '/path/to/writable/directory',
    'initialUploadBatchSize' => 200,
    'initialUploadIdentifier' => 'default-store',
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

// collect products from your shop system. $products is an array of your products.
// Each item of the array can be:
//      1) an associative array in Semknox compatible format
//   or 2) an instance of your shop systems product model, IF you have set the `productTransformer` configuration to a valid product transformer 
foreach($products as $product) {
    // transforms a product and adds it to the initial upload
    // you can optionally pass additional parameters as second argument
    $uploader->addProduct($product);
}

// when all products were collected: signalize that product upload is starting now 
$uploader->startUploading();

// upload batches until no products are left to be uploaded
while($uploader->sendUploadBatch()) ;

// all products have been uploaded: tell Semknox to start processing
// and complete the upload.
$uploader->finalizeUpload();
~~~


### Product transformer

Each online shop software stores its products a little different. To generate a unified output to upload to Semknox we have to transform every product first. To do so, generate a custom product transformer that extends `Semknox\Core\Transformer\AbstractProductTransformer`.
That class needs a `__construct` method and `transform` method. 

```php
<?php namespace \My\Shop\Semknox;

use Semknox\Core\Transformer\AbstractProductTransformer;

class ProductTransformer extends AbstractProductTransformer {

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
 
### Initial upload status reporting 

When specified how many products are expected to be uploaded, the InitialUploader can return useful metrics for the current progress of the upload.


~~~php
// ...
$uploader = $sxCore->getInitialUploader();
$uploader->startCollecting([
    'expectedNumberOfProducts' => 7384
]);

echo $uploader->getCollectingProgress(); // return 0 (because 0% of 7384 products have been collected)

echo $uploader->getUploadingProgress(); // returns how much percent of products have been uploaded

echo $uploader->getTotalProgress(); // returns total progress (collecting is 90%, uploading 10%)

echo $uploader->getRemainingTime(); // returns the expected remaining upload time in seconds
~~~

 
## Product search

~~~php
$search = $sxCore->getSearch();

// search() returns a SearchResponse
$response = $search->query('ding')
                   ->search();

$totalResults = $response->getTotalResults();
$products     = $response->getResults();
$answer       = $response->getAnswerText();
~~~

## Search suggestions 

~~~php
$search = $sxCore->getSearchSuggestions();

// search() returns a SearchSuggestionResponse
$response = $search->limitProducts(8)
                   ->query('ding')
                   ->search();

$products = $response->getProducts();
~~~