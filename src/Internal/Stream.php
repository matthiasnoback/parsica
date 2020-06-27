<?php declare(strict_types=1);
/**
 * This file is part of the Parsica library.
 *
 * Copyright (c) 2020 Mathias Verraes <mathias@verraes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Verraes\Parsica\Internal;

/**
 * Represents an input stream. This allows us to have different types of input, each with their own optimizations.
 */
interface Stream
{
    /*
     * Extract a single token from the stream. Throw if the stream is empty.
     *
     * @throw EndOfStream
     */
    public function take1(): Take1;

    /*
     * Try to extract a chunk of length $n, or if the stream is too short, the rest of the stream.
     *
     * Valid implementation should follow the rules:
     *
     * 1. If the requested length <= 0, the empty token and the original stream should be returned.
     * 2. If the requested length > 0 and the stream is empty, throw EndOfStream.
     * 3. In other cases, take a chunk of length $n (or shorter if the stream is not long enough) from the input stream
     * and return the chunk along with the rest of the stream.
     *
     * @throw EndOfStream
     */
    public function takeN(int $n): TakeN;

    /**
     * @deprecated We will need to get rid of this again at some point, we can't assume all streams will be strings
     */
    public function __toString(): string;

    /**
     * Test if the stream is at its end.
     */
    public function isEOF(): bool;
}
