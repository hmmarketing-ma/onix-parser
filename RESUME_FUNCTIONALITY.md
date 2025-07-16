# True File Position Resume Implementation

## Overview

This implementation adds true file position resume capability to the ONIX parser library, allowing it to handle extremely large files (48,000+ products) without memory issues or hanging. The system can pause and resume parsing at exact byte positions, surviving server restarts and timeouts.

## Architecture

### Core Components

1. **ResumableOnixParser** - Main parser class extending OnixParser
2. **CheckpointManager** - Manages checkpoint creation, validation, and cleanup
3. **ParserState** - Serializable parser state and context
4. **ResumePoint** - Represents a specific resume position with validation
5. **FilePositionTracker** - Tracks byte positions during parsing

### Key Features

- **Byte-level precision**: Resume at exact file position
- **State persistence**: Complete parser context serialization  
- **Validation**: File integrity and resume point validation
- **Error recovery**: Automatic checkpoint repair and fallback
- **Performance**: Minimal overhead and memory usage
- **Compatibility**: Works with existing code without changes

## Usage

### Basic Usage

```php
use ONIXParser\ResumableOnixParser;
use ONIXParser\Resume\CheckpointManager;
use ONIXParser\Logger;

// Create parser with resume capability
$logger = new Logger(Logger::INFO);
$checkpointManager = new CheckpointManager('./checkpoints', $logger);
$parser = new ResumableOnixParser($logger, $checkpointManager);

// Parse with checkpoints enabled
$options = [
    'enable_checkpoints' => true,
    'checkpoint_interval' => 100,  // Checkpoint every 100 products
    'auto_resume' => true,         // Auto-resume if checkpoint exists
    'callback' => function($product, $index, $total) {
        // Process product...
        return true; // Continue processing
    }
];

$onix = $parser->parseFileStreaming('large_file.xml', $options);
```

### Advanced Configuration

```php
// Custom checkpoint configuration
$checkpointManager = new CheckpointManager(
    './checkpoints',    // Checkpoint directory
    $logger,           // Logger instance
    86400,             // Max checkpoint age (24 hours)
    10,                // Max checkpoints to keep
    true               // Enable compression
);

// Manual checkpoint control
$options = [
    'enable_checkpoints' => true,
    'checkpoint_interval' => 50,
    'checkpoint_id' => 'custom_checkpoint_id',
    'auto_resume' => false,
    'resume_from_checkpoint' => './checkpoints/specific.checkpoint'
];
```

### Handling Interruptions

```php
// In your processing callback
$callback = function($product, $index, $total) {
    // Process product...
    
    // Check for timeout/memory limits
    if (memory_get_usage() > 512 * 1024 * 1024) { // 512MB limit
        error_log("Memory limit reached, stopping at product $index");
        return false; // Stop processing, checkpoint will be saved
    }
    
    return true;
};
```

## Options Reference

### Resume Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enable_checkpoints` | bool | false | Enable checkpoint creation |
| `checkpoint_interval` | int | 100 | Products between checkpoints |
| `checkpoint_id` | string | auto | Custom checkpoint identifier |
| `auto_resume` | bool | true | Automatically resume from checkpoint |
| `resume_from_checkpoint` | string | null | Specific checkpoint file to resume from |
| `checkpoint_dir` | string | null | Custom checkpoint directory |

### Checkpoint Manager Options

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `checkpointDir` | string | sys_temp_dir | Directory for checkpoint files |
| `maxCheckpointAge` | int | 86400 | Max age in seconds (24 hours) |
| `maxCheckpoints` | int | 10 | Maximum checkpoints to keep |
| `compressCheckpoints` | bool | false | Enable checkpoint compression |

## File Structure

```
src/
├── ResumableOnixParser.php          # Main resumable parser
├── Resume/
│   ├── CheckpointManager.php        # Checkpoint management
│   ├── ParserState.php             # Parser state serialization
│   ├── ResumePoint.php             # Resume point representation
│   └── FilePositionTracker.php     # File position tracking
├── Exception/
│   ├── CheckpointException.php     # Checkpoint-related exceptions
│   └── ResumeException.php         # Resume-related exceptions
├── examples/
│   └── resumable_parsing.php       # Usage examples
└── tests/
    └── ResumableParserTest.php      # Unit tests
```

