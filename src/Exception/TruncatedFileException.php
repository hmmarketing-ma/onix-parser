<?php

namespace ONIXParser\Exception;

/**
 * The file ends in the middle of a <Product> record (cut download, full disk ...).
 * The parser never drops that last record without telling you.
 */
class TruncatedFileException extends \RuntimeException
{
    /** @var int */
    private $byteOffset;

    public function __construct(int $byteOffset)
    {
        $this->byteOffset = $byteOffset;
        parent::__construct("The file ends inside a <Product> record that starts at byte $byteOffset (file is truncated).");
    }

    /** Byte offset in the file of the unfinished `<Product`. */
    public function getByteOffset(): int
    {
        return $this->byteOffset;
    }
}
