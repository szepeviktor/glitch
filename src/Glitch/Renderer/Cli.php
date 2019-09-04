<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Renderer;

use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Renderer;
use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;

class Cli implements Renderer
{
    const SPACES = 2;
    const RENDER_CLOSED = false;

    const RENDER_SECTIONS = [
        'info' => false,
        'meta' => false,
        'text' => true,
        'properties' => true,
        'values' => true,
        'stack' => true
    ];

    const RENDER_STACK = false;

    const FG_COLORS = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37,
        'default' => 38,
        'reset' => 39
    ];

    const BG_COLORS = [
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'white' => 47,
        'default' => 48,
        'reset' => 49
    ];

    const OPTIONS = [
        'bold' => [1, 22],
        'dim' => [2, 22],
        'underline' => [4, 24],
        'blink' => [5, 25],
        'reverse' => [7, 27],
        'private' => [8, 28]
    ];

    use Base;

    protected $formatStack = [];


    /**
     * Build a stat list header bar
     */
    protected function renderStats(array $stats): string
    {
        $output = [];

        foreach ($stats as $key => $stat) {
            $output[] = $this->format($stat->render('text'), 'cyan');
        }

        return implode(' | ', $output);
    }

    /**
     * Flatten buffer for final render
     */
    protected function exportBuffer(array $buffer): string
    {
        return "\n".implode("\n\n", $buffer)."\n\n";
    }

    /**
     * Render a null scalar
     */
    protected function renderNull(?string $class=null): string
    {
        return $this->format('null', 'magenta', null, 'bold');
    }

    /**
     * Render a boolean scalar
     */
    protected function renderBool(bool $value, ?string $class=null): string
    {
        return $this->format($value ? 'true' : 'false', 'magenta', null, 'bold');
    }

    /**
     * Render a integer scalar
     */
    protected function renderInt(int $value, ?string $class=null): string
    {
        return $this->format((string)$value, 'blue', null, 'bold');
    }

    /**
     * Render a float scalar
     */
    protected function renderFloat(float $value, ?string $class=null): string
    {
        return $this->format($this->normalizeFloat($value), 'blue', null, 'bold');
    }

    /**
     * Render a single identifier string
     */
    protected function renderIdentifierString(string $string, string $class, int $forceSingleLineMax=null): string
    {
        $options = [];
        $parts = explode(' ', $class);
        $mod = array_pop($parts);
        $style = array_pop($parts);
        $color = 'yellow';
        $output = '';

        switch ($style) {
            case 'info':
                $color = 'cyan';
                break;

            case 'meta':
                $color = 'white';
                break;

            case 'properties':
                $color = 'white';
                $options[] = 'bold';

                switch ($mod) {
                    case 'public':
                        break;

                    case 'protected':
                        $output .= $this->format('*', 'blue', null, 'bold');
                        break;

                    case 'private':
                        $output .= $this->format('!', 'red', null, 'bold');
                        break;
                }
                break;

            case 'values':
                $color = 'yellow';
                break;
        }

        return $output.$this->format($this->renderStringLine($string, $forceSingleLineMax), $color, null, ...$options);
    }

    /**
     * Render a standard multi line string
     */
    protected function renderMultiLineString(string $string): string
    {
        $string = str_replace("\r", '', $string);
        $parts = explode("\n", $string);
        $count = count($parts);

        $output = [];
        $output[] = $this->format('""" '.mb_strlen($string), 'white', null, 'dim');

        foreach ($parts as $part) {
            $output[] = $this->format($this->renderStringLine($part), 'red', null, 'bold').
                $this->format('⏎', 'white', null, 'dim');
        }

        $output[] = $this->format('"""', 'white', null, 'dim');

        return implode("\n", $output);
    }

    /**
     * Render a standard single line string
     */
    protected function renderSingleLineString(string $string, int $forceSingleLineMax=null): string
    {
        $output = $this->format('"', 'white', null, 'dim');
        $output .= $this->stackFormat('red', null, 'bold');
        $output .= $this->renderStringLine($string, $forceSingleLineMax);
        $output .= $this->popFormat();
        $output .= $this->format('"', 'white', null, 'dim');

        return $output;
    }

    /**
     * Render binary string chunk
     */
    protected function renderBinaryStringChunk(string $chunk): string
    {
        return $this->format($chunk, 'magenta').' ';
    }

    /**
     * Render a detected ascii control character
     */
    protected function wrapControlCharacter(string $control): string
    {
        return $this->format($control, 'white', 'red', null, 'bold');
    }


    /**
     * Render structure grammer
     */
    protected function renderGrammar(string $grammar): string
    {
        return $this->format($grammar, 'white', null, 'dim');
    }

    /**
     * Render structure pointer
     */
    protected function renderPointer(string $pointer): string
    {
        return $this->format($pointer, 'white', null, 'dim');
    }

    /**
     * Render line number
     */
    protected function renderLineNumber(int $number): string
    {
        return $this->format(str_pad((string)$number, 2), 'blue', null, 'bold');
    }

    /**
     * Render file path
     */
    protected function renderSourceFile(string $path): string
    {
        return $this->format($path, 'yellow');
    }

    /**
     * Render source line
     */
    protected function renderSourceLine(int $number): string
    {
        return $this->format((string)$number, 'magenta', null, 'bold');
    }


    /**
     * render signature namespace part
     */
    protected function renderSignatureNamespace(string $namespace): string
    {
        return $this->format($namespace, 'cyan');
    }

    /**
     * render signature class part
     */
    protected function renderSignatureClass(string $class): string
    {
        return $this->format($class, 'cyan', null, 'bold');
    }

    /**
     * render signature call type part (:: or ->)
     */
    protected function renderSignatureCallType(string $type): string
    {
        return $this->format($type, 'white', null, 'dim');
    }

    /**
     * render signature constant part
     */
    protected function renderSignatureConstant(string $constant): string
    {
        return $this->format($constant, 'magenta');
    }

    /**
     * Wrap signature function block
     */
    protected function wrapSignatureFunction(string $function, ?string $class=null): string
    {
        $output = '';

        if ($class == 'closure') {
            $output .= $this->format('{', 'white', null, 'dim');
        }

        $output .= $this->format($function, 'blue');

        if ($class == 'closure') {
            $output .= $this->format('}', 'white', null, 'dim');
        }

        return $output;
    }

    /**
     * render signature bracket string
     */
    protected function renderSignatureBracket(string $bracket): string
    {
        return $this->format($bracket, 'white', null, 'dim');
    }

    /**
     * render signature arg comma
     */
    protected function renderSignatureComma(): string
    {
        return $this->format(',', 'white', null, 'dim');
    }

    /**
     * render signature object name
     */
    protected function renderSignatureObject(string $object): string
    {
        return $this->format($object, 'green');
    }

    /**
     * Wrap entity name if reference
     */
    protected function wrapReferenceName(string $name): string
    {
        return
            $this->format('&', 'white', null, 'dim').
            $this->format($name, 'green', null, 'bold');
    }

    /**
     * Wrap entity name link
     */
    protected function wrapEntityName(string $name, bool $open, string $linkId): string
    {
        return $this->format($name, 'green', null, 'bold');
    }

    /**
     * render entity length tag
     */
    protected function renderEntityLength(int $length): string
    {
        return $this->format((string)$length, 'cyan', null, 'bold');
    }

    /**
     * render entity class name
     */
    protected function renderEntityClassName(string $class): string
    {
        return $this->format($class, 'white');
    }

    /**
     * render object id tag
     */
    protected function renderEntityOid(int $objectId, bool $isRef, string $id): string
    {
        return
            $this->format('#', 'white', null, 'dim').
            $this->format((string)$objectId, 'white');
    }



    /**
     * Format output for colours
     */
    protected function format(string $message, ?string $fgColor, ?string $bgColor=null, string ...$options): string
    {
        $output = $this->setFormat(...($args = array_slice(func_get_args(), 1)));
        $output .= $message;
        $output .= $this->applyStackedFormat($args);

        return $output;
    }

    /**
     * Stack a format
     */
    protected function stackFormat(?string $fgColor, ?string $bgColor=null, string ...$options): string
    {
        array_unshift($this->formatStack, $args = func_get_args());
        return $this->setFormat(...$args);
    }

    protected function setFormat(?string $fgColor, ?string $bgColor=null, string ...$options): string
    {
        if ($fgColor !== null) {
            $setCodes[] = static::FG_COLORS[$fgColor];
        }

        if ($bgColor !== null) {
            $setCodes[] = static::BG_COLORS[$bgColor];
        }

        foreach ($options as $option) {
            $setCodes[] = static::OPTIONS[$option][0];
        }

        return sprintf("\033[%sm", implode(';', $setCodes));
    }

    protected function resetFormat(?string $fgColor, ?string $bgColor=null, string ...$options): string
    {
        $setCodes[] = static::FG_COLORS['reset'];
        $setCodes[] = static::BG_COLORS['reset'];

        foreach ($options as $option) {
            $setCodes[] = static::OPTIONS[$option][1];
        }

        return sprintf("\033[%sm", implode(';', $setCodes));
    }

    /**
     * Pop formats
     */
    protected function popFormat(): string
    {
        $args = array_shift($this->formatStack);
        return $this->applyStackedFormat($args);
    }

    /**
     * Apply stacked args
     */
    protected function applyStackedFormat(array $args): string
    {
        $output = $this->resetFormat(...$args);

        if (isset($this->formatStack[0])) {
            $args = $this->formatStack[0];

            if (!isset($args[0])) {
                $args[0] = 'reset';
            }

            if (!isset($args[1])) {
                $args[1] = 'reset';
            }

            $output .= $this->setFormat(...$args);
        }

        return $output;
    }
}