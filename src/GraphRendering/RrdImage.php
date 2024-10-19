<?php

namespace IMEdge\Web\Grapher\GraphRendering;

use gipfl\IcingaWeb2\Url;
use Icinga\Web\UrlParams;
use IMEdge\Json\JsonString;
use IMEdge\RrdGraphInfo\GraphInfo;
use IMEdge\Web\Grapher\Graph\ImedgeRrdGraph;
use IMEdge\Web\Grapher\GraphModifier\PrintLabelFixer;
use IMEdge\Web\Grapher\Structure\ExtendedRrdInfo;
use IMEdge\Web\Rpc\IMEdgeClient;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

use function Clue\React\Block\await;

class RrdImage extends HtmlDocument // TODO: become Element -> imedge-graph-canvas?
{
    protected const TRANSPARENT_GIF = 'data:image/gif;base64,'
        . 'R0lGODlhAQABAJAAAAAAAAAAACH5BAEUAAAALAAAAAABAAEAAAICRAEAOw==';

    public ImedgeRrdGraph $graph;
    /** @var ExtendedRrdInfo[] */
    public array $fileInfos = [];
    protected IMEdgeClient $client;
    protected bool $loadImmediately = false;
    protected string $graphUrl = 'imedge/graph'; // TODO: Parameter?
    protected string $template;
    protected ?GraphInfo $graphInfo = null;

    /**
     * @param ExtendedRrdInfo[] $fileInfos
     */
    public function __construct(ImedgeRrdGraph $graph, string $template, IMEdgeClient $client, array $fileInfos = [])
    {
        $this->graph = $graph;
        $this->client = $client;
        foreach ($fileInfos as $info) {
            $this->fileInfos[$info->getUuid()->toString()] = $info;
        }
        $this->template = $template;
    }

    public function loadImmediately(bool $load = true)
    {
        $this->loadImmediately = $load;
    }

    protected function getLoadedImageAttrs(GraphInfo $info): array
    {
        return [
            'src'         => $info->raw,
            'data-value'  => JsonString::encode($info->value),
            'data-graph'  => JsonString::encode($info->graph),
            'data-image'  => JsonString::encode($info->image),
            'data-description' => $this->getDescription()->render(),
            'data-preLoaded' => '1',
        ];
    }

    protected function getUrl(): Url
    {
        $dimensions = $this->graph->dimensions;
        $timeRange = $this->graph->timeRange;
        $params = new UrlParams();
        $params->addValues([
            'uuid'    => implode(',', array_keys($this->fileInfos)),
            'height'   => $dimensions->getHeight(),
            'width'    => $dimensions->getWidth(),
            // 'format'   => 'png',
            'start'    => $timeRange->getStart(),
            'end'      => $timeRange->getEnd(),
            'template' => $this->template,
        ]);
        $this->graph->applyToUrlParams($params);

        $url = Url::fromPath($this->graphUrl);
        $url->setParams($params);

        return $url;
        // TODO: sign w/o destroying cache lifetime!
        // $signer = new UrlSigner(Keys::getUrlSigningKey(), [
        //     'width',
        //     'height',
        //     'start',
        //     'end',
        //     'rnd'
        // ]);
        //
        // return $signer->sign($url, time() + 900);
    }

    protected function assemble()
    {
        $dimensions = $this->graph->getDimensions();
        if ($dimensions->getHeight() > 40) {
            $this->add(Html::tag('div', ['class' => 'imedge-graph-debug'], 'debug...'));
        }
        $img = Html::tag('img', [
            'alt'   => '',
            'class' => 'imedge-graph-img',
            'style'  => 'width: 100%; max-height: 100%;',
            // 'width'  => $dimensions->getWidth(),
            // 'height' => $dimensions->getHeight(),
            'data-imedge-graph-url' => $this->getUrl(),
        ]);
        if ($this->loadImmediately) {
            $graphInfo = $this->getGraphInfo();
            $img->addAttributes($this->getLoadedImageAttrs($graphInfo));
        } else {
            $img->setAttribute('src', self::TRANSPARENT_GIF);
        }

        $this->add($img);
        $descriptionClasses = ['description'];
        if ($this->graph->layout->showsOnlyGraph()) {
            $descriptionClasses[] = 'hidden';
        }
        //$this->add(Html::tag('div', ['class' => $descriptionClasses], $this->getDescription()));
    }

    public function getDescription(): ?TrafficExtraInfo
    {
        if ($this->loadImmediately || $this->graphInfo) {
            return (new TrafficExtraInfo($this->getGraphInfo()));
        }

        return null;
    }

    public function getClient(): IMEdgeClient
    {
        return $this->client;
    }

    public function getGraphInfo(): GraphInfo
    {
        return $this->graphInfo ??= $this->client->graph($this->graph);
    }

    public function graph(ImedgeRrdGraph $graph): GraphInfo
    {
        $props = await($this->client->request('rrd.graph', [
            'command' => (string) $graph,
            'format'  => strtoupper($graph->getFormat()->getFormat()),
        ]));
        $info = GraphInfo::fromSerialization($props);
        PrintLabelFixer::replacePrintLabels($graph, $info);

        return $info;
    }
}
