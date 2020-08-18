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
    'apiUrl' => 'https://dev-api-v3.semknox.com/',

    // optional options
    
    // update date is stored on the file system and then sent to Semknox bundled
    // this config tells the core where to store the update data
    // it is required if you plan to do an initial upload
    'storagePath'        => '/path/to/writable/directory',
    
    // when this configuration is givem, an instance of this class will
    // automatically try to convert the given product to a Semknox compatible format
    // For more information check the section `Product transformer`    
    'productTransformer' => \My\Shop\Semknox\ProductTransformer::class,
    
    // how many products to collect in one file / send in one request
    'uploadBatchSize' => 2000,
    
    // how the directory to collect the products should be called
    'storeIdentifier' => 'default',
    
    // how long (in seconds) a request should take before it gets aborted
    'requestTimeout' => 15,

    // deletes all completed initial uploads except for the last X ones
    'keepCompletedUploads' => 5,

    // deletes all aborted initial uploads except for the last X ones
    'keepAbortedUploads' => 1
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

## Product updates

Product updates work very similar to the initial upload. The difference here is that you do not need to initiate it with startCollecting(). When you add a product it collects that product update to a file. When you call sendUploadBatch() it will send all collected product updates to Semknox.   

~~~php
// collect products
$updater = $sxCore->getProductUpdater();

foreach($products as $product) {
    $updater->addProduct($product);
}

// then send them as update
$updater->sendUploadBatch();
~~~

 
## Product search

~~~php
$search = $sxCore->getSearch();

// search() returns a SearchResponse
$response = $search->query('ding')
                   ->search();

$totalResults = $response->getTotalResults();
$products     = $response->getProducts();
$answer       = $response->getAnswerText();
~~~

## Product search with filters

To add filters to your search you can use the `addFilter` method on the SearchService instance. Pass the exact name as the first parameter and the values you want to filter as an array as second parameter. For range filters (e.g. price) the min and max values should be given as an array as second argument. 

~~~php
$search = $sxCore->getSearch();

// search() returns a SearchResponse
$response = $search->query('ding')
                   ->addFilter('Price', [50, 100])
                   ->addFilter('Farbe', ['blau', 'rot'])
                   ->search();

$products     = $response->getProducts();
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