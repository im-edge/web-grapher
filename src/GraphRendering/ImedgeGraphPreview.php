<?php

namespace IMEdge\Web\Grapher\GraphRendering;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class ImedgeGraphPreview extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = [
        'class' => ['imedge-graph', 'preview-graph'],
    ];
    public HtmlElement $canvas;
    protected RrdImage $image;

    public function __construct(RrdImage $image)
    {
        $this->image = $image;
        $this->canvas = Html::tag('div', ['class' => 'imedge-graph-canvas'], $image);
        $this->add($this->canvas);
    }
}
