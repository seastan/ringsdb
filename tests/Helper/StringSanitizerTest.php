<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\StringSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * StringSanitizer behaves as the FILTER_SANITIZE_STRING filter it replaces.
 */
class StringSanitizerTest extends TestCase
{
    #[DataProvider('valueProvider')]
    public function testSanitize(mixed $value, string $encoded, string $notEncoded): void
    {
        $this->assertSame($encoded, StringSanitizer::sanitize($value));
        $this->assertSame($notEncoded, StringSanitizer::sanitize($value, false));
    }

    /**
     * @return array<string, string[]|int[]|string[]|string[][]|null[]>
     */
    public static function valueProvider(): array
    {
        return [
            'plain text' => ['Dwarf Deck', 'Dwarf Deck', 'Dwarf Deck'],
            'tags are stripped' => ['<b>Bold</b> <script>alert(1)</script>name', 'Bold alert(1)name', 'Bold alert(1)name'],
            'quotes' => ['Gandalf\'s "Deck"', 'Gandalf&#39;s &#34;Deck&#34;', 'Gandalf\'s "Deck"'],
            'null' => [null, '', ''],
            'integer' => [42, '42', '42'],
            'array' => [['name'], '', ''],
        ];
    }
}
