<?php

namespace IMEdge\Web\Grapher\Graph;

use Icinga\Web\UrlParams;

class ImageFormat
{
    protected const DEFAULT_FORMAT = 'svg';

    protected ?string $format = null;

    public function __construct(?string $format = null)
    {
        if ($format) {
            $this->setFormat($format);
        }
    }

    public function getFormat(): string
    {
        return $this->format ?? self::DEFAULT_FORMAT;
    }

    public function setFormat(?string $format)
    {
        if ($format === null) {
            $this->format = null;
        } else {
            $this->format = strtolower($format);
        }
    }

    public static function fromUrlParams(UrlParams $params): ImageFormat
    {
        return new ImageFormat($params->get('format'));
    }

    public function applyUrlParams(UrlParams $params): void
    {
        $this->setFormat($params->get('format'));
    }

    public function applyToUrlParams(UrlParams $params)
    {
        if ($this->format !== null) {
            $params->set('format', $this->format);
        }
    }

    public function __toString(): string
    {
        return ShellParameter::renderOptional('imgformat', strtoupper($this->getFormat()));
    }
}
