<?php

namespace Techgroup\Addon\Plugin\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeGroupRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory as EavAttributeFactory;
use Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules;
use Magento\Eav\Api\Data\AttributeGroupInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filter\Translit;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Element\Wysiwyg as WysiwygElement;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\DataProvider\Mapper\FormElement as FormElementMapper;
use Magento\Ui\DataProvider\Mapper\MetaProperties as MetaPropertiesMapper;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Zend\Validator\Explode;

class Eav {

    const SORT_ORDER_MULTIPLIER = 10;
    const FORM_NAME = 'product_form';
    const DATA_SOURCE_DEFAULT = 'product';
    const DATA_SCOPE_PRODUCT = 'data.product';

    /**
     *
     * @var LocatorInterface
     * @since 101.0.0
     */
    protected $locator;

    /**
     *
     * @var Config
     * @since 101.0.0
     */
    protected $eavConfig;

    /**
     *
     * @var CatalogEavValidationRules
     * @since 101.0.0
     */
    protected $catalogEavValidationRules;

    /**
     *
     * @var RequestInterface
     * @since 101.0.0
     */
    protected $request;

    /**
     *
     * @var GroupCollectionFactory
     * @since 101.0.0
     */
    protected $groupCollectionFactory;

    /**
     *
     * @var StoreManagerInterface
     * @since 101.0.0
     */
    protected $storeManager;

    /**
     *
     * @var FormElementMapper
     * @since 101.0.0
     */
    protected $formElementMapper;

    /**
     *
     * @var MetaPropertiesMapper
     * @since 101.0.0
     */
    protected $metaPropertiesMapper;

    /**
     *
     * @var ProductAttributeGroupRepositoryInterface
     * @since 101.0.0
     */
    protected $attributeGroupRepository;

    /**
     *
     * @var SearchCriteriaBuilder
     * @since 101.0.0
     */
    protected $searchCriteriaBuilder;

    /**
     *
     * @var ProductAttributeRepositoryInterface
     * @since 101.0.0
     */
    protected $attributeRepository;

    /**
     *
     * @var SortOrderBuilder
     * @since 101.0.0
     */
    protected $sortOrderBuilder;

    /**
     *
     * @var EavAttributeFactory
     * @since 101.0.0
     */
    protected $eavAttributeFactory;

    /**
     *
     * @var Translit
     * @since 101.0.0
     */
    protected $translitFilter;

    /**
     *
     * @var ArrayManager
     * @since 101.0.0
     */
    protected $arrayManager;

    /**
     *
     * @var ScopeOverriddenValue
     */
    private $scopeOverriddenValue;

    /**
     *
     * @var array
     */
    private $attributesToDisable;

    /**
     *
     * @var array
     * @since 101.0.0
     */
    protected $attributesToEliminate;

    /**
     *
     * @var DataPersistorInterface
     * @since 101.0.0
     */
    protected $dataPersistor;

    /**
     *
     * @var EavAttribute[]
     */
    private $attributes = [];

    /**
     *
     * @var AttributeGroupInterface[]
     */
    private $attributeGroups = [];

    /**
     *
     * @var array
     */
    private $canDisplayUseDefault = [];

    /**
     *
     * @var array
     */
    private $bannedInputTypes = [
        'media_image'
    ];

    /**
     *
     * @var array
     */
    private $prevSetAttributes;

    /**
     *
     * @var CurrencyInterface
     */
    private $localeCurrency;

