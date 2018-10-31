<?php

namespace Webleit\ZohoBooksApi\Test;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Webleit\RevisoApi\ListEndpoint;
use Webleit\RevisoApi\Model;
use Webleit\RevisoApi\Endpoint;
use Webleit\RevisoApi\Reviso;

/**
 * Class ClassNameGeneratorTest
 * @package Webleit\ZohoBooksApi\Test
 */
class RevisoCUDTest extends TestCase
{
    public static $reviso;

    public static function setUpBeforeClass()
    {
        $authFile = __DIR__ . '/config.example.json';
        if (file_exists(__DIR__ . '/config.json')) {
            $authFile = __DIR__ . '/config.json';
        }

        $auth = json_decode(file_get_contents($authFile));

        self::$reviso = new Reviso(
            isset($auth->AppSecretToken) ? $auth->AppSecretToken : 'demo' ,
            isset($auth->AgreementGrantToken) ? $auth->AgreementGrantToken : 'demo'
        );
    }

    /**
     * @test
     * @dataProvider customerDataProvider
     */
    public function can_create_customer ($data)
    {
        $item = $this->createCustomer($data);

        $this->assertInstanceOf(Model::class, $item);
        $this->assertArraySubset($data, $item->toArray());
    }

    /**
     * @param $data
     * @return Model
     * @throws \Webleit\RevisoApi\Exceptions\ErrorResponseException
     */
    protected function createCustomer($data)
    {
        /** @var Endpoint $resource */
        $resource = self::$reviso->customers;
        return $resource->create($data);
    }

    /**
     * @return array
     * @throws \Webleit\RevisoApi\Exceptions\ErrorResponseException
     */
    public function customerDataProvider ()
    {
        self::setUpBeforeClass();

        $customerGroup = self::$reviso->customerGroups->get()->first()->customerGroupNumber;
        $vatZone = self::$reviso->vatZones->get()->first()->vatZoneNumber;
        $paymentTerms = self::$reviso->paymentTerms->get()->first()->paymentTermsNumber;

        $data = [[
            [
                'currency' => 'EUR',
                'customerGroup' => [
                    'customerGroupNumber' => $customerGroup
                ],
                'vatZone' => [
                    'vatZoneNumber' => $vatZone
                ],
                'name' => 'Test1',
                'paymentTerms' => [
                    'paymentTermsNumber' => $paymentTerms
                ],
                'address' => 'Test 1',
                'city' => 'test',
                'country' => 'IT',
            ]
            ]
        ];

        return $data;
    }

    /**
     * @test
     */
    public function can_update_customer ()
    {
        /** @var Model $customer */
        $customer = self::$reviso->customers->get()->getData()->last();

        $data = [
            'name' => $customer->name . '-123'
        ];

        /** @var Endpoint $resource */
        $customer->save($data);

        $this->assertEquals($data['name'], $customer->name);
    }

    /**
     * @test
     * @dataProvider customerDataProvider
     */
    public function can_delete_customer ($data)
    {
        /** @var Model $customer */
        $customer = $this->createCustomer($data);

        $result = $customer->delete();

        $this->assertEquals(true, $result);
    }

    public static function tearDownAfterClass()
    {
        self::$reviso = null;
    }
}