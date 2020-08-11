<?php

namespace Addon\Attributeupload\Model\ProductAttribute\Frontend;

class FileUpload extends \Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend {

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Construct
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager) {
        $this->_storeManager = $storeManager;
    }

    /**
     * Returns url to product image
     *
     * @param  \Magento\Catalog\Model\Product $product
     *
     * @return string|false
     */
    public function getUrl($product) {

        $fileData = $product->getData($this->getAttribute()->getAttributeCode());
        $url = false;
        if (!empty($fileData)) {
            $url = $this->_storeManager->getStore($product->getStore())
                            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                    . 'catalog/attribute-data/' . $fileData;
        }
        return $url;
    }
    
    public function getValue(\Magento\Framework\DataObject $object)
    {
        return $value = $this->getUrl($object);
    }

}
