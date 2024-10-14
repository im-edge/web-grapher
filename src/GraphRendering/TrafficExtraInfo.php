<?php

namespace IMEdge\Web\Grapher\GraphRendering;

use gipfl\IcingaWeb2\Widget\NameValueTable;
use gipfl\Translation\TranslationHelper;
use Icinga\Util\Format;
use IMEdge\RrdGraph\Color;
use IMEdge\RrdGraphInfo\GraphInfo;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;

class TrafficExtraInfo extends HtmlDocument
{
    use TranslationHelper;

    public function __construct(GraphInfo $info)
    {
        $p = (object) $info->print;
        if (isset($p->rxOctetsTotal)) {
            $span = Html::tag('span', [
                'class' => 'traffic-summary'
            ]);
            if (is_string($p->rxOctetsTotal) && preg_match('/nan/', $p->rxOctetsTotal)) {
                return;
            }

            $in = new NameValueTable();
            $graph = $info->graph->jsonSerialize(); // TODO: make properties accessible
            $in->addNameValueRow([
                self::showColor(new Color('#57985Bff')),
                ' ',
                $this->translate('Received')
            ], Format::bytes((int) $p->rxOctetsTotal));
            $in->addNameValuePairs([
                $this->translate('95th Percentile') => Format::bits((int) $p->ifInBitsMaxPerc95) . '/s',
                $this->translate('99th Percentile') => Format::bits((int) $p->ifInBitsMaxPerc99) . '/s',
                $this->translate('Peak (15s)')      => Format::bits((int) $p->rxBitsHighest) . '/s',
                // $this->translate('Time frame')      => DateFormatter::formatDuration(
                //     $graph->end  - $graph->start
                // ),
            ]);
            $out = new NameValueTable();
            $out->addNameValueRow([
                self::showColor(new Color('#0095BFff')),
                ' ',
                $this->translate('Transmitted')
            ], Format::bytes((int) $p->txOctetsTotal));
            $out->addNameValuePairs([
                $this->translate('95th Percentile') => Format::bits((int) $p->ifOutBitsMaxPerc95) . '/s',
                $this->translate('99th Percentile') => Format::bits((int) $p->ifOutBitsMaxPerc99) . '/s',
                $this->translate('Peak (15s)')  => Format::bits((int) $p->txBitsHighest) . '/s',
            ]);
            $this->add([$in, $out]);
            // $this->add($span);
        }
    }

    // TODO: Legend?
    protected static function showColor(?Color $color): ?HtmlElement
    {
        if ($color === null) {
            return null;
        }
        return Html::tag('div', [
            'class' => 'legend-color',
            'style' => 'background-color: '
                . $color->toRgba() . ';',
            'title' => $color->getHexCode() . $color->getAlphaHex()
        ]);
    }
}
