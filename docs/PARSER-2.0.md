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

## 3. Buffer and big records

* The scanner (`ChunkOnixParser::iterateRecords`, private) keeps at most one unfinished record plus one chunk
  in memory. It no longer trims by guessing: bytes are never cut.
* A record bigger than the chunk size is read over several chunks. Growth stops at `setMaxRecordSize($bytes)`
  (default 32 MB; real DILICOM records are about 15 KB). Over that, `Exception\RecordTooLargeException` is thrown.
  `getByteOffset()` is the offset of that record's `<Product`.
* A file that ends inside a record throws `Exception\TruncatedFileException` (with the offset). It used to end
  quietly and drop the last record.
* `parseWithCheckpoints()` does not collect callback results any more. It returns a summary:
  `['processed' => n handled in this call, 'count' => total handled, 'position' => checkpoint offset
  (file size when finished), 'stopped' => 'after'|'before'|null, 'completed' => bool]`.
  (1.x returned the list of callback results: that list is gone, it grew with the file.)
* Tag scan: `<Product` must be followed by `>`, space, tab, CR or LF. Prefixed tags (`<onix:Product>`) and
  short tags (`<product>`) are **not** supported. The closing tag must be `</Product>` (case sensitive).

## 4. New getters (all additive, no 1.x getter was renamed)

| Where | Method | Notes |
|---|---|---|
| `Product` | `getSupplyDetails()`, `getSupplyDetailByGln($gln)` | one `SupplyDetail` per element, nothing merged |
| `SupplyDetail` | `getSupplierGln()`, `getSupplierName()`, `getSupplierRole()`, `getSupplierIdentifiers()`, `getAvailability()`, `getAvailabilityName()`, `getReturnsCodeType()`, `getReturnsCode()`, `getReturnsConditions()`, `getSupplyDates()`, `getSupplyDate($role)`, `getOrderTime()`, `getUnpricedItemType()`, `getPrices()` | `getAvailability()` is the code exactly as sent; the library does not decide what is "in stock" |
| `Price` | `getTerritory()`, `getPriceQualifier()`, `getPriceStatus()`, `getDates()`, `getDate($role)`, `getTaxes()`, `getDiscountCodes()`, `getUnpricedItemType()` | `SupplyDetail::getPrices()` also holds prices without an amount |
| `Territory` | `getCountriesIncluded()`, `getRegionsIncluded()`, `getCountriesExcluded()`, `getRegionsExcluded()`, `coversCountry($cc, $extraRegions = [])` | only `WORLD` is known; other regions need `$extraRegions` |
| `Tax` | `getType()`, `getRateCode()`, `getRatePercent()`, `getRatePercentRaw()`, `getTaxableAmount()`, `getTaxAmount()` | a price can carry several |
| `Product` | `getMarketTerritories()` | `ProductSupply/Market/Territory` |
| `Product` | `getPresentBlocks()`, `hasBlock($name)`, `getBlockXml($name)` | names are the ONIX 3.0 element names, in message order |
| `Product` | `getMeasures()`, `getExtents()`, `getHeightMm()`, `getWidthMm()`, `getThicknessMm()`, `getWeightGrams()`, `getPageCount()` | `getHeight()` etc. still return `['value', 'unit', 'unit_name', 'type']` |
| `Product` | `getImprintGln()`, `getPublishingDates()` | |
| `Product` | `getResourceVersions($contentType = null)`, `getFrontCoverNear($px = 400)` | |
| `ResourceVersion` | `getContentType()`, `getUrl()`, `getHeight()`, `getWidth()`, `getBytes()`, `getFilename()`, `getImageFormat()`, `getContentDate($role)`, `getLastUpdated()` | role 17 = last updated |
| `Title` / `Description` | `getTextCase()`, `getLanguage()` / `getLanguage()` | attribute form, or element form |

Dates are arrays `['role', 'format', 'date']`; the date string is exactly as sent. Reading `dateformat`,
`textformat`, `textcase` and `language` works as attribute (real DILICOM files) and as element.

### Known limits

* `ExtentUnit 05` is accepted for the page count because the DILICOM checklist asks for it. In ONIX list 24 the
  code 05 is not "pages" for every sender; `getExtents()` returns the unit so you can check it.
* Several `SupplyDetail`: the 1.x single-value getters (`getAvailabilityCode()`, `getSupplierGLN()`,
  `getSupplierName()`) still return the **last** one. Use `getSupplyDetails()` for multi-distribution.
* The parser reads facts. It does not choose the Morocco price, the availability to show, or the cover to keep.
