<?php

namespace Addon\Attributeupload\Plugin\EavModelAdminhtml\System\Config\Source;

class Inputtype
{	
	/* Add File Option */
	public function afterToOptionArray(\Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype $subject,$result)
    {    	
    	$result[] = ['value' => 'file', 'label' => __('Upload Files')];
    	return $result;
    }
}
