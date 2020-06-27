define([
    'jquery',
    'moment'
], function ($, moment) {
    'use strict';

    return function (validator) {

        validator.addRule(
                'validate-max-size',
                function (size, maxSize) {
                    return maxSize === false || size < maxSize;
                },
                $.mage.__('File you are trying to upload exceeds maximum file size limit.')

                );

        return validator;
    };
});