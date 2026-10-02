<?php

declare(strict_types=1);

namespace PhpMiniCache\Connection;

/**
 * Accumulates bytes read from a socket.
 *
 * A single TCP read is not guaranteed to contain a whole command, so
 * incoming bytes are appended here until a parser further up the stack
 * decides enough has arrived.
 */
final class ReadBuffer
{
    private string $buffer = '';

    public function append(string $bytes): void
    {
        $this->buffer .= $bytes;
    }

    public function contents(): string
    {
        return $this->buffer;
    }

    public function length(): int
    {
        return strlen($this->buffer);
    }

    /**
     * Drops the first $length bytes - the ones a parser has already turned
     * into values.
     */
    public function consume(int $length): void
    {
        $this->buffer = substr($this->buffer, $length);
    }
}
