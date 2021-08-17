<?php


namespace SfmcRestSdk\DataExtension;


use SfmcRestSdk\Client;

class SyncCall
{
    private $client;

    /**
     * SyncCall constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $dataExtensionKey
     * @param array $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function createRow(string $dataExtensionKey, array $data)
    {
        $uri = "hub/v1/dataevents/key:{$dataExtensionKey}/rowset";

        return $this->client->postJson($uri, [$data]);
    }

    /**
     * @param string $dataExtensionKey
     * @param string $key
     * @param array $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function upsertRow(string $dataExtensionKey, string $key, array $data)
    {
        $uri = "hub/v1/dataevents/key:{$dataExtensionKey}/rowset/$key";

        return $this->client->postJson($uri, [$data]);
    }

    public function filterRows(
        string $dataExtensionKey,
        int $page,
        int $pageSize,
        string $orderBy,
        string $filters
    )
    {
        $uri = "data/v1/customobjectdata/key/{$dataExtensionKey}/rowset";

        $query['$page'] = $page;
        $query['$pageSize'] = $pageSize;
        $query['$orderBy'] = $orderBy;
        $query['$filter'] = $filters;

        return $this->client->get($uri, $query);
    }
}