    public function __construct(\Magento\Framework\Filesystem $filesystem, UrlInterface $urlBuilder, LocatorInterface $locator, CatalogEavValidationRules $catalogEavValidationRules, Config $eavConfig, RequestInterface $request, GroupCollectionFactory $groupCollectionFactory, StoreManagerInterface $storeManager, FormElementMapper $formElementMapper, MetaPropertiesMapper $metaPropertiesMapper, ProductAttributeGroupRepositoryInterface $attributeGroupRepository, ProductAttributeRepositoryInterface $attributeRepository, SearchCriteriaBuilder $searchCriteriaBuilder, SortOrderBuilder $sortOrderBuilder, EavAttributeFactory $eavAttributeFactory, Translit $translitFilter, ArrayManager $arrayManager, ScopeOverriddenValue $scopeOverriddenValue, DataPersistorInterface $dataPersistor, $attributesToDisable = [], $attributesToEliminate = []) {
        $this->urlBuilder = $urlBuilder;
        $this->locator = $locator;
        $this->catalogEavValidationRules = $catalogEavValidationRules;
        $this->eavConfig = $eavConfig;
        $this->request = $request;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->storeManager = $storeManager;
        $this->formElementMapper = $formElementMapper;
        $this->metaPropertiesMapper = $metaPropertiesMapper;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->eavAttributeFactory = $eavAttributeFactory;
        $this->translitFilter = $translitFilter;
        $this->arrayManager = $arrayManager;
        $this->scopeOverriddenValue = $scopeOverriddenValue;
        $this->dataPersistor = $dataPersistor;
        $this->attributesToDisable = $attributesToDisable;
        $this->attributesToEliminate = $attributesToEliminate;
        $this->_filesystem = $filesystem;
    }

    public function afterModifyData(\Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav $subject, $result, $data) {
        if (!$this->locator->getProduct()->getId() && $this->dataPersistor->get('catalog_product')) {
            return $result;
        }

        $productId = $this->locator->getProduct()->getId();
        foreach (array_keys($this->getGroups()) as $groupCode) {
            $attributes = !empty($this->getAttributes()[$groupCode]) ? $this->getAttributes()[$groupCode] : [];

            foreach ($attributes as $attribute) {

                if ($attribute->getFrontendInput() === 'file') {

                    if (null !== ($attributeValue = $this->setupAttributeData($attribute))) {

                        try {

                            $path = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getRelativePath('catalog/attribute-data/');

                            $fileSize = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->stat($path . $attributeValue);

                            $ext = (explode('.', $attributeValue));
                            $ext = end($ext);

                            $type = 'document';
                            if (in_array(strtolower($ext), [
                                        'jpg',
                                        'png',
                                        'gif',
                                        'jpeg'
                                    ])) {
                                $type = 'image';
                            }

                            $size = ($fileSize[7]);

                            $url = $this->urlBuilder->getBaseUrl([
                                        '_type' => UrlInterface::URL_TYPE_MEDIA
                                    ]) . 'catalog/attribute-data/' . $attributeValue;

                            $attributeValue = [
                                [
                                    'file' => $attributeValue,
                                    'name' => $attributeValue,
                                    'size' => $size,
                                    'url' => $url,
                                    'type' => $type
                                ]
                            ];
                        } catch (\Exception $e) {

                            $attributeValue = [
                                [
                                    'file' => $attributeValue,
                                    'name' => $attributeValue,
                                    'size' => 0
                                ]
                            ];
                        }
                        $result[$productId][self::DATA_SOURCE_DEFAULT][$attribute->getAttributeCode()] = $attributeValue;
                    }
                }
            }
        }

        return $result;
    }

    public function afterSetupAttributeMeta(\Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav $subject, $result, ProductAttributeInterface $attribute, $groupCode, $sortOrder) {
        $productId = $this->locator->getProduct()->getId();

        if ($productId) {
            $result['arguments']['data']['config']['product'] = $productId;
        } else {
            $result['arguments']['data']['config']['product'] = 0;
        }
        if ('file' == $attribute->getFrontendInput()) {
            $otherInformationForFile = [
                'formElement' => 'fileUploader',
                'componentType' => 'fileUploader',
                'validation' => [
                    'validate-max-size' => '1000000000000',
                ],
                'component' => 'Techgroup_Addon/js/form/element/file-uploader',
                'elementTmpl' => 'Magento_Ui/js/form/element/file-uploader',
                'fileInputName' => 'file',
                'uploaderConfig' => [
                    'url' => $this->urlBuilder->getUrl('addon/file/upload', [
                        'type' => 'file',
                        '_secure' => true
                    ])
                ],
                'dataScope' => $attribute->getAttributeCode()
            ];
            $result['arguments']['data']['config'] = array_merge($result['arguments']['data']['config'], $otherInformationForFile);
        }
        return $result;
    }

