<?php

namespace gipfl\OpenRpc\Reflection;

use function explode;
use function implode;
use function preg_match;
use function preg_replace;
use function substr;
use function trim;

class MethodCommentParser
{
    const REGEXP_START_OF_COMMENT   = '~^\s*/\*\*\n~s';
    const REGEXP_COMMENT_LINE_START = '~^\s*\*\s?~';
    const REGEXP_END_OF_COMMENT     = '~\n\s*\*/\s*~s';
    const REGEXP_TAG_TYPE_VALUE     = '/^@([A-z0-9]+)\s+(.+?)$/';

    protected $paragraphs = [];
    protected $currentParagraph;

    /** @var MetaDataMethod */
    protected $meta;

    /** @var MetaDataTagParser|null */
    protected $currentTag;

    /** @var MetaDataTagSet */
    protected $tags;

    protected function __construct(MetaDataMethod $meta)
    {
        $this->meta = $meta;
        $this->tags = new MetaDataTagSet();
    }

    public function getTitle()
    {
        return $this->meta->title;
    }

    public function getParams()
    {
        return $this->getTags()->getParams();
    }

    public function getResultType()
    {
        return $this->getTags()->getReturnType();
    }

    public function getDescription()
    {
        return implode("\n", $this->paragraphs);
    }

    public function getTags()
    {
        return $this->tags;
    }

    protected function parseLine($line)
    {
        // Strip * at line start
        $line = preg_replace(self::REGEXP_COMMENT_LINE_START, '', $line);
        $line = trim($line);
        if (preg_match(self::REGEXP_TAG_TYPE_VALUE, $line, $match)) {
            $this->finishCurrentObjects();
            $this->currentTag = new MetaDataTagParser($match[1], $match[2]);
            return;
        }

        if ($this->currentTag) {
            $this->currentTag->appendValueString($line);
            return;
        }

        $this->eventuallyFinishCurrentTag();
        $this->appendToParagraph($line);
    }

    protected function appendToParagraph($line)
    {
        if (trim($line) === '') {
            $this->eventuallyFinishCurrentLine();
            return;
        }

        if ($this->currentParagraph === null) {
            $this->currentParagraph = & $this->paragraphs[];
            $this->currentParagraph = $line;
        } else {
            if (substr($line, 0, 2) === '  ') {
                $this->currentParagraph .= "\n" . $line;
            } else {
                $this->currentParagraph .= ' ' . $line;
            }
        }
    }

    protected function finishCurrentObjects()
    {
        $this->eventuallyFinishCurrentTag();
        $this->eventuallyFinishCurrentLine();
    }

    protected function eventuallyFinishCurrentTag()
    {
        if ($this->currentTag) {
            $this->tags->add($this->currentTag->getTag());
            $this->currentTag = null;
        }
    }

    protected function eventuallyFinishCurrentLine()
    {
        if ($this->currentParagraph !== null) {
            unset($this->currentParagraph);
            $this->currentParagraph = null;
        }
    }

    protected function parse($plain)
    {
        foreach (explode("\n", $plain) as $line) {
            $this->parseLine($line);
        }
        $this->finishCurrentObjects();
    }

    public static function parseMethod($methodName, $methodType, $raw)
    {
        $meta = new MetaDataMethod($methodName, $methodType);
        $self = new static($meta);
        $plain = (string) $raw;
        static::stripStartOfComment($plain);
        static::stripEndOfComment($plain);
        $self->parse($plain);
        $meta->addParsed($self);

        return $meta;
    }

    /**
     * Removes comment start -> /**
     *
     * @param $string
     */
    protected static function stripStartOfComment(&$string)
    {
        $string = preg_replace(self::REGEXP_START_OF_COMMENT, '', $string);
    }

    /**
     * Removes comment end ->  * /
     *
     * @param $string
     */
    protected static function stripEndOfComment(&$string)
    {
        $string = preg_replace(self::REGEXP_END_OF_COMMENT, "\n", $string);
    }
}
