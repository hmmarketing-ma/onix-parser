<?php

namespace ONIXParser\Resume;

/**
 * Where ChunkOnixParser keeps its resume point.
 *
 * The default is FileCheckpointStore (atomic file). A caller that needs the checkpoint to commit
 * together with its own data (for example in the same database transaction as the records it just
 * wrote) implements this interface and passes it to ChunkOnixParser::setCheckpointStore().
 *
 * save() is called by the parser right after the callback of the record that ends the batch has
 * returned, on the same thread. If the callback and save() share a transaction, they commit as one.
 *
 * A checkpoint is an associative array. The parser writes at least:
 *   position   int     byte offset where parsing must restart (first byte after the last handled record)
 *   count      int     number of records handled so far
 *   file_size  int     size of the ONIX file when the checkpoint was written
 *   file_mtime int     modification time of the ONIX file
 *   caller_hash string|null  hash given by the caller with ChunkOnixParser::setFileHash()
 *   timestamp  int
 * A store must give back exactly what it was given.
 */
interface CheckpointStore
{
    /**
     * Load the checkpoint.
     *
     * @return array|null null when there is NO checkpoint (a fresh start).
     * @throws \ONIXParser\Exception\CheckpointException when a checkpoint exists but cannot be read or is damaged.
     *         Never return null for a damaged checkpoint: that would restart a big file from byte 0 without notice.
     */
    public function load(): ?array;

    /**
     * Save the checkpoint. Must be atomic: after a crash the store holds either the old or the new one.
     *
     * @throws \ONIXParser\Exception\CheckpointException when it cannot be written.
     */
    public function save(array $checkpoint): void;

    /**
     * Remove the checkpoint (file finished, or the caller wants a fresh start).
     */
    public function clear(): void;
}
