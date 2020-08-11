<?php

namespace Addon\Attributeupload\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class AttributeBeforeSave implements ObserverInterface {

    public function execute(\Magento\Framework\Event\Observer $observer) {
        try {
            $object = $observer->getData('object');
            if ($object instanceof Attribute) {
                if ($object->getAttributeId() === null && $object->getFrontendInput() === 'file') {

                    $object->setBackendModel('Addon\Attributeupload\Model\ProductAttribute\Backend\FileUpload');
                    $object->setFrontendModel('Addon\Attributeupload\Model\ProductAttribute\Frontend\FileUpload');
                    $object->setBackendType('varchar');
                }
            }
            return $this;
        } catch (\Exception $e) {
            throw new \Exception('Observer not works on attribute');
        }
    }

}
