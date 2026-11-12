# Changelog

All notable changes to the ONIX Parser library will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.2] - 2026-11-12

### Added
- `Product::getHeadXml()`: XML between the `<Product>` start tag and the first block element (whitespace included),
  same string in full and blocks-only mode.
- `Product::getProductIdentifiers()`: every direct `ProductIdentifier` child as `['type', 'value']` (a second 03 too).

### Fixed (behaviour change)
- `getEan()` / `getIsbn()` read direct children of `<Product>` only. Before, a descendant XPath could pick a
  `ProductIdentifier` of a related product inside `RelatedMaterial` and give the wrong key without any warning.
  Records with a top-level 03/15 are unchanged (initial file: EAN on 27,383 of 27,383, same as before).
  With several direct 03, `getEan()` still returns the first.

## [2.0.1] - 2026-11-12

### Fixed
- **`Product::getBlockXml()` returned only the FIRST element of a repeated block.** `ProductSupply` repeats
  (one per market: first FR/BE, second WORLD minus FR/BE = the one that covers Morocco). Real data: DIF451053808
  has 4,057 of 79,000 records with 2 `ProductSupply`, DIF451048537 has 39. Now returns every element in file
  order (several roots joined), `getBlockElements($name)` returns them one by one. `ProductSupply` is the only
  block that repeats in ONIX 3.0; the same all-elements rule is applied to every block name.
- `SupplyDetail::getMarketTerritories()`: each SupplyDetail is linked to the `Market` of its own `ProductSupply`.
- `Product::getXml()` and the block XML are UTF-8 (the root was written with `&#xE9;`-style entities).

### Added
- `ChunkOnixParser::setBlocksOnly(true)` / `OnixParser::parseProductBlocks()`: ids, notification type, present
  blocks, block XML and per-SupplyDetail GLN/name/availability only. Block strings are identical to the full mode
  (SHA-256 equal on 27,383 and 2 x 79,000 real records). 27k file: 2,652 rec/s against 765 rec/s.

Note: tag v2.0.0 was moved once before it was ever pushed; it is not moved again. This fix is v2.0.1.

## [2.0.0] - 2026-11-11

DILICOM pipeline rebuild (STORY-879). Details: docs/PARSER-2.0.md.

### Fixed
- `ProductAvailability` was never read (xpath context). Now read per `SupplyDetail`.
- Height, width, thickness, weight and page count were always empty (namespace on nested xpath). Units are exposed.
  Page count accepts `ExtentType 00/03` with `ExtentUnit 03` only; unit 05 (minutes) is `getDurationMinutes()`.
- Checkpoint write is atomic (temp + fsync + rename). No more `md5_file()` of the whole file at every checkpoint.
- A record larger than the buffer is no longer cut and lost; a file that ends inside a record no longer drops it silently.
- Two divisions by zero on very fast runs; PHP 8.4 implicit-nullable deprecations; `filesize()` before `file_exists()`.
- `LIBXML_NONET` on every load. `\r` accepted after `<Product`.

### Added
- `SupplyDetail`, `Territory`, `Tax`, `ResourceVersion` models; full `Price` model.
- `Product::getSupplyDetails()`, `getPresentBlocks()`, `hasBlock()`, `getBlockXml()`, `getMarketTerritories()`,
  `getMeasures()`, `getExtents()`, `getHeightMm()` ..., `getImprintGln()`, `getPublishingDates()`,
  `getResourceVersions()`, `getFrontCoverNear()`.
- `ChunkOnixParser::STOP_AFTER`, `STOP_BEFORE`, `setCheckpointStore()`, `setFileHash()`, `setMaxRecordSize()`.
- `Resume\CheckpointStore`, `Resume\FileCheckpointStore`, `Exception\RecordTooLargeException`, `Exception\TruncatedFileException`.
- `dateformat` / `textformat` / `textcase` / `language` read as attribute and as element.
- Real-file field-coverage harness (`tests/tools/field-coverage.php`) and resume proof (`tests/tools/resume-proof.php`).

