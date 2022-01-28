<?php

namespace Weble\RevisoApi;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;
use RectorPrefix20220126\Symfony\Contracts\HttpClient\HttpClientInterface;
use Weble\RevisoApi\Endpoint\Endpoint;
use Weble\RevisoApi\Exceptions\EndpointNotFoundException;

/**
 * Class Reviso
 * @package Weble\RevisoApi
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
 * @property-read Endpoint $voucherTemplates
 */
class Reviso
{
    public const REDIRECT_URL = 'https://app.reviso.com/api1/requestaccess.aspx';
    protected Client $client;
    protected array $endpoints = [];
    protected ?object $info = null;

    public function __construct(string $appSecretToken = 'demo', string $agreementGrantToken = 'demo', ?HttpClientInterface $httpClient = null)
    {
        $this->client = new Client($appSecretToken, $agreementGrantToken, $httpClient);
    }

    public static function getRedirectUrl(string $appPublicToken = 'demo', string $locale = 'en-GB', string $redirectUrl = ''): string
    {
        return static::REDIRECT_URL . '?appId=' . $appPublicToken . '&locale=' . $locale . '&redirectUrl=' . $redirectUrl;
    }

    public static function parseTokenFromUrl(UriInterface $uri): ?string
    {
        parse_str($uri->getQuery(), $query);

        return $query['token'] ?? null;
    }

    /**
     * @throws Exceptions\ErrorResponseException
     */
    public function __get($name)
    {
        $name = Utils::snakeString($name);
        $endpoints = $this->getEndpoints();

        if (!isset($endpoints[$name])) {
            return $this->$name;
        }

        return $this->getEndpoint($name);
    }

    /**
     * @throws Exceptions\ErrorResponseException
     */
    public function getEndpoint(string $name): Endpoint
    {
        $endpoints = $this->getEndpoints();

        if (!isset($endpoints[$name])) {
            throw new EndpointNotFoundException($name, $endpoints);
        }

        // Dedicated class?
        $class = '\\Webleit\\RevisoApi\\Endpoint\\' . ucfirst(strtolower($name)) . 'Endpoint';
        if (class_exists($class)) {
            return new $class($this->client, new Uri($endpoints[$name]));
        }

        // Generic Endpoint
        return new Endpoint($this->client, new Uri($endpoints[$name]));
    }

    public function isDemo(): bool
    {
        return $this->client->isDemo();
    }

    /**
     * @throws Exceptions\ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getInfo(): \stdClass
    {
        if ($this->info === null) {
            $this->info = $this->client->get('/');
        }

        return $this->info;
    }

    /**
     * @throws Exceptions\ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getVersion(): string
    {
        return $this->getInfo()->version ?? '';
    }

    /**
     * @throws Exceptions\ErrorResponseException
     */
    public function getEndpoints(): array
    {
        if (empty($this->endpoints)) {
            $this->endpoints = array_merge($this->getProductionEndpoints(), $this->getExperimentalEndpoints());
        }

        return $this->endpoints;
    }

    /**
     * @throws Exceptions\ErrorResponseException
     * @throws ClientExceptionInterface
     */
    protected function getEndpointList(): \stdClass
    {
        return $this->client->get(new Uri($this->getInfo()->resources));
    }

    /**
     * @throws Exceptions\ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getProductionEndpoints(): array
    {
        return (array)($this->getEndpointList()->production ?? []);
    }

    /**
     * @throws Exceptions\ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getExperimentalEndpoints(): array
    {
        return (array)($this->getEndpointList()->experimental ?? []);
    }
}
