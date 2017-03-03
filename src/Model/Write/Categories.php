<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Helper;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;

class Categories implements WriterInterface
{
    /**
     * @var EavIterator
     */
    protected $iterator;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Categories constructor.
     *
     * @param EavIterator $iterator
     * @param StoreManager $storeManager
     * @param Config $config
     * @param Helper $helper
     */
    public function __construct(EavIterator $iterator, StoreManager $storeManager, Config $config, Helper $helper)
    {
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(Writer $writer, XmlWriter $xml)
    {
        $xml->startElement('categories');
        $writer->flush();

        $this->writeCategory($xml, null, ['entity_id' => 1, 'name' => 'Root', 'position' => 0]);
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->config->isEnabled($store)) {
                $this->exportStore($writer, $xml, $store);
            }
        }

        $xml->endElement(); // categories
        $writer->flush();
        return $this;
    }

    /**
     * @param Writer $writer
     * @param XmlWriter $xml
     * @param Store $store
     * @return $this
     */
    protected function exportStore(Writer $writer, XmlWriter $xml, Store $store)
    {
        // Set root category as exported
        $exportedCategories = [1 => true];
        $storeId = $store->getId();
        $this->iterator->setStoreId($storeId);

        foreach ($this->iterator as $data) {
            // Store root category extend name so it is clear in tweakwise
            // Always export store root category whether it is enabled or not
            if ($data['parent_id'] == 1) {
                $data['name'] = $store->getName() . ' - ' . $data['name'] ;
            } elseif (!isset($data['is_active']) || !$data['is_active']) {
                continue;
            }
            
            if (!isset($exportedCategories[$data['parent_id']])) {
                continue;
            }

            // Set category as exported
            $exportedCategories[$data['entity_id']] = true;
            $this->writeCategory($xml, $storeId, $data);

            $writer->flush();
        }
        return $this;
    }

    /**
     * @param XmlWriter $xml
     * @param int $storeId
     * @param array $data
     * @return $this
     */
    protected function writeCategory(XmlWriter $xml, $storeId, array $data)
    {
        $tweakwiseId = $this->helper->getTweakwiseId($storeId, $data['entity_id']);

        $xml->startElement('category');
        $xml->writeElement('categoryid', $tweakwiseId);
        $xml->writeElement('rank', $data['position']);
        $xml->writeElement('name', $data['name']);

        if (isset($data['parent_id']) && $data['parent_id']) {
            $xml->startElement('parents');

            $parentId = $data['parent_id'];
            if ($parentId != 1) {
                $parentId = $this->helper->getTweakwiseId($storeId, $data['parent_id']);
            }
            $xml->writeElement('categoryid', $parentId);
            $xml->endElement(); // </parents>
        }

        $xml->endElement(); // </category>

        return $this;
    }
}