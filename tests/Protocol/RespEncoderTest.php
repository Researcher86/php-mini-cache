<?php

declare(strict_types=1);

namespace PhpMiniCache\Tests\Protocol;

use PhpMiniCache\Protocol\RespEncoder;
use PhpMiniCache\Protocol\RespValue;
use PHPUnit\Framework\TestCase;

final class RespEncoderTest extends TestCase
{
    public function testEncodesSimpleString(): void
    {
        $encoder = new RespEncoder();

        self::assertSame("+OK\r\n", $encoder->encode(RespValue::simpleString('OK')));
    }

    public function testEncodesError(): void
    {
        $encoder = new RespEncoder();

        self::assertSame("-ERR unknown command\r\n", $encoder->encode(RespValue::error('ERR unknown command')));
    }

    public function testEncodesInteger(): void
    {
        $encoder = new RespEncoder();

        self::assertSame(":100\r\n", $encoder->encode(RespValue::integer(100)));
    }

    public function testEncodesBulkString(): void
    {
        $encoder = new RespEncoder();

        self::assertSame("\$5\r\nhello\r\n", $encoder->encode(RespValue::bulkString('hello')));
    }

    public function testEncodesNullBulkStringAsNegativeOne(): void
    {
        $encoder = new RespEncoder();

        self::assertSame("\$-1\r\n", $encoder->encode(RespValue::bulkString(null)));
    }

    public function testEncodesArrayOfBulkStrings(): void
    {
        $encoder = new RespEncoder();

        $value = RespValue::array([
            RespValue::bulkString('GET'),
            RespValue::bulkString('foo'),
        ]);

        self::assertSame("*2\r\n\$3\r\nGET\r\n\$3\r\nfoo\r\n", $encoder->encode($value));
    }

    public function testEncodesNullArrayAsNegativeOne(): void
    {
        $encoder = new RespEncoder();

        self::assertSame("*-1\r\n", $encoder->encode(RespValue::array(null)));
    }

    /**
     * A simple string or error is one line by definition, and its text can
     * carry client-chosen bytes (an unknown command's name, a bad header).
     * Left raw, a CR or LF in it ends the line early and the rest is read
     * as a reply of its own - "-ERR ...'x" followed by a forged "+OK".
     */
    public function testLineBreaksInsideSimpleStringsAndErrorsAreFlattenedToSpaces(): void
    {
        $encoder = new RespEncoder();

        self::assertSame("-ERR unknown command 'x  +ok'\r\n", $encoder->encode(RespValue::error("ERR unknown command 'x\r\n+ok'")));
        self::assertSame("+a b c\r\n", $encoder->encode(RespValue::simpleString("a\rb\nc")));
    }
}
