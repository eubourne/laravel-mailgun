<?php

namespace EuBourne\LaravelMailgun\Traits;

trait SupportsFormatting
{
    const string COLOR_BLACK = 'black';
    const string COLOR_RED = 'red';
    const string COLOR_GREEN = 'green';
    const string COLOR_YELLOW = 'yellow';
    const string COLOR_BLUE = 'blue';
    const string COLOR_MAGENTA = 'magenta';
    const string COLOR_CYAN = 'cyan';
    const string COLOR_WHITE = 'white';
    const string COLOR_DEFAULT = 'default';
    const string COLOR_GRAY = 'gray';
    const string COLOR_BRIGHT_RED = 'bright-red';
    const string COLOR_BRIGHT_GREEN = 'bright-green';
    const string COLOR_BRIGHT_YELLOW = 'bright-yellow';
    const string COLOR_BRIGHT_BLUE = 'bright-blue';
    const string COLOR_BRIGHT_MAGENTA = 'bright-magenta';
    const string COLOR_BRIGHT_CYAN = 'bright-cyan';
    const string COLOR_BRIGHT_WHITE = 'bright-white';
    const string COLOR_VIOLET = '#6C7280';

    /**
     * Format text with color, background and bold options.
     *
     * @param string $text
     * @param string|null $color
     * @param string|null $background
     * @param bool $bold
     * @return string
     */
    public function format(string $text, string $color = null, string $background = null, bool $bold = false): string
    {
        if ($text && ($color || $background || $bold)) {
            $params = collect([
                'fg' => $color,
                'bg' => $background,
                'options' => $bold ? 'bold' : null
            ])
                ->filter()
                ->map(fn(string $value, string $key) => $key . '=' . $value);

            return "<" . $params->implode(';') . ">" . $text . "</>";
        }

        return '';
    }
}
