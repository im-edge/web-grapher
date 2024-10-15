<?php

namespace IMEdge\Web\Grapher\Structure;

use gipfl\ZfDb\Adapter\Adapter;
use Ramsey\Uuid\UuidInterface;

class Ci
{
    protected ?string $hostname;
    protected ?string $subject;
    protected ?string $instance;
    protected array $tags = [];
    protected ?UuidInterface $uuid;

    public function __construct(
        ?string $hostname,
        ?string $subject,
        ?string $instance,
        $tags = [],
        ?UuidInterface $uuid = null
    ) {
        $this->hostname = $hostname;
        $this->subject = $subject;
        $this->instance = $instance;
        $this->tags = $tags ? (array) $tags : [];
        $this->uuid = $uuid;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getInstance(): ?string
    {
        return $this->instance;
    }

    public function getTag(string $name)
    {
        return $this->tags[$name] ?? null;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public static function load(UuidInterface $uuid, Adapter $db): Ci
    {
        $query = $db->select()->from('ci')->where('uuid = ?', $uuid->getBytes());
        $row = $db->fetchRow($query);

        return new Ci($row->hostname, $row->subject, $row->instance, $uuid);
    }
}
