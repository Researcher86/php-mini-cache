<?php

declare(strict_types=1);

namespace PhpMiniCache\Protocol;

final readonly class RespEncoder
{
    public function encode(RespValue $value): string
    {
        return match ($value->type) {
            RespType::SimpleString => '+' . $this->singleLine($value->value) . "\r\n",
            RespType::Error => '-' . $this->singleLine($value->value) . "\r\n",
            RespType::Integer => ':' . $value->value . "\r\n",
            RespType::BulkString => $this->encodeBulkString($value->value),
            RespType::Array => $this->encodeArray($value->value),
        };
    }

    /**
     * A simple string or error ends at the first CRLF, and its text can carry
     * bytes a client chose (an unknown command's name, a malformed header).
     * Written raw, a line break in it ends the reply early and turns the rest
     * into a forged reply of its own, so CR and LF become spaces instead.
     */
    private function singleLine(mixed $text): string
    {
        /** @var string $text RespValue::simpleString()/error() only ever hold a string. */
        return strtr($text, "\r\n", '  ');
    }

    private function encodeBulkString(mixed $value): string
    {
        if ($value === null) {
            return '$-1' . "\r\n";
        }

        /** @var string $value RespValue::bulkString() only ever holds a string or null. */
        return '$' . strlen($value) . "\r\n" . $value . "\r\n";
    }

    private function encodeArray(mixed $items): string
    {
        if ($items === null) {
            return '*-1' . "\r\n";
        }

        /** @var list<RespValue> $items RespValue::array() only ever holds a list of values or null. */
        $encoded = '*' . count($items) . "\r\n";

        foreach ($items as $item) {
            $encoded .= $this->encode($item);
        }

        return $encoded;
    }
}