    /**
     *
     * @ERROR!!!
     *
     * @since 101.0.0
     */
    public function modifyMeta(array $meta) {
        $sortOrder = 0;

        foreach ($this->getGroups() as $groupCode => $group) {
            $attributes = !empty($this->getAttributes()[$groupCode]) ? $this->getAttributes()[$groupCode] : [];

            if ($attributes) {
                $meta[$groupCode]['children'] = $this->getAttributesMeta($attributes, $groupCode);
                $meta[$groupCode]['arguments']['data']['config']['componentType'] = Fieldset::NAME;
                $meta[$groupCode]['arguments']['data']['config']['label'] = __('%1', $group->getAttributeGroupName());
                $meta[$groupCode]['arguments']['data']['config']['collapsible'] = true;
                $meta[$groupCode]['arguments']['data']['config']['dataScope'] = self::DATA_SCOPE_PRODUCT;
                $meta[$groupCode]['arguments']['data']['config']['sortOrder'] = $sortOrder * self::SORT_ORDER_MULTIPLIER;
            }

            $sortOrder++;
        }

        return $meta;
    }

    /**
     * Get attributes meta
     *
     * @param ProductAttributeInterface[] $attributes
     * @param string $groupCode
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAttributesMeta(array $attributes, $groupCode) {
        $meta = [];

        foreach ($attributes as $sortOrder => $attribute) {
            if (in_array($attribute->getFrontendInput(), $this->bannedInputTypes)) {
                continue;
            }

            if (in_array($attribute->getAttributeCode(), $this->attributesToEliminate)) {
                continue;
            }

            if (!($attributeContainer = $this->setupAttributeContainerMeta($attribute))) {
                continue;
            }

            $attributeContainer = $this->addContainerChildren($attributeContainer, $attribute, $groupCode, $sortOrder);

            $meta[static::CONTAINER_PREFIX . $attribute->getAttributeCode()] = $attributeContainer;
        }

        return $meta;
    }

    /**
     * Add container children
     *
     * @param array $attributeContainer
     * @param ProductAttributeInterface $attribute
     * @param string $groupCode
     * @param int $sortOrder
     * @return array @api
     * @since 101.0.0
     */
    public function addContainerChildren(array $attributeContainer, ProductAttributeInterface $attribute, $groupCode, $sortOrder) {
        foreach ($this->getContainerChildren($attribute, $groupCode, $sortOrder) as $childCode => $child) {
            $attributeContainer['children'][$childCode] = $child;
        }

        $attributeContainer = $this->arrayManager->merge(ltrim(static::META_CONFIG_PATH, ArrayManager::DEFAULT_PATH_DELIMITER), $attributeContainer, [
            'sortOrder' => $sortOrder * self::SORT_ORDER_MULTIPLIER
        ]);

        return $attributeContainer;
    }

    /**
     * Retrieve container child fields
     *
     * @param ProductAttributeInterface $attribute
     * @param string $groupCode
     * @param int $sortOrder
     * @return array @api
     * @since 101.0.0
     */
    public function getContainerChildren(ProductAttributeInterface $attribute, $groupCode, $sortOrder) {
        if (!($child = $this->setupAttributeMeta($attribute, $groupCode, $sortOrder))) {
            return [];
        }

        return [
            $attribute->getAttributeCode() => $child
        ];
    }

