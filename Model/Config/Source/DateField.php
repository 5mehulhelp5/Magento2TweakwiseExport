<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class DateField implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $dateFields = [
            'all' => 'All Dates',
            'min' => 'Min Date',
            'max' => 'Max Date',
        ];

        return $dateFields;
    }
}
