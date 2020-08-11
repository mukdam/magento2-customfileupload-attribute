var config = {
    map: {
        '*': {
            'Magento_Backend/js/media-uploader': 'Addon_Attributeupload/js/media-uploader'
        }
    }
    ,
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'Addon_Attributeupload/js/validator-mixin': true
            }
        }
    },
    deps: [
        'Magento_Catalog/catalog/product'
    ]

}