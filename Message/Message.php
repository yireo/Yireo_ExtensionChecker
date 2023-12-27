<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Message;

class Message
{
    private MessageGroupLabels $messageGroupLabels;
    private string $message;
    private string $group;
    private string $suggestion;
    private string $module;

    /**
     * @param MessageGroupLabels $messageGroupLabels
     * @param string $message
     * @param string $group
     * @param string $suggestion
     */
    public function __construct(
        MessageGroupLabels $messageGroupLabels,
        string $message,
        string $group,
        string $suggestion = ''
    ) {
        $this->messageGroupLabels = $messageGroupLabels;
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
        $groupLabels = $this->messageGroupLabels->get();
        if (isset($groupLabels[$this->getGroup()])) {
            return $groupLabels[$this->getGroup()];
        }

        return $this->getGroup();
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->getGroup() === MessageGroupLabels::GROUP_EXCEPTION;
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
            'suggestion' => $this->getSuggestion()
        ];
    }
}
