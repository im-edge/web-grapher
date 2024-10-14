<?php

namespace IMEdge\Web\Grapher\GraphModifier;

use IMEdge\RrdGraph\Color;
use IMEdge\RrdGraph\Data\DataDefinition;
use IMEdge\RrdGraph\DataType\StringType;
use IMEdge\RrdGraph\Graph\Instruction\DefinitionBasedGraphInstruction;
use IMEdge\RrdGraph\GraphDefinition;
use IMEdge\RrdGraph\GraphDefinitionParser;

class Modifier
{
    public static function withoutLegend(GraphDefinition $definition): GraphDefinition
    {
        $parser = new GraphDefinitionParser((string) $definition);
        $clone = $parser->parse();
        foreach ($clone->getInstructions() as $instruction) {
            if ($instruction instanceof DefinitionBasedGraphInstruction) {
                $instruction->setLegend(null);
            }
        }

        return $clone;
    }

    public static function withFile(GraphDefinition $definition, string $filename): GraphDefinition
    {
        $parser = new GraphDefinitionParser((string) $definition);
        $clone = $parser->parse();
        foreach ($clone->getDataDefinitions() as $assignment) {
            $def = $assignment->getExpression();
            assert($def instanceof DataDefinition);
            $def->rrdFile = new StringType($filename);
        }

        return $clone;
    }

    public static function withVariableNameSuffix(GraphDefinition $definition, string $suffix): GraphDefinition
    {
        $parser = new GraphDefinitionParser((string) $definition);
        $clone = $parser->parse();
        foreach ($clone->listUsedVariableNames() as $varName) {
            $clone->renameVariable($varName, $varName . $suffix);
        }

        return $clone;
    }

    public static function replaceDs(GraphDefinition $definition, string $search, string $replace): GraphDefinition
    {
        $parser = new GraphDefinitionParser((string) $definition);
        $clone = $parser->parse();
        foreach ($clone->getDataDefinitions() as $assignment) {
            $def = $assignment->getExpression();
            assert($def instanceof DataDefinition);
            if ($def->dsName->getRawString() === $search) {
                $def->dsName = new StringType($replace);
            }
        }

        return $clone;
    }

    public static function replaceInstructionColor(
        GraphDefinition $definition,
        string $color,
        string $newColor
    ): GraphDefinition {
        $parser = new GraphDefinitionParser((string) $definition);
        $clone = $parser->parse();
        foreach (self::getGraphInstructionsByColor($clone, $color) as $instruction) {
            $instruction->setColor(new Color($newColor, $instruction->getColor()->getAlphaHex()));
        }
        return $clone;
    }

    public static function replaceInstructionColorAndLegend(
        GraphDefinition $definition,
        string $color,
        string $newColor,
        string $newLegend
    ): GraphDefinition {
        $parser = new GraphDefinitionParser((string) $definition);
        $clone = $parser->parse();
        $titleCandidates = [];
        foreach (self::getGraphInstructionsByColor($clone, $color) as $instruction) {
            if ($instruction->getLegend()) {
                $titleCandidates[$instruction->getDefinition()->getName()] = $instruction;
            }
            $instruction->setColor(new Color($newColor, $instruction->getColor()->getAlphaHex()));
        }

        // Set legend only once. e.g.: lighter area, then line
        foreach ($titleCandidates as $instruction) {
            $instruction->setLegend(new StringType($newLegend));
        }

        return $clone;
    }

    // Hint: ignores opacity

    /**
     * @param GraphDefinition $definition
     * @param string $color
     * @return DefinitionBasedGraphInstruction[]
     */
    protected static function getGraphInstructionsByColor(
        GraphDefinition $definition,
        string $color
    ): array {
        $matching = [];
        foreach ($definition->getInstructions() as $idx => $candidate) {
            if ($candidate instanceof DefinitionBasedGraphInstruction) {
                if (strtolower($candidate->getColor()->getHexCode()) === strtolower($color)) {
                    $matching[$idx] =  $candidate;
                }
            }
        }

        return $matching;
    }

    public static function monsterStack(GraphDefinition $template, array $filenames, $singleMax = 100): GraphDefinition
    {
        $varName = 'newFloor';
        $floor = 1;
        $separator = '__';
        $length = strlen((int) count($filenames));
        $result = new GraphDefinition();

        while ($filename = array_shift($filenames)) {
            $floor++;
            $suffix = $separator . sprintf('%0' . $length . 'd', $floor);
            $current = Modifier::withVariableNameSuffix(Modifier::withFile($template, $filename), $suffix);
            foreach ($current->getDefinitions() as $def) {
                $result->addAssignment($def);
            }
            foreach ($current->getInstructions() as $instruction) {
                $result->addGraphInstruction($instruction);
            }
        }

        return $result;
    }
}
