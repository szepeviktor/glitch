<?php
/**
 * This file is part of the Glitch package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

use DecodeLabs\Glitch\Factory;
use DecodeLabs\Glitch\Context;
use DecodeLabs\Glitch\Stack\Trace;

/**
 * This is just a facade.
 */
final class Glitch
{
    const VERSION = 'v0.9.0';

    public static $autoRegister = true;

    /**
     * Override auto-register of error handlers
     */
    public static function setAutoRegister(bool $register): void
    {
        self::$autoRegister = $register;
    }

    /**
     * Redirect type list to Factory
     */
    public static function __callStatic(string $method, array $args): \EGlitch
    {
        return Factory::create(
            null,
            explode(',', $method),
            ...$args
        );
    }

    /**
     * Shortcut to Context
     */
    public static function getContext(): Context
    {
        return Context::getDefault();
    }

    /**
     * Shortcut to incomplete context method
     */
    public static function incomplete($data=null)
    {
        Context::getDefault()->incomplete($data, 1);
    }

    /**
     * Shortcut to normalizePath context method
     */
    public static function normalizePath(string $path): string
    {
        return Context::getDefault()->normalizePath($path);
    }

    /**
     * Shortcut to logException context method
     */
    public static function logException(\Throwable $e): void
    {
        Context::getDefault()->logException($e);
    }

    /**
     * Create a stack trace
     */
    public static function stackTrace(): Trace
    {
        return Trace::create();
    }

    /**
     * Private instanciation
     */
    private function __construct()
    {
    }
}
