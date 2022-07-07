<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Report;

class Message
{
    public const TYPE_NOTICE = 'notice';
    public const TYPE_WARNING = 'warning';
    public const TYPE_DEBUG = 'debug';
    
    /**
     * @var string
     */
    private $text;
    
    /**
     * @var string
     */
    private $type;
    
    /**
     * @param string $text
     * @param string $type
     */
    public function __construct(
        string $text,
        string $type
    ) {
        $this->text = $text;
        $this->type = $type;
    }
    
    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }
    
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    public function isNotice(): bool
    {
        return $this->getType() === self::TYPE_NOTICE;
    }
    
    public function isWarning(): bool
    {
        return $this->getType() === self::TYPE_WARNING;
    }
    
    public function isDebug(): bool
    {
        return $this->getType() === self::TYPE_DEBUG;
    }
}
