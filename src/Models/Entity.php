<?php

namespace FaubourgNumerique\NgsiLd\Models;

use FaubourgNumerique\NgsiLd\Exceptions\EntityInitException;
use FaubourgNumerique\NgsiLd\Exceptions\ReservedKeywordException;

class Entity
{
    public array $entity = [];

    public function __construct(string|array $idOrArray, string $type = null) {
        if (is_array($idOrArray)) {
            if (!is_null($type)) {
                trigger_error();
            }

            $this->entity = $idOrArray;
        } else {
            if (is_null($type)) {
                trigger_error();
            }

            $this->entity["id"] = $idOrArray;
            $this->entity["type"] = $type;
        }
    }

    public function getId(): string
    {
        return $this->entity["id"];
    }

    public function getType(): string
    {
        return $this->entity["type"];
    }

    public function isProperty(string $name): bool
    {
        return isset($this->entity[$name]["value"]);
    }

    public function isRelationship(string $name): bool
    {
        return isset($this->entity[$name]["object"]);
    }

    public function getProperty(string $name): mixed
    {
        if (!$this->isProperty($name)) {
            return null;
        }

        return $this->entity[$name]["value"];
    }

    public function getRelationship(string $name): string
    {
        if (!$this->isRelationship($name)) {
            return null;
        }

        return $this->entity[$name]["object"];
    }

    public function setProperty(string $name, mixed $value): void
    {
        if ($name === "id" || $name === "type") {
            throw new ReservedKeywordException("\"{$name}\" is a reserved keyword and cannot be used as a property name");
        }

        $this->entity[$name] = [
            "type" => "Property",
            "value" => $value
        ];
    }

    public function setRelationship(string $name, string $object): void
    {
        if ($name === "id" || $name === "type") {
            throw new ReservedKeywordException("\"{$name}\" is a reserved keyword and cannot be used as a relationship name");
        }

        $this->entity[$name] = [
            "type" => "Relationship",
            "object" => $object
        ];
    }

    public function toArray(): array
    {
        return $this->entity;
    }
}
