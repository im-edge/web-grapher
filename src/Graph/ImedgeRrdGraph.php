<?php

namespace IMEdge\Web\Grapher\Graph;

use Icinga\Web\UrlParams;
use IMEdge\RrdGraph\GraphDefinition;

class ImedgeRrdGraph
{
    public ImageLayout $layout;
    public TimeRange $timeRange;
    public ImageDimensions $dimensions;
    public ImageFormat $format;
    public ?GraphDefinition $definition;

    public function __construct(
        ?ImageLayout $layout = null,
        ?TimeRange $timeRange = null,
        ?ImageDimensions $dimensions = null,
        ?ImageFormat $format = null,
        ?GraphDefinition $definition = null
    ) {
        $this->layout = $layout ?? new ImageLayout();
        $this->timeRange = $timeRange ?? new TimeRange();
        $this->dimensions = $dimensions ?? new ImageDimensions();
        $this->format = $format ?? new ImageFormat();
        $this->definition = $definition;
    }

    public function setDefinition(GraphDefinition $definition)
    {
        $this->definition = $definition;
    }

    public function getLayout(): ImageLayout
    {
        return $this->layout;
    }

    public function setLayout(ImageLayout $layout): void
    {
        $this->layout = $layout;
    }

    public function getTimeRange(): TimeRange
    {
        return $this->timeRange;
    }

    public function setTimeRange(TimeRange $timeRange): void
    {
        $this->timeRange = $timeRange;
    }

    public function getDimensions(): ImageDimensions
    {
        return $this->dimensions;
    }

    public function setDimensions(ImageDimensions $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function getFormat(): ImageFormat
    {
        return $this->format;
    }

    public function setFormat(ImageFormat $format): void
    {
        $this->format = $format;
    }

    public static function fromUrlParams(UrlParams $params): ImedgeRrdGraph
    {
        $graph = new ImedgeRrdGraph();
        $graph->applyUrlParams($params);

        return $graph;
    }

    public function applyUrlParams(UrlParams $params): void
    {
        $this->layout->applyUrlParams($params);
        $this->timeRange->applyUrlParams($params);
        $this->dimensions->applyUrlParams($params);
        $this->format->applyUrlParams($params);
    }

    public function applyToUrlParams(UrlParams $params)
    {
        $this->layout->applyToUrlParams($params);
        $this->timeRange->applyToUrlParams($params);
        $this->dimensions->applyToUrlParams($params);
        $this->format->applyToUrlParams($params);
    }

    public function __toString(): string
    {
        return "graphv -"
            . $this->getTimeRange()
            . $this->getFormat()
            . $this->getDimensions()
            . $this->getLayout()
            . ' ' . $this->definition;
    }
}
