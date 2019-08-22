<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace Glitch;

interface ITransport
{
    public function sendDump(string $packet): void;
}
