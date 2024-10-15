<?php

namespace IMEdge\Web\Grapher\Structure;

use IMEdge\RrdStructure\DsList;
use IMEdge\RrdStructure\RraSet;
use IMEdge\RrdStructure\RrdInfo;
use Ramsey\Uuid\UuidInterface;

class ExtendedRrdInfo extends RrdInfo
{
    protected UuidInterface $uuid;
    protected Ci $ci;
    protected string $metricStoreIdentifier;

    public function __construct(
        UuidInterface $uuid,
        string $filename,
        int $step,
        DsList $dsList,
        RraSet $rra,
        Ci $ci,
        string $metricStoreIdentifier
    ) {
        parent::__construct($filename, $step, $dsList, $rra);
        $this->uuid = $uuid;
        $this->ci = $ci;
        $this->metricStoreIdentifier = $metricStoreIdentifier;
    }

    public function getCi(): Ci
    {
        return $this->ci;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getMetricStoreIdentifier(): string
    {
        return $this->metricStoreIdentifier;
    }
}
