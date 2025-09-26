<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model;

use DateTime;
use IntlDateFormatter;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use SplFileInfo;
use Magento\Framework\App\ProductMetadata as CommunityProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Helper
{
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var TimezoneInterface
     */
    protected $localDate;

    protected static ?array $attributeSetNames = null;

    /**
     * Helper constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param Config $config
     * @param TimezoneInterface $localDate
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        Config $config,
        TimezoneInterface $localDate,
        protected readonly AttributeSetRepositoryInterface $attributeSetRepository,
        protected readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productMetadata = $productMetadata;
        $this->config = $config;
        $this->localDate = $localDate;
    }

    /**
     * @param int $storeId
     * @param int $entityId
     * @return string
     */
    public function getTweakwiseId(int $storeId, int $entityId): string
    {
        if (!$storeId) {
            return $entityId;
        }

        // Prefix 1 is to make sure it stays the same length when casting to int
        return '1' . str_pad($storeId, 4, '0', STR_PAD_LEFT) . $entityId;
    }

    /**
     * @param int $id
     *
     * @return int
     */
    public function getStoreId(int $id): int
    {
        return (int)substr($id, 5);
    }

    /**
     * @return bool
     */
    public function isEnterprise(): bool
    {
        return $this->productMetadata->getEdition()
            !== CommunityProductMetadata::EDITION_NAME;
    }

    /**
     * Get start date of current feed export. Only working with export to file.
     *
     * @return DateTime|null
     */
    public function getFeedExportStartDate(): ?DateTime
    {
        $file = new SplFileInfo($this->config->getFeedTmpFile());
        if (!$file->isFile()) {
            return null;
        }

        return new DateTime('@' . $file->getMTime());
    }

    /**
     * Get date of last finished feed export
     *
     * @param string $type
     *
     * @return DateTime|null
     */
    public function getLastFeedExportDate($type = null): ?DateTime
    {
        $file = new SplFileInfo($this->config->getDefaultFeedFile(null, $type));
        if (!$file->isFile()) {
            return null;
        }

        return new DateTime('@' . $file->getMTime());
    }

    /**
     * @param string $type
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getExportStateText($type = null)
    {
        $startDate = $this->getFeedExportStartDate();
        if (!$this->config->isRealTime() && $startDate) {
            return sprintf(
                __('Running, started on %s.'),
                $this->localDate->formatDate(
                    $startDate,
                    IntlDateFormatter::MEDIUM,
                    true
                )
            );
        }

        $finishedDate = $this->getLastFeedExportDate($type);
        if ($finishedDate) {
            return sprintf(
                __('Finished on %s.'),
                $this->localDate->formatDate(
                    $finishedDate,
                    IntlDateFormatter::MEDIUM,
                    true
                )
            );
        }

        return __('Export never triggered.');
    }

    /**
    * Load all attribute set names into a static array to prevent multiple loading
    * @return array attribute set names with attribute set id as key
    */
    public function loadAttributeSetNames(): array
    {
        if (!empty(self::$attributeSetNames)) {
            return self::$attributeSetNames;
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributeSets = $this->attributeSetRepository->getList($searchCriteria)->getItems();

        foreach ($attributeSets as $attributeSet) {
            self::$attributeSetNames[$attributeSet->getAttributeSetId()] = $attributeSet->getAttributeSetName();
        }

        if (empty(self::$attributeSetNames)) {
            //prevent result from being empty and loading attribute set names multiple times, should never happen
            self::$attributeSetNames = ['empty' => 'empty'];
        }

        return self::$attributeSetNames;
    }
}
