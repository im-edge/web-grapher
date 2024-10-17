<?php

namespace IMEdge\Web\Grapher\Graph;

use gipfl\Translation\TranslationHelper;
use Icinga\Web\UrlParams;

class ImageLayout
{
    use TranslationHelper;

    protected bool $onlyGraph = false;
    protected array $defaultParams = [
        'base' => 1000, // 1024 for memory. Traffic -> 1000
        // SI-prefix: 1000, Festplatten, Netzwerktraffic (bit/s) etc
        // IEC-prefix: 1024, RAM, Grafikspeicher, Prozessor-Cache
        'border' => 0,
        // 'step' => 60,
        // 'vertical-label' => 'Percent',
        // 'title' => 'CPU Usage',
        'full-size-mode' => true,
        'no-legend' => true,
        'grid-dash' => '3:0',
        'disable-rrdtool-tag' => true,
        'font-render-mode' => 'normal', // normal, light, mono
        //      normal: Full Hinting and Anti-aliasing (default)
        //      light: Slight Hinting and Anti-aliasing
        //      mono: Full Hinting and NO Anti-aliasing
        'zoom' => 1,
        // 'y' => 'none',
        // 'imgformat' => 'JSON',
        'rigid' => true,
        'units' => 'si', // SI is default for linear graphs
        'slope-mode' => false,
        // 'Z' => true,
        // 'watermark' => 'Graph rendered with IMEdge Metrics, powered by rrdtool',
        'units-length' => 6,
        'left-axis-formatter' => 'numeric',
        'week-fmt' => 'CW %W', // TODO: Translate! (KW, ..)
        // --x-grid ... -> very nice: http://rrdtool.vandenbogaerdt.nl/tutorial/x-grid.php
        // 'graph-render-mode' => 'mono',
    ];
    protected bool $disableCached = false;
    protected bool $allowShrink = false;
    protected ?int $upperLimit = null;
    protected ?int $lowerLimit = null;
    protected bool $disableXAxis = false;
    protected bool $darkMode = false;

    public function setOnlyGraph(bool $graphOnly = true): void
    {
        $this->onlyGraph = $graphOnly;
    }

    public function showsOnlyGraph(): bool
    {
        return $this->onlyGraph;
    }

    public function disableRrdCached(bool $disable = true): void
    {
        $this->disableCached = $disable;
    }

    public function disableXAxis(bool $disable = true): void
    {
        $this->disableXAxis = $disable;
    }

    public function setUpperLimit(?int $limit = null)
    {
        $this->upperLimit = $limit;
    }

    public function setLowerLimit(?int $limit = null)
    {
        $this->lowerLimit = $limit;
    }

    public function setDarkMode(bool $darkMode = true)
    {
        $this->darkMode = $darkMode;
    }

    protected function getParams(): array
    {
        $params = [
            'only-graph' => $this->onlyGraph,
        ];
        if ($this->disableCached) {
            $params['daemon'] = '';
        }
        if ($this->upperLimit) {
            $params['upper-limit'] = $this->upperLimit;
            $params['rigid'] = true;
        }
        if ($this->lowerLimit) {
            $params['lower-limit'] = $this->lowerLimit;
            $params['rigid'] = true;
        }
        // Hint: works even with --rigid
        if ($this->allowShrink) {
            $params['allow-shrink'] = true;
        }
        if ($this->disableXAxis) {
            $params['x'] = 'none';
        }
        $params['color'] = $this->getColorParams();
        $params['font'] = $this->getFontParams();

        return $params + $this->defaultParams;
    }

    protected function getFontParams(): array
    {
        return [
            'DEFAULT:0:LiberationSansMono',
            sprintf('AXIS:%d:LiberationSansMono', $this->disableXAxis ? 7 : 8),
        ];
    }

    protected function getColorParams(): array
    {
        if ($this->darkMode) {
            $modeBased = [
                'ARROW#DEDEDEff',
                'AXIS#DEDEDEff',
                'FONT#DEDEDEff',
                // 'CANVAS#111111ff', // charting area
                'CANVAS#11111100', // charting area
            ];
        } else {
            $modeBased = [
                // light:
                'ARROW#535353ff',
                'AXIS#535353ff',
                'FONT#535353ff',
                'CANVAS#fefefe00', // charting area
                // 'CANVAS#fefefe', // charting area white
            ];
        }

        return array_merge([
            // BACK: background
            // CANVAS: background of the actual graph
            // SHADEA: left and top border
            // SHADEB: right and bottom border
            // GRID, MGRID: major grid
            // FONT: color of the font
            // AXIS: axis of the graph
            // FRAME: line around the color spots
            // ARROW: arrow head pointing up and forward
            'BACK#ffffff00',  // full background
            'FRAME#ffffff00',  // line around the color spots
            // 'BACK#ffffffff',  // full background
            // 'CANVAS#0095BF00', // charting area
            // 'CANVAS#ffffffff', // charting area
            // 'CANVAS#fefefeff', // charting area
            // 'CANVAS#FFFFFF',
            'GRID#0095BF00',
            'MGRID#0095BF44',

            // Hide grid
            // 'GRID#0095BF00',
            // 'MGRID#0095BF00',
        ], $modeBased);
    }

    public static function fromUrlParams(UrlParams $params): ImageLayout
    {
        $self = new ImageLayout();
        $self->applyUrlParams($params);
        return $self;
    }

    public function applyUrlParams(UrlParams $params): void
    {
        if ($upperLimit = $params->get('upperLimit')) {
            $this->setUpperLimit((int) $upperLimit);
        }
        if ($lowerLimit = $params->get('lowerLimit')) {
            $this->setLowerLimit((int) $lowerLimit);
        }
        if ($params->get('disableCached')) {
            $this->disableCached = true;
        }
        if ($params->get('onlyGraph')) {
            $this->onlyGraph = true;
        }
        if ($params->get('allowShrink')) { // upper/lower-limits apply as max values, but graph can shrink
            $this->allowShrink = true;
        }
        if ($params->get('disableXAxis')) {
            $this->disableXAxis = true;
        }
    }

    public function applyToUrlParams(UrlParams $params)
    {
        if ($this->upperLimit === null) {
            $params->remove('upperLimit');
        } else {
            $params->set('upperLimit', $this->upperLimit);
        }
        if ($this->lowerLimit === null) {
            $params->remove('lowerLimit');
        } else {
            $params->set('lowerLimit', $this->lowerLimit);
        }

        if ($this->disableCached === false) {
            $params->remove('disableCached');
        } else {
            $params->set('disableCached', true);
        }
        if ($this->onlyGraph === false) {
            $params->remove('onlyGraph');
        } else {
            $params->set('onlyGraph', true);
        }
        if ($this->allowShrink === false) {
            $params->remove('allowShrink');
        } else {
            $params->set('allowShrink', true);
        }
        if ($this->disableXAxis === false) {
            $params->remove('disableXAxis');
        } else {
            $params->set('disableXAxis', true);
        }
    }

    public function __toString()
    {
        $result = '';
        foreach ($this->getParams() as $name => $value) {
            $result .= ShellParameter::renderOptional($name, $value);
        }

        return $result;
    }
}
