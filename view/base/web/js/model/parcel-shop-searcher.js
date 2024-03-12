define([
    'ko',
    'mage/storage',
    'jquery'
], function(ko, storage, $) {

    var currentRequest = null;

    return function(wexoShippingData, shippingCountryId, cmp) {
        if (currentRequest && currentRequest.abort) {
            currentRequest.abort();
        }

        $('body').trigger('processStart');
        return storage.get('/rest/V1/wexo-postnord/get-parcel-shops?' + $.param({
            country: shippingCountryId,
            postcode: wexoShippingData.postcode,
            amount: 20,
            cache: true
        })).always(function() {
            currentRequest = null;
            $('body').trigger('processStop');
        });
    };
});
