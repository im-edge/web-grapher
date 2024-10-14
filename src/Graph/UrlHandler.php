<?php

namespace IMEdge\Web\Grapher\Graph;

use gipfl\IcingaWeb2\Url;
use Icinga\Web\UrlParams;
use IMEdge\RrdGraph\GraphDefinitionParser;
use IMEdge\Web\Grapher\GraphModifier\Modifier;
use IMEdge\Web\Grapher\GraphTemplateLoader;

class UrlHandler
{
    protected UrlParams $params;
    protected GraphTemplateLoader $templateLoader;

    public function __construct(Url $url)
    {
        $this->params = $url->getParams();
        $this->templateLoader = new GraphTemplateLoader();
    }

    public function getImageDimensions(): ImageDimensions
    {
        return new ImageDimensions(
            $this->params->get('width'),
            $this->params->get('height')
        );
    }

    public function getTimeRange(): TimeRange
    {
        return new TimeRange(
            $this->params->get('start'),
            $this->params->get('end')
        );
    }

    public function getFormat(): ImageFormat
    {
        return new ImageFormat($this->params->get('format'));
    }

    public function getLayout(): ImageLayout
    {
        $layout = new ImageLayout();
        if ($this->params->get('onlyGraph')) {
            $layout->setOnlyGraph();
        }
        if ($this->params->get('disableCached')) {
            $layout->disableRrdCached();
        }

        return $layout;
    }

    public function getGraph(): ImedgeRrdGraph
    {
        if ($template = $this->params->get('template')) {
            $definition = (new GraphDefinitionParser($this->templateLoader->load($template)))->parse();
            $file = $this->params->getRequired('file');
            $definition = Modifier::withFile($definition, $file);
            if ($ds = $this->params->get('ds')) {
                $definition = Modifier::replaceDs($definition, 'value', $ds);
            }
        } else {
            $definition = null;
        }
        return new ImedgeRrdGraph(
            $this->getLayout(),
            $this->getTimeRange(),
            $this->getImageDimensions(),
            $this->getFormat(),
            $definition
        );
    }

    public function wantsJson(): bool
    {
        return (bool) $this->params->get('simulateXhr');
    }

    public function wantsCommand(): bool
    {
        return (bool) $this->params->get('showCommand');
    }

    public function wantsDownload(): bool
    {
        return (bool) $this->params->get('download');
    }
}