### Changed (breaking)
- `ChunkOnixParser::parseWithCheckpoints()` returns a summary array (`processed`, `count`, `position`, `stopped`, `completed`).
- A damaged checkpoint, or one made for another file, throws `CheckpointException` (it used to restart at byte 0).
- Minimum PHP 8.0.

## [1.7.0] - 2025-07-04

### Added

#### DILICOM Compliance Methods (7 total)
**Core DILICOM Methods:**
- `getProductComposition()` - Get ProductComposition code for professional access control (00/10/11/20/30/31)
- `getTradeCategory()` - Get TradeCategory code for school book identification (08 for school books)
- `getEditionType()` - Get EditionType code for teacher access control (TCH for teacher-only)

**Convenience Access Control Methods:**
- `requiresProfessionalAccess()` - Check if product requires professional access (composition 11/20/30/31)
- `isSchoolBook()` - Check if product is a school book/textbook (category 08)
- `isTeacherOnly()` - Check if product is teacher-only edition (type TCH)
- `requiresAccessControls()` - Check if any access controls are required

#### New FieldMappings
- Added `dilicom_compliance` section with XPath mappings for:
  - `product_composition` - Primary and fallback XPath for ProductComposition
  - `trade_category` - Primary and alternative XPath for TradeCategory  
  - `edition_type` - XPath for EditionType

### Technical Details
- Added comprehensive XPath support for both namespaced and non-namespaced ONIX XML
- Methods follow existing library patterns with proper error handling
- Supports DILICOM compliance requirements for French book distribution
- Full test coverage with sample ONIX data validation

## [1.6.0] - 2025-06-14

### Added

#### New Product Methods (16 total)
**High Priority Methods:**
- `getAvailabilityName()` - Get human-readable availability status using CodeMaps
- `getPageCount()` - Extract page count from ONIX Extent elements
- `getLanguageCode()` - Get primary language code (ISO 639)
- `getLanguageName()` - Get human-readable language name

**Medium Priority Methods:**
- `getProductFormDetail()` - Get product form detail code
- `getProductFormDetailName()` - Get human-readable product form detail name
- `getImprintName()` - Get publisher imprint name
- `getHeight()` - Get height measurement with unit conversion
- `getWidth()` - Get width measurement with unit conversion
- `getThickness()` - Get thickness measurement with unit conversion
- `getWeight()` - Get weight measurement with unit conversion

**Publishing Metadata Methods:**
- `getCountryOfPublication()` - Get country of publication code
- `getCountryOfPublicationName()` - Get human-readable country name
- `getCityOfPublication()` - Get city of publication
- `getCopyrightYear()` - Get copyright year
- `getFirstPublicationYear()` - Extract year from publication date
- `getEditionNumber()` - Get edition number
- `getEditionStatement()` - Get edition statement

#### New CodeMaps
- **Product Form Detail Map** (19 entries) - ONIX List 175 codes (A101-A305)
  - Examples: 'A101' => 'Hardcover book with dust jacket', 'A301' => 'Basic ebook'
- **Language Code Map** (29 entries) - ISO 639 language codes
  - Examples: 'fra' => 'French', 'eng' => 'English', 'spa' => 'Spanish'
- **Country Code Map** (30 entries) - ISO 3166-1 country codes
  - Examples: 'FR' => 'France', 'US' => 'United States', 'GB' => 'United Kingdom'
- **Measure Unit Map** (9 entries) - ONIX List 50 measurement units
  - Examples: 'mm' => 'Millimeters', 'cm' => 'Centimeters', 'gr' => 'Grams'

#### Enhanced FieldMappings
- **Language mappings** - XPath for language detection and extraction
  - Primary language detection with role-based filtering
  - Support for both namespaced and non-namespaced XML
- **Publishing metadata mappings** - XPath for publishing information
  - Country and city of publication
  - Copyright year extraction
  - Edition number and statement

#### Enhanced Price Model
- `getPriceTypeName()` - Get human-readable price type names
  - Integration with existing CodeMaps price type mapping
  - Examples: '04' => 'Fixed retail price including tax'

### Enhanced

#### Product Model
- **Physical measurements** now return structured arrays with unit conversion
  - Format: `['value' => float, 'unit' => string, 'unit_name' => string]`
  - Automatic unit name resolution using CodeMaps
