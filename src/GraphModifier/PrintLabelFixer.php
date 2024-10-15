<?php

namespace IMEdge\Web\Grapher\GraphModifier;

use IMEdge\RrdGraph\Graph\Instruction\PrintGraphInstruction;
use IMEdge\RrdGraphInfo\GraphInfo;
use IMEdge\Web\Grapher\Graph\ImedgeRrdGraph;

class PrintLabelFixer
{
    public static function replacePrintLabels(ImedgeRrdGraph $graph, GraphInfo $info): void
    {
        $printVars = [];
        if ($graph->definition) {
            foreach ($graph->definition->getInstructions() as $instruction) {
                if ($instruction instanceof PrintGraphInstruction) {
                    $printVars[] = $instruction->getVariableName()->getName();
                }
            }
        }

        $print = [];
        foreach ($info->print as $idx => $value) {
            $print[$printVars[$idx]] = $value;
        }

        $info->print = $print;
    }
}
