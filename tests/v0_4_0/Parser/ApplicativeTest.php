<?php declare(strict_types=1);
/**
 * This file is part of the Parsica library.
 *
 * Copyright (c) 2020 Mathias Verraes <mathias@verraes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Verraes\Parsica\v0_4_0\Parser;

use PHPUnit\Framework\TestCase;
use Verraes\Parsica\PHPUnit\ParserAssertions;
use function Cypress\Curry\curry;
use function Verraes\Parsica\{alphaChar,
    anything,
    atLeastOne,
    char,
    digitChar,
    keepFirst,
    keepSecond,
    pure,
    repeat,
    repeatList,
    sepBy,
    sepBy1,
    skipSpace,
    string};

final class ApplicativeTest extends TestCase
{
    use ParserAssertions;

    /** @test */
    public function pure()
    {
        $parser = pure("<3");
        $this->assertParses("(╯°□°)╯", $parser, "<3");
    }

    /** @test */
    public function sequential_application()
    {
        $upper = pure(fn(string $v) => strtoupper($v));
        $hello = string('hello');

        // Parser<callable(a):b> -> Parser<a> -> Parser<b>
        $parser = $upper->apply($hello);
        $this->assertParses("hello", $parser, "HELLO");
    }

    /** @test */
    public function sequential_application_2()
    {
        $multiply = curry(fn($x, $y) => $x * $y);
        $number = digitChar()->map(fn($s) => intval($s));

        // Parser<callable(a, b):c> -> Parser<a> -> Parser<b> -> Parser<c>
        $parser = pure($multiply)->apply($number)->apply($number);
        $input = "35";
        $this->assertParses($input, $parser, 15);
    }

    /** @test */
    public function sequential_application_3()
    {
        $sort3 = curry(function($x, $y, $z) {
            $arr = [$x, $y, $z];
            sort($arr);
            return implode('', $arr);
        });

        $parser = pure($sort3)->apply(anything())->apply(anything())->apply(anything());
        $this->assertParses("735", $parser, "357");
        $this->assertParses("cba", $parser, "abc");
    }

    /** @test */
    public function keepFirst()
    {
        $parser = keepFirst(char('a'), char('b'));
        $this->assertParses("abc", $parser, "a");
        $this->assertRemainder("abc", $parser, "c");
        $this->assertParseFails("ac", $parser);
    }

    /** @test */
    public function keepFirst_with_ignore()
    {
        $parser = keepFirst(char('a'), skipSpace());
        $this->assertParses("a     ", $parser, "a");
    }

    /** @test */
    public function keepSecond()
    {
        $parser = keepSecond(char('a'), char('b'));
        $this->assertParses("abc", $parser, "b");
        $this->assertRemainder("abc", $parser, "c");
        $this->assertParseFails("ac", $parser);
    }

    /** @test */
    public function sepBy()
    {
        $parser = sepBy(string('||'), atLeastOne(alphaChar()));

        $input = "";
        $expected = [];
        $this->assertParses($input, $parser, $expected);

        $input = "foo";
        $expected = ["foo"];
        $this->assertParses($input, $parser, $expected);

        $input = "foo||";
        $expected = ["foo"];
        $this->assertParses($input, $parser, $expected);
        $this->assertRemainder($input, $parser, "||");

        $input = "foo||bar";
        $expected = ["foo", "bar"];
        $this->assertParses($input, $parser, $expected);

        $input = "foo||bar||";
        $expected = ["foo", "bar"];
        $this->assertParses($input, $parser, $expected);
        $this->assertRemainder($input, $parser, "||");

        $input = "foo||bar||baz";
        $expected = ["foo", "bar", "baz"];
        $this->assertParses($input, $parser, $expected);

        $input = "||";
        $this->assertParses($input, $parser, [], "The sepBy parser always succeed, even if it doesn't find anything");
        $this->assertRemainder($input, $parser, $input);

        $input = "||bar||baz";
        $this->assertParses($input, $parser, []);
        $this->assertRemainder($input, $parser, $input);

        $input = "||bar||";
        $this->assertParses($input, $parser, []);
        $this->assertRemainder($input, $parser, $input);

        $input = "||bar";
        $this->assertParses($input, $parser, []);
        $this->assertRemainder($input, $parser, $input);
    }


    /** @test */
    public function sepBy1()
    {
        $parser = sepBy1(string('||'), atLeastOne(alphaChar()));

        $input = "";
        $this->assertParseFails($input, $parser, "at least one A-Z or a-z, separated by '||'");

        $input = "||";
        $this->assertParseFails($input, $parser);

        $input = "||bar||baz";
        $this->assertParseFails($input, $parser);

        $input = "||bar||";
        $this->assertParseFails($input, $parser);

        $input = "||bar";
        $this->assertParseFails($input, $parser);


        $input = "foo";
        $expected = ["foo"];
        $this->assertParses($input, $parser, $expected);

        $input = "foo||";
        $expected = ["foo"];
        $this->assertParses($input, $parser, $expected);
        $this->assertRemainder($input, $parser, "||");

        $input = "foo||bar";
        $expected = ["foo", "bar"];
        $this->assertParses($input, $parser, $expected);

        $input = "foo||bar||";
        $expected = ["foo", "bar"];
        $this->assertParses($input, $parser, $expected);
        $this->assertRemainder($input, $parser, "||");

        $input = "foo||bar||baz";
        $expected = ["foo", "bar", "baz"];
        $this->assertParses($input, $parser, $expected);
    }


    /** @test */
    public function repeat_vs_repeatList()
    {
        $parser = repeat(5, alphaChar());
        $this->assertParses("hello", $parser, "hello");
        $parser = repeatList(5, alphaChar());
        $this->assertParses("hello", $parser, ["h", "e", "l", "l", "o"]);

        $parser = repeatList(3, repeat(3, alphaChar()));
        $this->assertParses("EURUSDGBP", $parser, ["EUR", "USD", "GBP"]);
    }
}

