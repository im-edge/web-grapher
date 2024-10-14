<?php

namespace IMEdge\Web\Grapher\GraphRendering;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class CommandRenderer extends HtmlDocument
{
    use TranslationHelper;

    protected string $commandString;

    public function __construct(string $commandString)
    {
        $this->commandString = $commandString;
    }

    protected function assemble()
    {
        // TODO: JS behaviour
        $icon = Icon::create('paste', [
            'onclick' => 'navigator.clipboard.writeText($(\'pre\','
                . ' $(this).closest(\'.content\')).first().text().replace(/\r?\n/gs, \' \'))',
            'class'   => 'clipboard-icon',
            'title'   => $this->translate('Copy to Clipboard'),
        ]);
        $this->add(
            Html::tag('pre', [$icon, wordwrap($this->commandString, 80)])
        );
    }
}
