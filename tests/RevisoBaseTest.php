<?php

namespace Weble\RevisoApi\Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Weble\RevisoApi\Endpoint\ListEndpoint;
use Weble\RevisoApi\Model;
use Weble\RevisoApi\Reviso;

class RevisoBaseTest extends TestCase
{
    use ArraySubsetAsserts;

    protected static ?Reviso $reviso = null;

    public static function setUpBeforeClass(): void
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
        $resource = self::$reviso->getEndpoint('accounts');

        $this->assertEquals('accounts', $resource->getName());
        $this->assertGreaterThan(0, count($resource->getRouteList()));
    }

    /**
     * @test
     */
    public function can_get_account_list()
    {
        $resource = self::$reviso->getEndpoint('accounts');

        $this->assertGreaterThan(0, $resource->get()->count());
    }

    /**
     * @test
     */
    public function can_get_account()
    {
        $resource = self::$reviso->getEndpoint('accounts');

        $this->assertGreaterThan(0, $resource->get()->count());
    }

    /**
     * @test
     */
    public function can_parse_route()
    {
        $resource = self::$reviso->getEndpoint('accounts');

        $route = new Uri('https://rest.reviso.com/accounts/{accountNumber:int}/accounting-years/{accountingYear}');
        $parameters = $resource->getRouteParameters($route)->toArray();

        $this->assertArraySubset(['accountNumber', 'accountingYear'], $parameters);
    }

    /**
     * @test
     */
    public function can_use_dashed_name_routes()
    {
        $resource = self::$reviso->accountingYears;

        $this->assertEquals('accounting-years', $resource->getName());
    }

    /**
     * @test
     */
    public function can_get_post_schema()
    {
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
        $resource = self::$reviso->accounts;
        $item = $resource->get()->first();

        $this->assertInstanceOf(ListEndpoint::class, $item->accountingYears);
        $this->assertGreaterThan(0, $item->accountingYears->get()->count());
    }

    /**
     * @test
     */
    public function can_filter_customers()
    {
        $resources = self::$reviso->customers;
        $list = $resources->get();
        $resource = $list->first();
        $lastResource = $list->last();

        $corporateIdentificationNumber = $resource->corporateIdentificationNumber;
        $list = collect($resources->where('corporateIdentificationNumber', '=', $corporateIdentificationNumber)->get()->toArray());

        $this->assertGreaterThan(0, $list->count());
        $resource = $list->first();
        $this->assertEquals($corporateIdentificationNumber, $resource['corporateIdentificationNumber']);
        $this->assertEquals(0, $list->where('corporateIdentificationNumber', '!=', $corporateIdentificationNumber)->count());
    }

    /**
     * @test
     */
    public function can_list_customers()
    {
        $resources = self::$reviso->customers;
        $list = $resources->get();

        $this->assertGreaterThan(0, $list->count());

    }

    public static function tearDownAfterClass(): void
    {
        self::$reviso = null;
    }
}
