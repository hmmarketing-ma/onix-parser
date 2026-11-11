# onix-parser 2.0 — what changed and how to use it

This page covers the 2.0 additions that a loader needs. Names in this page are public API.

## 1. Callback stop contract (`ChunkOnixParser::parseWithCheckpoints`)

`callback(Product $product, int $index)` returns:

| Return value | Meaning | Checkpoint `position` | Checkpoint `count` |
|---|---|---|---|
| anything normal (`true`, `null`, `0`, an array ...) | record handled, continue | — | — |
| `ChunkOnixParser::STOP_AFTER` | record handled, stop | first byte **after** the record | includes the record |
| `ChunkOnixParser::STOP_BEFORE` | record **not** handled, stop | first byte **of** the record | does not include it |
| `false` | old contract = `STOP_AFTER` | as `STOP_AFTER` | as `STOP_AFTER` |

Use `STOP_BEFORE` when you check a time budget or a signal *before* you work on the record. Returning `false`
at that point (the old way) lost the record: it was skipped on resume.

If the callback throws, the exception goes up, the record is not counted, and the last saved checkpoint stays.
Records after that checkpoint are delivered again on resume (at-least-once). Make the callback idempotent
or save the checkpoint in the same transaction (next section).

## 2. Checkpoints

A checkpoint always points to a record boundary.

* `Resume\CheckpointStore` is an interface (`load`, `save`, `clear`). Use `setCheckpointStore($store)`.
  Implement it to keep the checkpoint in your database, in the same transaction as your records.
* The default `Resume\FileCheckpointStore` writes a temp file, `fsync`, then `rename`. A crash leaves the old or
  the new file, never half.
* A damaged checkpoint (empty, cut, not JSON, no `position`) raises `Exception\CheckpointException`.
  It is **never** read as "no checkpoint": that would restart a big file from byte 0 without a word.
  Delete it with `clearCheckpoint()` if you really want a fresh start.
* The parser never hashes the ONIX file. Resume is validated by file size, file mtime and an optional hash
  that **you** give with `setFileHash($sha256)`. If you give a hash and the checkpoint has one, only the hash
  decides (so a re-copied file with a new mtime is accepted). Otherwise size and mtime must match.
  A mismatch raises `CheckpointException` (the file is not the one the checkpoint was made for).
* `getCheckpointInfo()` returns `['position', 'count', 'file_size', 'file_mtime', 'caller_hash', 'timestamp', ...]`
  or `null` when there is no checkpoint.
