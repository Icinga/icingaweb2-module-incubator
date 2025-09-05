<?php

namespace gipfl\IcingaWeb2\Table\Extension;

use gipfl\IcingaWeb2\Url;

// Could also be a static method, MultiSelect::enable($table)
trait MultiSelect
{
    protected function enableMultiSelect($url, $sourceUrl, array $keys)
    {
        /** @var $table \ipl\Html\BaseHtmlElement */
        $table = $this;
        $table->addAttributes([
            'class' => 'multiselect'
        ]);

        $prefix = 'data-icinga-multiselect';
        $multi = [
            "$prefix-url"         => Url::fromPath($url),
            "$prefix-controllers" => Url::fromPath($sourceUrl),
            "$prefix-data"        => implode(',', $keys),
        ];

        $table->addAttributes($multi);

        return $this;
    }
}
