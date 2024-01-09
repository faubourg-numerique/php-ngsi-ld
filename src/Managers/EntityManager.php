<?php

namespace FaubourgNumerique\NgsiLd\Managers;

use FaubourgNumerique\NgsiLd\Models\ContextBrokerConfig;
use FaubourgNumerique\NgsiLd\Models\Entity;
use GuzzleHttp;

class EntityManager
{
    private GuzzleHttp\Client $contextBroker;

    public function __construct(bool $https, string $host, ?int $port = null, string $path = null, string $contextUrl = null, array $customHeaders = [])
    {
        $headers = [];
        if (!is_null($contextUrl)) {
            $headers["Link"] = "<{$contextUrl}>" . '; rel="http://www.w3.org/ns/json-ld#context"; type="application/ld+json"';
        }

        $this->contextBroker = new GuzzleHttp\Client([
            "base_uri" => implode("", [$https ? "https" : "http", "://", $host, !is_null($port) ? ":{$port}" : null, $path, "/ngsi-ld/v1/"]),
            "headers" => array_merge($headers, $customHeaders)
        ]);
    }

    public function insert(Entity $entity): void
    {
        //GuzzleHttp\RequestOptions::JSON
        $this->contextBroker->post("entities", ["json" => $entity->toArray()]);
    }

    public function findById(string $id): Entity
    {
        if (!$id) {
            throw new EmptyIdException();
        }

        $response = $this->contextBroker->get("entities/{$id}");
        $data = json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        return new Entity($data);
    }

    public function findMultiple(string $type = null, string $q = null): array
    {
        $query = [];
        if (!is_null($type)) {
            $query["type"] = $type;
        }
        if (!is_null($q)) {
            $query["q"] = $q;
        }

        $limit = 1000;
        $offset = 0;

        $entities = [];
        while (true) {
            $query["limit"] = $limit;
            $query["offset"] = $offset;

            $response = $this->contextBroker->get("entities", ["query" => $query]);
            $rows = json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);

            foreach ($rows as $row) {
                $entities[] = new Entity($row);
            }

            if (count($rows) !== $limit) {
                break;
            }

            $offset += $limit;
        }

        return $entities;
    }

    public function update(Entity $entity): void
    {
        if (!$entity->getId()) {
            throw new EmptyIdException();
        }

        $data = $entity->toArray();
        unset($data["id"]);
        unset($data["type"]);
        $this->contextBroker->post("entities/{$entity->getId()}/attrs", ["json" => $data]);
    }

    public function delete(Entity $entity): void
    {
        if (!$entity->getId()) {
            throw new EmptyIdException();
        }

        $this->contextBroker->delete("entities/{$entity->getId()}");
    }
}