    /**
     * Resolve data persistence
     *
     * @param array $data
     * @return array
     */
    private function resolvePersistentData(array $data) {
        $persistentData = (array) $this->dataPersistor->get('catalog_product');
        $this->dataPersistor->clear('catalog_product');
        $productId = $this->locator->getProduct()->getId();

        if (empty($data[$productId][self::DATA_SOURCE_DEFAULT])) {
            $data[$productId][self::DATA_SOURCE_DEFAULT] = [];
        }

        $data[$productId] = array_replace_recursive($data[$productId][self::DATA_SOURCE_DEFAULT], $persistentData);

        return $data;
    }

    /**
     * Get product type
     *
     * @return null|string
     */
    private function getProductType() {
        return (string) $this->request->getParam('type', $this->locator->getProduct()
                                ->getTypeId());
    }

    /**
     * Return prev set id
     *
     * @return int
     */
    private function getPreviousSetId() {
        return (int) $this->request->getParam('prev_set_id', 0);
    }

    /**
     * Retrieve groups
     *
     * @return AttributeGroupInterface[]
     */
    private function getGroups() {
        if (!$this->attributeGroups) {
            $searchCriteria = $this->prepareGroupSearchCriteria()->create();
            $attributeGroupSearchResult = $this->attributeGroupRepository->getList($searchCriteria);
            foreach ($attributeGroupSearchResult->getItems() as $group) {
                $this->attributeGroups[$this->calculateGroupCode($group)] = $group;
            }
        }

        return $this->attributeGroups;
    }

    /**
     * Initialize attribute group search criteria with filters.
     *
     * @return SearchCriteriaBuilder
     */
    private function prepareGroupSearchCriteria() {
        return $this->searchCriteriaBuilder->addFilter(AttributeGroupInterface::ATTRIBUTE_SET_ID, $this->getAttributeSetId());
    }

    /**
     * Return current attribute set id
     *
     * @return int|null
     */
    private function getAttributeSetId() {
        return $this->locator->getProduct()->getAttributeSetId();
    }

    /**
     * Retrieve attributes
     *
     * @return ProductAttributeInterface[]
     */
    private function getAttributes() {
        if (!$this->attributes) {
            foreach ($this->getGroups() as $group) {
                $this->attributes[$this->calculateGroupCode($group)] = $this->loadAttributes($group);
            }
        }

        return $this->attributes;
    }

