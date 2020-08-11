<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Addon\Attributeupload\Controller\Adminhtml\File;

use Magento\Framework\Controller\ResultFactory;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Catalog\Model\ProductFactory;

/**
 * Class Upload
 */
class Upload extends \Magento\Backend\App\Action {

    /**
     * Image uploader
     *
     * @var \Magento\Catalog\Model\ImageUploader
     */
    protected $imageUploader;

    /**
     * Upload constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context        	
     * @param \Magento\Catalog\Model\ImageUploader $imageUploader        	
     */
    public function __construct(ProductFactory $productFactory, \Magento\Backend\App\Action\Context $context, AttributeFactory $attributeFactory) {

        parent::__construct($context);
        $this->attributeFactory = $attributeFactory;
        $this->productFactory = $productFactory;
    }

    /**
     * Check admin permissions for this controller
     *
     * @return boolean
     */
    protected function _isAllowed() {

        return true;
    }

    /**
     *
     * @return \Magento\Catalog\Model\ImageUploader
     *
     */
    private function getImageUploader() {

        if ($this->imageUploader === null) {
            $this->imageUploader = \Magento\Framework\App\ObjectManager::getInstance()->get(\Addon\Attributeupload\Model\FileUploader::class);
        }

        return $this->imageUploader;
    }

    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        $imageId = $this->_request->getParam('param_name', 'image');
        if ((int) $this->_request->getParam('product') > 0) {
            $productId = (int) $this->_request->getParam('product');
            $product = $this->productFactory->create()->load($productId);
            $product->setStoreId(0)->setData($this->_request->getParam('code'), null)->save();
        }

        try {
            $attribute = $this->getAttribute($imageId);
            $this->getImageUploader()->setAllowedExtensions([]);
            
            $this->getImageUploader()->setBaseTmpPath('catalog/tmp/attribute-data');
            $this->getImageUploader()->setBasePath('catalog/attribute-data');

            $result = $this->getImageUploader()->saveFileToTmpDir($imageId);
            $result ['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain()
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    public function getAttribute($code) {
        if (strpos($code, 'product[') !== false) {
            $code = str_replace(['product[', ']'], '', $code);
        }

        $attributeModel = $this->attributeFactory->create();
        return $attributeModel->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $code);
    }

}
