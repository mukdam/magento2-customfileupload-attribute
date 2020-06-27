var config = {
    map: {
        '*': {
            'Magento_Backend/js/media-uploader': 'Techgroup_Addon/js/media-uploader'
        }
    }
    ,
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'Techgroup_Addon/js/validator-mixin': true
            }
        }
    },
    deps: [
        'Magento_Catalog/catalog/product'
    ]

}