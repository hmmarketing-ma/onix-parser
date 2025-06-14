# Changelog

All notable changes to the ONIX Parser library will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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