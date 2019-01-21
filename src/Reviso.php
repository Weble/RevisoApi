<?php

namespace Webleit\RevisoApi;
use function GuzzleHttp\Psr7\parse_query;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Webleit\RevisoApi\Endpoint\Endpoint;

/**
 * Class Reviso
 * @package Webleit\RevisoApi
 * @property-read Endpoint $accountingYears
 * @property-read Endpoint $accounts
 * @property-read Endpoint $aliasLayers
 * @property-read Endpoint $appSettings
 * @property-read Endpoint $assetGroups
 * @property-read Endpoint $countryCodes
 * @property-read Endpoint $currencies
 * @property-read Endpoint $customerGroups
 * @property-read Endpoint $customers
 * @property-read Endpoint $daybooks
 * @property-read Endpoint $departmentalDistributions
 * @property-read Endpoint $departments
 * @property-read Endpoint $employees
 * @property-read Endpoint $entries
 * @property-read Endpoint $entrySubtypes
 * @property-read Endpoint $exemptVatCodes
 * @property-read Endpoint $invoices
 * @property-read Endpoint $invoiceTotals
 * @property-read Endpoint $layouts
 * @property-read Endpoint $numberSeries
 * @property-read Endpoint $orders
 * @property-read Endpoint $paymentTerms
 * @property-read Endpoint $paymentTypes
 * @property-read Endpoint $priceGroups
 * @property-read Endpoint $productGroups
 * @property-read Endpoint $products
 * @property-read Endpoint $project
 * @property-read Endpoint $quotations
 * @property-read Endpoint $self
 * @property-read Endpoint $supplierGroups
 * @property-read Endpoint $suppliers
 * @property-read Endpoint $units
 * @property-read Endpoint $vatAccounts
 * @property-read Endpoint $vatTypes
 * @property-read Endpoint $vatZones
 * @property-read Endpoint $voucherAttachments
 * @property-read Endpoint $vouchers
 * @property-read Endpoint $additionalExpenses
 * @property-read Endpoint $apiResources
 * @property-read Endpoint $appRoles
 * @property-read Endpoint $balances
 * @property-read Endpoint $bank-accounts
 * @property-read Endpoint $banks
 * @property-read Endpoint $cities
 * @property-read Endpoint $configuration
 * @property-read Endpoint $matching
 * @property-read Endpoint $partner
 * @property-read Endpoint $payment-info
 * @property-read Endpoint $provinces
 * @property-read Endpoint $studio
 * @property-read Endpoint $tenderContracts
 * @property-read Endpoint $vatStatements
 * @property-read Endpoin $voucherTemplates
 */
class Reviso
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $endpoints;

    /**
     * @var \stdClass
     */
    protected $info;

    /**
     * Reviso constructor.
     * @param string $appSecretToken
     * @param string $agreementGrantToken
     */
    public function __construct ($appSecretToken = 'demo', $agreementGrantToken = 'demo')
    {
        $this->client = Client::getInstance($appSecretToken, $agreementGrantToken);
    }

    /**
     * @param string $appPublicToken
     * @param string $locale
     * @param string $redirectUrl
     * @return string
     */
    public static function getRedirectUrl($appPublicToken = 'demo', $locale = 'en-GB', $redirectUrl = '')
    {
        return 'https://app.reviso.com/api1/requestaccess.aspx?appId=' . $appPublicToken .'&locale=' . $locale . '&redirectUrl=' . $redirectUrl;
    }

    /**
     * @param UriInterface $uri
     * @return bool
     */
    public static function parseTokenFromUrl(UriInterface $uri)
    {
        $query = $uri->getQuery();
        $query = parse_query($query);

        if (isset($query['token'])) {
            return $query['token'];
        }

        return false;
    }

    /**
     * @param $name
     * @return Endpoint
     * @throws Exceptions\ErrorResponseException
     */
    public function __get ($name)
    {
        $name = self::snakeString($name);
        $endpoints = $this->getEndpoints();

        if (!isset($endpoints[$name])) {
            return $this->$name;
        }

        return $this->getEndpoint($name);
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    public static function studlyString($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', '', $value);
    }

    /**
     * Convert a string to snake case.
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    public static function snakeString($value, $delimiter = '-')
    {
        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return $value;
    }

    /**
     * @param $name
     * @return Endpoint
     * @throws Exceptions\ErrorResponseException
     */
    public function getEndpoint($name)
    {
        $endpoints = $this->getEndpoints();

        if (!isset($endpoints[$name])) {
            return $this->$name;
        }

        // Dedicated class?
        $class = '\\Webleit\\RevisoApi\\Endpoint\\' . ucfirst(strtolower($name)) . 'Endpoint';
        if (class_exists($class)) {
            return new $class($this->client, new Uri($endpoints[$name]));
        }


        return new Endpoint($this->client, new Uri($endpoints[$name]));
    }

    /**
     * @param null $demo
     * @return bool
     */
    public function isDemo ($demo = null)
    {
        return $this->client->isDemo($demo);
    }

    /**
     * @return \stdClass
     * @throws Exceptions\ErrorResponseException
     */
    public function getInfo ()
    {
        if (!$this->info) {
            $this->info = $this->client->get('/');
        }

        return $this->info;
    }

    /**
     * @return string
     * @throws Exceptions\ErrorResponseException
     */
    public function getVersion ()
    {
        return $this->getInfo()->version;
    }

    /**
     * @return array
     * @throws Exceptions\ErrorResponseException
     */
    public function getEndpoints()
    {
        if (!$this->endpoints) {
            $this->endpoints = array_merge((array)$this->getProductionEndpoints(), (array)$this->getExperimentalEndpoints());
        }

        return $this->endpoints;
    }

    /**
     * @return \stdClass
     * @throws Exceptions\ErrorResponseException
     */
    protected function getEndpointList()
    {
        return $this->client->get(new Uri($this->getInfo()->resources));
    }
    /**
     * @return array
     * @throws Exceptions\ErrorResponseException
     */
    public function getProductionEndpoints()
    {
        return $this->getEndpointList()->production;
    }

    /**
     * @return array
     * @throws Exceptions\ErrorResponseException
     */
    public function getExperimentalEndpoints()
    {
        return $this->getEndpointList()->experimental;
    }
}