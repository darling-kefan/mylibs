<?php

namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;

use Illuminate\Support\Str;

/**
 * 精简日志类
 * 
 * Example:
 *
 * \App\Services\FLogger::instance('ka')->info('write a log {user} {title}', ['user'=>'shouqiang', 'title'=>'Hello World!']);
 *
 * @author shoquiang
 */
class FLogger
{
    // Logger instance
    protected static $instances = array();

    public static function instance($channel = 'tvm')
    {
        if (!isset(static::$instances[$channel])) {
            static::$instances[$channel] = new self($channel);
        }
        return static::$instances[$channel];
    }

    // Monolog instance
    protected $monolog = null;

    // Log path
    protected $logPath = null;

    // Whether to write context, the default value is false
    protected $context = false;

    // Whether to rotate log
    protected $rotate  = true;

    // The maximum number of log
    protected $maxFiles = 7;

    protected function __construct($channel)
    {
        $this->setLogPath();
        $this->monolog = new Logger($channel);
        $this->monolog->pushProcessor(new PsrLogMessageProcessor());
        $this->pushFileHandler($channel);
    }

    /**
     * Add log handler
     */
    public function pushFileHandler($file)
    {
        $formatter = new LineFormatter($this->recordFormat());
        $logFile = $this->logPath . DIRECTORY_SEPARATOR . $file . '.log';
        
        if ($this->rotate) {
            $fileHandler = new RotatingFileHandler($logFile, $this->maxFiles);
        } else {
            $fileHandler = new StreamHandler($logFile, Logger::DEBUG);
        }
        $formatter = new LineFormatter($this->recordFormat());
        
        $fileHandler->setFormatter($formatter);
        $this->monolog->pushHandler($fileHandler);
        
        return $this;
    }

    /**
     * Write to the log
     */
    public function info($record, $context)
    {
        $this->monolog->addInfo($record, $context);
    }

    /**
     * Wether to write context
     */
    public function context($isContext = false)
    {
        $this->context = $isContext;
        
        return $this;
    }

    /**
     * Wether to rotate log
     */
    public function rotate($isRotate = false)
    {
        $this->rotate = $isRotate;

        return $this;
    }

    /**
     * Set maxFiles
     */
    public function setMaxFiles($num)
    {
        $this->maxFiles = $num;

        return $this;
    }

    /**
     * Get Monolog instance
     */
    public function getMonolog()
    {
        return $this->monolog;
    }

    /**
     * Set log format
     */
    protected function recordFormat()
    {
        $format = "[%datetime%] [%process_id%] %message% %context%\n";
        $format = str_replace('%process_id%', $this->generateProcessIdentifer(), $format);
        if (!$this->context) $format = str_replace(' %context%', '', $format);
        
        return $format;
    }

    /**
     * Set log path
     */
    protected function setLogPath()
    {
        if (is_dir(env('FLOGGER_PATH'))) {
            $this->logPath = env('FLOGGER_PATH');
        } else {
            $this->logPath = storage_path('logs');
        }
    }

    /**
     * Generate process identifer
     */
    protected function generateProcessIdentifer()
    {
        return sha1(uniqid('', true).Str::random(25).microtime(true)).'-'.getmypid();
    }
}
