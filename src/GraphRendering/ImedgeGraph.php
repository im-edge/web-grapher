<?php

namespace IMEdge\Web\Grapher\GraphRendering;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class ImedgeGraph extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => ['imedge-graph'],
    ];
    public HtmlElement $header;
    public HtmlElement $canvas;
    public HtmlElement $legend;
    protected RrdImage $image;
    protected Url $url;

    public function __construct(RrdImage $image, Url $url, string $title)
    {
        $this->url = $url;
        $this->image = $image;
        $this->header = Html::tag('div', ['class' => 'imedge-graph-header'], [
            Html::tag('h2', $title),
            new TimeControl($url),
            $this->getMaxControl(),
            $this->getFullscreenControl(),
        ]);
        $this->canvas = Html::tag('div', ['class' => 'imedge-graph-canvas'], $image);
        $this->legend = Html::tag('div', ['class' => 'imedge-graph-legend'], $image->getDescription());
        $this->add([$this->header, $this->canvas, $this->legend]);
    }

    protected function getFullscreenControl(): HtmlElement
    {
        $icon = 'resize-full';
        return $this->newNavUl(Link::create('', '#', null, [
            'class' => [
                'icon-' . $icon, 'imedge-toggle-fullscreen',
                'data-no-icinga-ajax' => true,
            ]
        ]));
    }

    protected function newNavUl($mainLink, $sub = null): HtmlElement
    {
        return Html::tag('ul', ['class' => 'nav'], Html::tag('li', [$mainLink, $sub]));
    }

    protected function getMaxControl(): HtmlElement
    {
        $templates = [
            'if_traffic'  => $this->translate('Show maximum Averages'),
            'if_traffic_max' => $this->translate('Show Peaks'),
        ];
        $icon = 'resize-vertical';
        $link = Link::create('', '#', null, [
            'class' => 'icon-' . $icon,
        ]);

        $links = [];
        foreach ($templates as $template => $label) {
            // removing start and end, as it confuses front-end. It's not correct
            $links[] = Link::create($label, $this->url->with('template', $template)->without(['start', 'end']));
        }

        return $this->newNavUl($link, Html::tag('ul', Html::wrapEach($links, 'li')))->addAttributes([
            'data-no-icinga-ajax' => true,
        ]);
    }
}
