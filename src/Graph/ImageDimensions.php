<?php

namespace IMEdge\Web\Grapher\Graph;

use Icinga\Web\UrlParams;
use InvalidArgumentException;

class ImageDimensions
{
    protected const DEFAULT_WIDTH = 640;
    protected const DEFAULT_HEIGHT = 320;

    protected ?int $width;
    protected ?int $height;

    public function __construct(?int $width = null, ?int $height = null)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getWidth(): int
    {
        return $this->width ?? self::DEFAULT_WIDTH;
    }

    public function setWidth(?int $width): ImageDimensions
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): int
    {
        return $this->height ?? self::DEFAULT_HEIGHT;
    }

    public function setHeight(?int $height): ImageDimensions
    {
        $this->height = $height;
        return $this;
    }

    public function set(int $width, int $height): ImageDimensions
    {
        $this->setWidth($width);
        $this->setHeight($height);
        return $this;
    }

    public static function fromUrlParams(UrlParams $params): ImageDimensions
    {
        $dimensions = new ImageDimensions();
        $dimensions->applyUrlParams($params);
        return $dimensions;
    }

    public function applyUrlParams(UrlParams $params): void
    {
        $width = $params->get('width');
        $height = $params->get('height');
        if (ctype_digit($width)) {
            $this->setWidth((int) $width);
        } elseif ($width !== null && $width !== '') {
            throw new InvalidArgumentException('Got invalid width: ' . $width);
        }
        if (ctype_digit($height)) {
            $this->setHeight((int) $height);
        } elseif ($height !== null && $height !== '') {
            throw new InvalidArgumentException('Got invalid height: ' . $height);
        }
    }

    public function applyToUrlParams(UrlParams $params)
    {
        $params->set('width', $this->width);
        $params->set('height', $this->height);
    }

    // TODO: parse. And: what, if we skip defaults?
    public function __toString()
    {
        return ShellParameter::renderOptional('width', $this->getWidth())
            . ShellParameter::renderOptional('height', $this->getHeight());
    }
}
