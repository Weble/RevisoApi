# Reviso REST Api - PHP SDK

This Library is a SDK in PHP that simplifies the usage of the Reviso REST API (http://api-docs.reviso.com)
It provides both an interface to ease the interaction with the APIs without bothering with the actual REST request, while packaging the various responses using very simple Model classes that can be then uses with any other library or framework.

## Installation 

```
composer require webleit/revisoapi
```

## Usage

In order to use the library, just require the composer autoload file, and then fire up the library itself.
In order for the library to work, you need to provide an auth token for the zoho book apis.

```php
require './vendor/autoload.php';
$reviso = new \Webleit\RevisoApi\Reviso($appSecretToken, $agreementGrantToken);
```

If you want to use the demo account, just don't specify the auth parameters, and you'll be able to use any
GET request.

```php
$reviso = new \Webleit\RevisoApi\Reviso();
```

## API calls

To call any Api, just use the same name reported in the api docs.
You can get the list of supported apis using the getEndpoints() method

```php 
$reviso->getEndpoints();
```

You can, for example, get the list of customers by using:

```php
$customers = $reviso->customers->get();
```

or the list of customer groups

```php
$groups = $reviso->customerGroups->get();
```

### List calls

To get a list of resources from a module, use the getList() method

```php
$customers = $reviso->customers->get();
```

In order to navigate the pages, just use the "page" and "perPage" methods

```php
$customers = $reviso->customers->page(1)->perPage(100)->get();
```


## Return Types

Any "list" api call returns a Collection object, which contains information on the list itself, allows for further pagination, 
and stores the list of items in a Laravel-derived Collection package.
You can therefore use the result as Collection, which allows mapping, reducing, serializing, etc

```php
$customers = $reviso->customers->get();

$data = $customers->toArray();
$json = $customers->toJson();

// After fetch filtering in php
$filtered = $customers->where('accountNumber', '>', 200);

// Grouping
$filtered = $customers->groupBy('country');

```

Any "resource" api call returns a Model object of a class dedicated to the single resource you're fetching.
For example, calling

```php
$customer = $reviso->customers->get($accuntNumber);
$data = $customer->toArray();
$name = $customer->name;

```

will return a \Webleit\RevisoApi\Model object, which is Arrayable and Jsonable, and that can be therefore used in many ways.

## CRUD

You can create / Read / Update / Delete a resource from the Endpoint class or on the model itself.

### Create
```php
$data = [
    /** Data of the customer */
];
$customer = $reviso->customers->create($data);
```

### Read
```php
$customer = $reviso->customers->find($accountNumber);
```

### Update
```php
$data = [
    /** Data of the customer */
];
$customer = $reviso->customers->find($accountNumber);
$customer->save($data);
```

### Delete
```php
$data = [
    /** Data of the customer */
];
$customer = $reviso->customers->find($accountNumber);
$customer->delete();
```

## Test
This package contains some tests to test the basic functionalities of the package.
In order to run the tests also on the "CRUD" methods, you need to create a config.json file in the "tests/" directory,
with the authentication details of an app you want to use as a test base

```vendor/bin/phpunit tests``` will run all the tests
```vendor/bin/phpunit tests/RevisoBaseTest.php``` will run only the GET tests, that runs even in demo mode, without
authentication details

## Contributing

Finding bugs, sending pull requests or improving the docs - any contribution is welcome and highly appreciated

## Versioning

Semantic Versioning Specification (SemVer) is used.

## Copyright and License

Copyright Weble Srl under the MIT license.
