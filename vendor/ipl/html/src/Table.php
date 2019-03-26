<?php

namespace ipl\Html;

use RuntimeException;
use Traversable;
use stdClass;

class Table extends BaseHtmlElement
{
    protected $contentSeparator = "\n";

    /** @var string */
    protected $tag = 'table';

    /** @var HtmlElement */
    private $caption;

    /** @var HtmlElement */
    private $header;

    /** @var HtmlElement */
    private $body;

    /** @var HtmlElement */
    private $footer;

    /**
     * @param array|ValidHtml|string $content
     * @return $this
     */
    public function add($content)
    {
        $this->ensureAssembled();

        if ($content instanceof BaseHtmlElement) {
            switch ($content->getTag()) {
                case 'tr':
                    $this->getBody()->add($content);
                    break;

                case 'thead':
                    parent::add($content);
                    $this->header = $content;
                    break;

                case 'tbody':
                    parent::add($content);
                    $this->body = $content;
                    break;

                case 'tfoot':
                    parent::add($content);
                    $this->footer = $content;
                    break;

                case 'caption':
                    if ($this->caption === null) {
                        $this->prepend($content);
                        $this->caption = $content;
                    } else {
                        throw new RuntimeException(
                            'Tables allow only one <caption> tag'
                        );
                    }
                    break;

                default:
                    $this->getBody()->add(static::row([$content]));
            }
        } elseif ($content instanceof stdClass) {
            $this->getBody()->add(static::row((array) $content));
        } elseif (is_array($content) || $content instanceof Traversable) {
            $this->getBody()->add(static::row($content));
        } else {
            $this->getBody()->add(static::row([$content]));
        }

        return $this;
    }

    /**
     * Set the table title
     *
     * Will be rendered as a "caption" HTML element
     *
     * @param $caption
     * @return $this
     */
    public function setCaption($caption)
    {
        if ($caption instanceof BaseHtmlElement && $caption->getTag() === 'caption') {
            $this->caption = $caption;
            $this->prepend($caption);
        } elseif ($this->caption === null) {
            $this->caption = new HtmlElement('caption', null, $caption);
            $this->prepend($this->caption);
        } else {
            $this->caption->setContent($caption);
        }

        return $this;
    }

    /**
     * Static helper creating a tr element
     *
     * @param Attributes|array $attributes
     * @param Html|array|string $content
     * @return HtmlElement
     */
    public static function tr($content = null, $attributes = null)
    {
        return Html::tag('tr', $attributes, $content);
    }

    /**
     * Static helper creating a th element
     *
     * @param Attributes|array $attributes
     * @param Html|array|string $content
     * @return HtmlElement
     */
    public static function th($content = null, $attributes = null)
    {
        return Html::tag('th', $attributes, $content);
    }

    /**
     * Static helper creating a td element
     *
     * @param Attributes|array $attributes
     * @param Html|array|string $content
     * @return HtmlElement
     */
    public static function td($content = null, $attributes = null)
    {
        return Html::tag('td', $attributes, $content);
    }

    /**
     * @param $row
     * @param null $attributes
     * @param string $tag
     * @return HtmlElement
     */
    public static function row($row, $attributes = null, $tag = 'td')
    {
        $tr = static::tr();
        foreach ((array) $row as $value) {
            $tr->add(Html::tag($tag, null, $value));
        }

        if ($attributes !== null) {
            $tr->setAttributes($attributes);
        }

        return $tr;
    }

    /**
     * @return HtmlElement
     */
    public function getBody()
    {
        if ($this->body === null) {
            $this->add(Html::tag('tbody')->setSeparator("\n"));
        }

        return $this->body;
    }

    /**
     * @return HtmlElement
     */
    public function getHeader()
    {
        if ($this->header === null) {
            $this->add(Html::tag('thead')->setSeparator("\n"));
        }

        return $this->header;
    }

    /**
     * @return HtmlElement
     */
    public function getFooter()
    {
        if ($this->footer === null) {
            $this->add(Html::tag('tfoot')->setSeparator("\n"));
        }

        return $this->footer;
    }

    /**
     * @return HtmlElement
     */
    public function nextBody()
    {
        $this->body = null;

        return $this->getBody();
    }

    /**
     * @return HtmlElement
     */
    public function nextHeader()
    {
        $this->header = null;

        return $this->getHeader();
    }
}
