<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Dumper\Dump;
use DecodeLabs\Glitch\Dumper\Entity;

use Throwable;

interface Renderer
{
    /**
     * Override production rendering
     *
     * @return $this
     */
    public function setProductionOverride(bool $flag): static;

    /**
     * Get production override
     */
    public function getProductionOverride(): bool;

    public function renderDump(
        Dump $dump,
        bool $final
    ): Packet;

    public function renderException(
        Throwable $exception,
        Entity $entity,
        Dump $dataDump
    ): Packet;
}
