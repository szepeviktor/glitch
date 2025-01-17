<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

class Packet
{
    protected string $body;
    protected string $contentType;

    /**
     * Init with body and content type
     */
    public function __construct(string $body, string $contentType)
    {
        $this->body = $body;
        $this->contentType = $contentType;
    }

    /**
     * Get body
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get content type
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }
}