    /**
     * Loading product attributes from group
     *
     * @param AttributeGroupInterface $group
     * @return ProductAttributeInterface[]
     */
    private function loadAttributes(AttributeGroupInterface $group) {
        $attributes = [];
        $sortOrder = $this->sortOrderBuilder->setField('sort_order')
                ->setAscendingDirection()
                ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(AttributeGroupInterface::GROUP_ID, $group->getAttributeGroupId())
                ->addFilter(ProductAttributeInterface::IS_VISIBLE, 1)
                ->addSortOrder($sortOrder)
                ->create();
        $groupAttributes = $this->attributeRepository->getList($searchCriteria)->getItems();
        $productType = $this->getProductType();
        foreach ($groupAttributes as $attribute) {
            $applyTo = $attribute->getApplyTo();
            $isRelated = !$applyTo || in_array($productType, $applyTo);
            if ($isRelated) {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * Get attribute codes of prev set
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getPreviousSetAttributes() {
        if ($this->prevSetAttributes === null) {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('attribute_set_id', $this->getPreviousSetId())
                    ->create();
            $attributes = $this->attributeRepository->getList($searchCriteria)->getItems();
            $this->prevSetAttributes = [];
            foreach ($attributes as $attribute) {
                $this->prevSetAttributes[] = $attribute->getAttributeCode();
            }
        }

        return $this->prevSetAttributes;
    }

    /**
     * Check is product already new or we trying to create one
     *
     * @return bool
     */
    private function isProductExists() {
        return (bool) $this->locator->getProduct()->getId();
    }

    /**
     *
     * @param ProductAttributeInterface $attribute
     * @param array $meta
     * @return array
     */
    private function addUseDefaultValueCheckbox(ProductAttributeInterface $attribute, array $meta) {
        $canDisplayService = $this->canDisplayUseDefault($attribute);
        if ($canDisplayService) {
            $meta['arguments']['data']['config']['service'] = [
                'template' => 'ui/form/element/helper/service'
            ];

            $meta['arguments']['data']['config']['disabled'] = !$this->scopeOverriddenValue->containsValue(\Magento\Catalog\Api\Data\ProductInterface::class, $this->locator->getProduct(), $attribute->getAttributeCode(), $this->locator->getStore()
                                    ->getId());
        }
        return $meta;
    }

    /**
     * Setup attribute container meta
     *
     * @param ProductAttributeInterface $attribute
     * @return array @api
     * @since 101.0.0
     */
    public function setupAttributeContainerMeta(ProductAttributeInterface $attribute) {
        $containerMeta = $this->arrayManager->set('arguments/data/config', [], [
            'formElement' => 'container',
            'componentType' => 'container',
            'breakLine' => false,
            'label' => $attribute->getDefaultFrontendLabel(),
            'required' => $attribute->getIsRequired()
        ]);

        if ($attribute->getIsWysiwygEnabled()) {
            $containerMeta = $this->arrayManager->merge('arguments/data/config', $containerMeta, [
                'component' => 'Magento_Ui/js/form/components/group'
            ]);
        }

        return $containerMeta;
    }

    /**
     * Setup attribute data
     *
     * @param ProductAttributeInterface $attribute
     * @return mixed|null @api
     * @since 101.0.0
     */
    public function setupAttributeData(ProductAttributeInterface $attribute) {
        $product = $this->locator->getProduct();
        $productId = $product->getId();
        $prevSetId = $this->getPreviousSetId();
        $notUsed = !$prevSetId || ($prevSetId && !in_array($attribute->getAttributeCode(), $this->getPreviousSetAttributes()));

        if ($productId && $notUsed) {
            return $this->getValue($attribute);
        }

        return null;
    }

    /**
     * Customize checkboxes
     *
     * @param ProductAttributeInterface $attribute
     * @param array $meta
     * @return array
     */
    private function customizeCheckbox(ProductAttributeInterface $attribute, array $meta) {
        if ($attribute->getFrontendInput() === 'boolean') {
            $meta['arguments']['data']['config']['prefer'] = 'toggle';
            $meta['arguments']['data']['config']['valueMap'] = [
                'true' => '1',
                'false' => '0'
            ];
        }

        return $meta;
    }

    /**
     * Customize attribute that has price type
     *
     * @param ProductAttributeInterface $attribute
     * @param array $meta
     * @return array
     */
    private function customizePriceAttribute(ProductAttributeInterface $attribute, array $meta) {
        if ($attribute->getFrontendInput() === 'price') {
            $meta['arguments']['data']['config']['addbefore'] = $this->locator->getStore()
                    ->getBaseCurrency()
                    ->getCurrencySymbol();
        }

        return $meta;
    }

    /**
     * Add wysiwyg properties
     *
     * @param ProductAttributeInterface $attribute
     * @param array $meta
     * @return array
     */
    private function customizeWysiwyg(ProductAttributeInterface $attribute, array $meta) {
        if (!$attribute->getIsWysiwygEnabled()) {
            return $meta;
        }

        $meta['arguments']['data']['config']['formElement'] = WysiwygElement::NAME;
        $meta['arguments']['data']['config']['wysiwyg'] = true;
        $meta['arguments']['data']['config']['wysiwygConfigData'] = [
            'add_variables' => false,
            'add_widgets' => false,
            'add_directives' => true,
            'use_container' => true,
            'container_class' => 'hor-scroll'
        ];

        return $meta;
    }

    /**
     * Retrieve form element
     *
     * @param string $value
     * @return mixed
     */
    private function getFormElementsMapValue($value) {
        $valueMap = $this->formElementMapper->getMappings();

        return isset($valueMap[$value]) ? $valueMap[$value] : $value;
    }

    /**
     * Retrieve attribute value
     *
     * @param ProductAttributeInterface $attribute
     * @return mixed
     */
    private function getValue(ProductAttributeInterface $attribute) {

        /**
         *
         * @var Product $product
         */
        $product = $this->locator->getProduct();

        return $product->getData($attribute->getAttributeCode());
    }

    /**
     * Retrieve scope label
     *
     * @param ProductAttributeInterface $attribute
     * @return \Magento\Framework\Phrase|string
     */
    private function getScopeLabel(ProductAttributeInterface $attribute) {
        if ($this->storeManager->isSingleStoreMode() || $attribute->getFrontendInput() === AttributeInterface::FRONTEND_INPUT) {
            return '';
        }

        switch ($attribute->getScope()) {
            case ProductAttributeInterface::SCOPE_GLOBAL_TEXT:
                return __('[GLOBAL]');
            case ProductAttributeInterface::SCOPE_WEBSITE_TEXT:
                return __('[WEBSITE]');
            case ProductAttributeInterface::SCOPE_STORE_TEXT:
                return __('[STORE VIEW]');
        }

        return '';
    }

    /**
     * Whether attribute can have default value
     *
     * @param ProductAttributeInterface $attribute
     * @return bool
     */
    private function canDisplayUseDefault(ProductAttributeInterface $attribute) {
        $attributeCode = $attribute->getAttributeCode();
        /**
         *
         * @var Product $product
         */
        $product = $this->locator->getProduct();

        if (isset($this->canDisplayUseDefault[$attributeCode])) {
            return $this->canDisplayUseDefault[$attributeCode];
        }

        return $this->canDisplayUseDefault[$attributeCode] = (($attribute->getScope() != ProductAttributeInterface::SCOPE_GLOBAL_TEXT) && $product && $product->getId() && $product->getStoreId());
    }

    /**
     * Check if attribute scope is global.
     *
     * @param ProductAttributeInterface $attribute
     * @return bool
     */
    private function isScopeGlobal($attribute) {
        return $attribute->getScope() === ProductAttributeInterface::SCOPE_GLOBAL_TEXT;
    }

    /**
     * Load attribute model by attribute data object.
     *
     * TODO: This method should be eliminated when all missing service methods are implemented
     *
     * @param ProductAttributeInterface $attribute
     * @return EavAttribute
     */
    private function getAttributeModel($attribute) {
        return $this->eavAttributeFactory->create()->load($attribute->getAttributeId());
    }

    /**
     * Calculate group code based on group name.
     *
     * TODO: This logic is copy-pasted from \Magento\Eav\Model\Entity\Attribute\Group::beforeSave
     * TODO: and should be moved to a separate service, which will allow two-way conversion groupName <=> groupCode
     * TODO: Remove after MAGETWO-48290 is complete
     *
     * @param AttributeGroupInterface $group
     * @return string
     */
    private function calculateGroupCode(AttributeGroupInterface $group) {
        $attributeGroupCode = $group->getAttributeGroupCode();

        if ($attributeGroupCode === 'images') {
            $attributeGroupCode = 'image-management';
        }

        return $attributeGroupCode;
    }

    /**
     * The getter function to get the locale currency for real application code
     *
     * @return \Magento\Framework\Locale\CurrencyInterface
     *
     * @deprecated 101.0.0
     */
    private function getLocaleCurrency() {
        if ($this->localeCurrency === null) {
            $this->localeCurrency = \Magento\Framework\App\ObjectManager::getInstance()->get(CurrencyInterface::class);
        }
        return $this->localeCurrency;
    }

    /**
     * Format price according to the locale of the currency
     *
     * @param mixed $value
     * @return string
     * @since 101.0.0
     */
    protected function formatPrice($value) {
        if (!is_numeric($value)) {
            return null;
        }

        $store = $this->storeManager->getStore();
        $currency = $this->getLocaleCurrency()->getCurrency($store->getBaseCurrencyCode());
        $value = $currency->toCurrency($value, [
            'display' => \Magento\Framework\Currency::NO_SYMBOL
        ]);

        return $value;
    }

}
