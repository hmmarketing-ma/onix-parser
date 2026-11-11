<?php

namespace ONIXParser\Exception;

/**
 * A single <Product> record is bigger than the configured hard cap
 * (ChunkOnixParser::setMaxRecordSize). The parser never cuts or skips such a record.
 */
class RecordTooLargeException extends \RuntimeException
{
    /** @var int */
    private $byteOffset;

    /** @var int */
    private $recordBytes;

    /** @var int */
    private $limit;

    public function __construct(int $byteOffset, int $recordBytes, int $limit)
    {
        $this->byteOffset = $byteOffset;
        $this->recordBytes = $recordBytes;
        $this->limit = $limit;
        parent::__construct(sprintf(
            'Record starting at byte %d is larger than the limit (read %d bytes so far, limit %d bytes). Raise it with setMaxRecordSize() or fix the file.',
            $byteOffset,
            $recordBytes,
            $limit
        ));
    }

    /** Byte offset in the file of the `<Product` that is too large. */
    public function getByteOffset(): int
    {
        return $this->byteOffset;
    }

    public function getRecordBytes(): int
    {
        return $this->recordBytes;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
