<?php

namespace ONIXParser;

/**
 * Simple logger class for ONIX Parser
 */
class Logger
{
    /** @var int Log level */
    private $level;
    
    /** @var string Log file path */
    private $logFile;
    
    /** @var resource|null File handle */
    private $fileHandle;
    
    /** Log levels */
    const ERROR = 1;
    const WARNING = 2;
    const INFO = 3;
    const DEBUG = 4;
    
    /**
     * Constructor
     *
     * @param int $level Log level (default: INFO)
     * @param string|null $logFile Optional log file path
     */
    public function __construct(int $level = self::INFO, ?string $logFile = null)
    {
        $this->level = $level;
        $this->logFile = $logFile;
        
        if ($this->logFile) {
            $this->fileHandle = fopen($this->logFile, 'a');
        }
    }
    
    /**
     * Destructor - close file handle if open
     */
    public function __destruct()
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }
    
    /**
     * Log an error message
     *
     * @param string $message
     */
    public function error(string $message): void
    {
        $this->log(self::ERROR, $message);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message
     */
    public function warning(string $message): void
    {
        $this->log(self::WARNING, $message);
    }
    
    /**
     * Log an info message
     *
     * @param string $message
     */
    public function info(string $message): void
    {
        $this->log(self::INFO, $message);
    }
    
    /**
     * Log a debug message
     *
     * @param string $message
     */
    public function debug(string $message): void
    {
        $this->log(self::DEBUG, $message);
    }
    
    /**
     * Log a message
     *
     * @param int $level
     * @param string $message
     */
    private function log(int $level, string $message): void
    {
        if ($level > $this->level) {
            return;
        }
        
        $levelName = $this->getLevelName($level);
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] $levelName: $message" . PHP_EOL;
        
        if ($this->fileHandle) {
            fwrite($this->fileHandle, $formattedMessage);
        } else {
            echo $formattedMessage;
        }
    }
    
    /**
     * Get level name from level constant
     *
     * @param int $level
     * @return string
     */
    private function getLevelName(int $level): string
    {
        switch ($level) {
            case self::ERROR:
                return 'ERROR';
            case self::WARNING:
                return 'WARNING';
            case self::INFO:
                return 'INFO';
            case self::DEBUG:
                return 'DEBUG';
            default:
                return 'UNKNOWN';
        }
    }
}