- **Language detection** with fallback mechanisms
  - Primary language detection (role '01')
  - Fallback to any available language
- **Publishing information** extraction with proper XPath handling
- **Edition information** with comprehensive metadata support

#### FieldMappings Architecture
- Added comprehensive XPath mappings for new features
- Support for both namespaced and non-namespaced XML
- Consistent structure following existing patterns

#### CodeMaps Architecture
- Extended `getAllMaps()` method to include new mappings
- Consistent naming conventions
- Well-documented code mappings with proper ONIX list references

### Technical Improvements

#### Architecture
- **Proper separation of concerns** - XML parsing stays in ONIX library
- **CodeMaps integration** - All human-readable translations use CodeMaps
- **FieldMappings integration** - All XPath queries use FieldMappings
- **Namespace handling** - Improved support for namespaced XML documents

#### Documentation
- **Comprehensive PHPDoc comments** for all new methods
- **Type hints** for improved IDE support and type safety
- **Usage examples** in method documentation
- **Proper return type documentation**

#### Testing
- **NewMethodsTest.php** - Comprehensive test suite for all new features
- **Real ONIX data testing** - Tested with actual ONIX 3.0 sample files
- **CodeMaps verification** - Ensures all mappings are properly loaded
- **Method availability checks** - Verifies all new methods are accessible

### Performance

#### Memory Efficiency
- **Streaming-compatible** - New methods work with streaming parser
- **Lazy evaluation** - Only processes data when requested
- **Minimal overhead** - Efficient XPath queries using existing patterns

#### Compatibility
- **Backward compatible** - All existing functionality preserved
- **Namespace agnostic** - Works with both namespaced and non-namespaced XML
- **ONIX 3.0 compliant** - Follows official ONIX specifications

### Bug Fixes
- **Namespace warnings** - Improved handling of namespaced XML (warnings are expected behavior)
- **XPath fallbacks** - Proper fallback mechanisms for missing data
- **Null safety** - Graceful handling of missing XML elements

## [1.5.1] - Previous Release
- Enhanced price type mapping functionality
- Improved ONIX parsing stability
- Bug fixes and performance improvements

## [1.5.0] - Previous Release
- Added comprehensive price type support
- Enhanced subject classification
- Improved streaming parser performance

---

## Migration Guide

### Upgrading to 1.6.0

The new version is fully backward compatible. To take advantage of new features:

1. **Update your dependencies**:
   ```bash
   composer update hm-marketing/onix-parser
   ```

2. **Use new methods** (all are optional):
   ```php
   // Physical details
   $pageCount = $product->getPageCount();
   $dimensions = [
       'height' => $product->getHeight(),
       'width' => $product->getWidth(),
       'weight' => $product->getWeight()
   ];
   
   // Language info
   $language = [
       'code' => $product->getLanguageCode(),
       'name' => $product->getLanguageName()
   ];
   
   // Enhanced availability
   $availability = [
       'code' => $product->getAvailabilityCode(),
       'name' => $product->getAvailabilityName()
   ];
   ```

3. **Use new CodeMaps** for custom applications:
   ```php
   use ONIXParser\CodeMaps;
   
   $languageMap = CodeMaps::getLanguageCodeMap();
   $countryMap = CodeMaps::getCountryCodeMap();
   $measureMap = CodeMaps::getMeasureUnitMap();
   ```

### Breaking Changes
**None** - This release is fully backward compatible.

### Deprecated Features
**None** - All existing features remain supported.

## Future Roadmap

### Planned Features
- **Enhanced contributor information** - Detailed author/contributor metadata
- **Publication date details** - More granular date handling
- **Extended physical measurements** - Additional measurement types
- **Audience information** - Target audience and age group details
- **Sales restrictions** - Territory and sales restriction handling

### Potential Enhancements
- **Measurement conversions** - Automatic unit conversions (metric/imperial)
- **Language hierarchies** - Support for multiple languages per product
- **Extended country information** - Regional and territory support
- **Performance optimizations** - Further memory and speed improvements

---

*For more information, see the [README.md](README.md) documentation.*