## Checkpoint Data Format

Checkpoints are stored as JSON files with the following structure:

```json
{
    "version": "1.0",
    "created_at": 1640995200,
    "checkpoint_id": "abc123...",
    "resume_point": {
        "byte_position": 1234567,
        "file_path": "/path/to/file.xml",
        "file_hash": "md5_hash_of_file",
        "file_size": 10485760,
        "xml_context": "<Product>...",
        "expected_element": "Product",
        "parser_state": {
            "has_namespace": true,
            "namespace_uri": "http://www.editeur.org/onix/3.0/reference",
            "header_processed": true,
            "version_detected": true,
            "onix_version": "3.0",
            "total_product_count": 1000,
            "processed_product_count": 500,
            "skipped_product_count": 0,
            "parsing_phase": "processing",
            "timestamp": 1640995200
        },
        "metadata": {
            "session_products": 500,
            "is_final": false
        }
    }
}
```

## Error Handling

### Common Exceptions

- `CheckpointException`: Checkpoint creation/loading failures
- `ResumeException`: Resume operation failures
- `ResumeException::fileIntegrityFailed`: File modified since checkpoint
- `ResumeException::invalidResumePoint`: Invalid resume position

### Recovery Strategies

1. **Automatic fallback**: If resume fails, falls back to normal parsing
2. **Checkpoint repair**: Attempts to repair corrupted checkpoints
3. **File validation**: Ensures file hasn't changed since checkpoint
4. **Position validation**: Verifies XML context at resume point

## Performance Considerations

### Memory Usage

- Constant memory usage regardless of file size
- Only current product kept in memory
- Checkpoint data is minimal and compressed

### Checkpoint Overhead

- Minimal impact on parsing performance
- Checkpoints created asynchronously where possible
- Configurable checkpoint frequency

### File I/O Optimization

- Efficient file positioning with fseek()
- Minimal file reads for validation
- Atomic checkpoint writes

## Monitoring and Debugging

### Parser Statistics

```php
$stats = $parser->getStats();
// Returns:
// - session_products: Products processed in current session
// - last_checkpoint_count: Last checkpoint product count
// - checkpoints_enabled: Whether checkpoints are enabled
// - is_resuming: Whether currently resuming
// - position_tracker: File position tracking stats
```

### Checkpoint Management

```php
// List all checkpoints
$checkpoints = $checkpointManager->listCheckpoints();

// Clean up old checkpoints
$cleaned = $checkpointManager->cleanupOldCheckpoints();

// Get checkpoint statistics
$stats = $checkpointManager->getStats();
```

## Testing

Run the test suite:

```bash
vendor/bin/phpunit tests/ResumableParserTest.php
```

Run the example:

```bash
php examples/resumable_parsing.php
```

## Troubleshooting

### Common Issues

1. **Checkpoint directory permissions**: Ensure write permissions
2. **File modifications**: File changed since checkpoint creation
3. **Memory limits**: Adjust PHP memory_limit for large files
4. **Timeout issues**: Use checkpoint intervals to avoid timeouts

### Debug Mode

Enable debug logging to see detailed checkpoint operations:

```php
$logger = new Logger(Logger::DEBUG, 'debug.log');
```

## Integration with Existing Code

The resumable parser is fully backward compatible:

```php
// Existing code works unchanged
$parser = new OnixParser($logger);
$onix = $parser->parseFileStreaming($file, $options);

// Just replace with resumable parser
$parser = new ResumableOnixParser($logger);
$onix = $parser->parseFileStreaming($file, $options);
```

## Best Practices

1. **Checkpoint frequency**: Balance between overhead and recovery time
2. **Memory monitoring**: Check memory usage in callbacks
3. **Error handling**: Always handle resume exceptions gracefully
4. **Cleanup**: Regularly clean up old checkpoints
5. **Testing**: Test resume functionality with your specific use case

This implementation solves the 48,000 product limitation by providing true resumable parsing with minimal memory overhead and robust error recovery.