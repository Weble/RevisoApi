<?php

namespace Webleit\ZohoBooksApi\Test;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use PHPUnit\Framework\TestCase;
use Weble\RevisoApi\Model;
use Weble\RevisoApi\Reviso;

class RevisoCUDTest extends TestCase
{
    use ArraySubsetAsserts;

    public static ?Reviso $reviso = null;

    public static function setUpBeforeClass(): void
    {
        $authFile = self::checkAuthFile();

        $auth = json_decode(file_get_contents($authFile));

        self::$reviso = new Reviso(
            isset($auth->AppSecretToken) ? $auth->AppSecretToken : 'demo',
            isset($auth->AgreementGrantToken) ? $auth->AgreementGrantToken : 'demo'
        );
    }

    private static function checkAuthFile(): string
    {
        $authFile = __DIR__ . '/config.json';
        if (! file_exists($authFile)) {
            self::markTestSkipped("No Auth File in {$authFile}, skipping adavanced tests");
        }

        return $authFile;
    }

    /**
     * @test
     * @dataProvider customerDataProvider
     */
    public function can_create_customer($data)
    {
        self::checkAuthFile();

        $item = $this->createCustomer($data);

        $this->assertInstanceOf(Model::class, $item);
        $this->assertArraySubset($data, $item->toArray());
    }

    /**
     * @param $data
     * @return Model
     * @throws \Weble\RevisoApi\Exceptions\ErrorResponseException
     */
    protected function createCustomer($data)
    {
        /** @var Endpoint $resource */
        $resource = self::$reviso->customers;

        return $resource->create($data);
    }

    /**
     * @return array
     * @throws \Weble\RevisoApi\Exceptions\ErrorResponseException
     */
    public function customerDataProvider()
    {
        self::setUpBeforeClass();

        $customerGroup = self::$reviso->customerGroups->get()->first()->customerGroupNumber;
        $vatZone = self::$reviso->vatZones->get()->first()->vatZoneNumber;
        $paymentTerms = self::$reviso->paymentTerms->get()->first()->paymentTermsNumber;

        $data = [
            [
                [
                    'currency' => 'EUR',
                    'customerGroup' => [
                        'customerGroupNumber' => $customerGroup,
                    ],
                    'vatZone' => [
                        'vatZoneNumber' => $vatZone,
                    ],
                    'name' => 'Test1',
                    'paymentTerms' => [
                        'paymentTermsNumber' => $paymentTerms,
                    ],
                    'address' => 'Test 1',
                    'city' => 'test',
                    'country' => 'IT',
                ],
            ],
        ];

        return $data;
    }

    /**
     * @test
     */
    public function can_update_customer()
    {
        self::checkAuthFile();

        /** @var Model $customer */
        $customer = self::$reviso->customers->get()->getData()->last();

        $data = [
            'name' => $customer->name . '-123',
        ];

        /** @var Endpoint $resource */
        $customer->save($data);

        $this->assertEquals($data['name'], $customer->name);
    }

    /**
     * @test
     * @dataProvider customerDataProvider
     */
    public function can_delete_customer($data)
    {
        self::checkAuthFile();

        /** @var Model $customer */
        $customer = $this->createCustomer($data);

        $result = $customer->delete();

        $this->assertEquals(true, $result);
    }

    public static function tearDownAfterClass(): void
    {
        self::$reviso = null;
    }
}
