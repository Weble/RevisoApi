<?php

namespace Webleit\ZohoBooksApi\Test;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Webleit\RevisoApi\Model;
use Webleit\RevisoApi\Endpoint;
use Webleit\RevisoApi\Reviso;

/**
 * Class ClassNameGeneratorTest
 * @package Webleit\ZohoBooksApi\Test
 */
class RevisoBaseTest extends TestCase
{
    protected static $reviso;

    public static function setUpBeforeClass()
    {
        self::$reviso = new Reviso();
    }

    /**
     * @test
     */
    public function can_get_info()
    {


        $info = self::$reviso->getInfo();

        $expectedData = [
            'apiName',
            'company',
            'gettingStarted',
            'resources',
            'version',
            'serverTime'
        ];

        foreach ($expectedData as $key) {
            $this->assertObjectHasAttribute($key, $info);
        }
    }

    /**
     * @test
     */
    public function can_get_version()
    {


        $version = self::$reviso->getVersion();

        $this->assertGreaterThan(0, strlen($version));
    }

    /**
     * @test
     */
    public function can_get_resources()
    {


        $resources = self::$reviso->getEndpoints();

        $this->assertGreaterThan(0, count($resources));
    }

    /**
     * @test
     */
    public function can_get_resource_key()
    {


        $key = self::$reviso->accounts->getResourceKey();

        $this->assertEquals('accountNumber', $key);
    }

    /**
     * @test
     */
    public function has_account_resource()
    {


        /** @var Endpoint $resource */
        $resource = self::$reviso->getEndpoint('accounts');

        $this->assertEquals('accounts', $resource->getName());
        $this->assertGreaterThan(0, count($resource->getRouteList()));
    }

    /**
     * @test
     */
    public function can_get_account_list()
    {


        /** @var Endpoint $resource */
        $resource = self::$reviso->getEndpoint('accounts');

        $this->assertGreaterThan(0, $resource->get()->count());
    }

    /**
     * @test
     */
    public function can_get_account()
    {


        /** @var Endpoint $resource */
        $resource = self::$reviso->getEndpoint('accounts');

        $this->assertGreaterThan(0, $resource->get()->count());
    }

    /**
     * @test
     */
    public function can_parse_route()
    {


        /** @var Endpoint $resource */
        $resource = self::$reviso->getEndpoint('accounts');

        $route = new Uri('https://rest.reviso.com/accounts/{accountNumber:int}/accounting-years/{accountingYear}');
        $parameters = $resource->getRouteParameters($route);

        $this->assertArraySubset([
            'accountNumber',
            'accountingYear'
        ], $parameters->toArray());

    }

    /**
     * @test
     */
    public function can_use_dashed_name_routes()
    {


        /** @var Endpoint $resource */
        $resource = self::$reviso->accountingYears;

        $this->assertEquals('accounting-years', $resource->getName());

    }

    /**
     * @test
     */
    public function can_get_post_schema()
    {


        /** @var Endpoint $resource */
        $resource = self::$reviso->accounts;
        $schema = $resource->getPostSchema();
        $properties = $schema->properties;

        $this->assertGreaterThan(0, count(get_object_vars($properties)));

    }

    /**
     * @test
     */
    public function can_find_account()
    {
        /** @var Endpoint $resource */
        $resource = self::$reviso->accounts;
        $item = $resource->get()->first();
        $model = $resource->find($item);

        $this->assertInstanceOf(Model::class, $model);
        $this->assertEquals($model->accountNumber, $item->accountNumber);
    }

    /**
     * @test
     */
    public function can_find_accounting_years_from_account()
    {

        /** @var Endpoint $resource */
        $resource = self::$reviso->accounts;
        $item = $resource->get()->first();

        $this->assertInstanceOf(Endpoint\ListEndpoint::class, $item->accountingYears);
        $this->assertGreaterThan(0, $item->accountingYears->get()->count());
    }

    /**
     * @test
     */
    public function can_list_customers()
    {
        /** @var Endpoint\Endpoint $resource */
        $resources = self::$reviso->customers;
        $list = $resources->get();
        $resource = $list->first();

        $this->assertGreaterThan(0, $list->count());

    }

    public static function tearDownAfterClass()
    {
        self::$reviso = null;
    }
}