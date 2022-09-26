<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Message;

class Message
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $group;

    /**
     * @var string
     */
    private $suggestion;

    /**
     * @param string $message
     * @param string $group
     * @param string $suggestion
     */
    public function __construct(
        string $message,
        string $group,
        string $suggestion = '',
    ) {
        $this->message = $message;
        $this->group = $group;
        $this->suggestion = $suggestion;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getGroupLabel(): string
    {
        $groupLabels = MessageBucket::getGroupLabels();
        if (isset($groupLabels[$this->getGroup()])) {
            return $groupLabels[$this->getGroup()];
        }

        return $this->getGroup();
    }

    /**
     * @return string
     */
    public function getSuggestion(): string
    {
        return $this->suggestion;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'group' => $this->getGroup(),
            'suggestion' => $this->getSuggestion(),
        ];
    }
}
