<?php

namespace IMEdge\Web\Grapher\GraphRendering;

use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Url;
use gipfl\Translation\TranslationHelper;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class TimeControl extends BaseHtmlElement
{
    use TranslationHelper;

    protected Url $url;
    protected $tag = 'ul';
    protected $defaultAttributes = [
        'class' => 'nav',
        'data-no-icinga-ajax' => true,
    ];

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    protected function assemble()
    {
        $timeRanges = [
            'end-4hour'  => $this->translate('4 hours'),
            'end-25hour' => $this->translate('25 hours'),
            'end-1week'  => $this->translate('1 week'),
            'end-1month'  => $this->translate('1 month'),
            'end-3month'  => $this->translate('3 months'),
            'end-1year'  => $this->translate('1 year'),
            'end-2hour'  => $this->translate('2 hours (live)'),
            'end-1hour'  => $this->translate('1 hour (live)'),
            'end-30minute'  => $this->translate('30 minutes (live)'),
            'end-15minute'  => $this->translate('15 minutes (live)'),
        ];
        $iconMain = 'clock';
        $link = Link::create('', '#', null, ['class' => 'icon-' . $iconMain]);

        $links = [];
        foreach ($timeRanges as $range => $label) {
            $links[] = Link::create($label, $this->url->with('start', $range)->with('end', 'now'));
        }
        $end = (new \DateTimeImmutable('today midnight'))->getTimestamp();
        $start = $end - 86400;
        $links[] = Link::create($this->translate('Yesterday'), $this->url->with('start', $start)->with('end', $end));
        $start = (new \DateTimeImmutable('today midnight'))->getTimestamp();
        $end = $start + 86400;
        $links[] = Link::create($this->translate('Today'), $this->url->with('start', $start)->with('end', $end));
        $sub = Html::tag('ul', Html::wrapEach($links, 'li'));
        $this->add(Html::tag('li', [$link, $sub]));
    }
}