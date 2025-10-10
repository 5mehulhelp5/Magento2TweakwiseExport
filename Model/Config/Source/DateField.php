<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class DateField implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'all' => 'All Dates',
            'min' => 'Min Date',
            'max' => 'Max Date',
        ];
    }
}
