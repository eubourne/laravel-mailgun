<?php

namespace EuBourne\LaravelMailgun\Console;

use EuBourne\LaravelMailgun\Traits\SupportsFormatting;
use Illuminate\Console\Command;

abstract class CommandAbstract extends Command
{
    use SupportsFormatting;

    /**
     * Define output color scheme
     */
    const string SUBJECT = CommandAbstract::COLOR_YELLOW;
    const string MUTED = CommandAbstract::COLOR_GRAY;
    const string ADDRESS = CommandAbstract::COLOR_GREEN;
    const string SECONDARY_ADDRESS = CommandAbstract::COLOR_VIOLET;
    const string QUEUE = CommandAbstract::COLOR_RED;
    const string TAGS = CommandAbstract::COLOR_VIOLET;
}
