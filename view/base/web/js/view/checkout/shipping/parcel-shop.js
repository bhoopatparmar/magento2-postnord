define([
    'Salecto_Shipping/js/view/parcel-shop',
    'ko',
    'mage/translate',
    'underscore',
    'Salecto_PostNord/js/model/parcel-shop-searcher'
], function(AbstractParcelShop, ko, $t, _, parcelShopSearcher) {

    return AbstractParcelShop.extend({
        defaults: {
            parcelShopSearcher: parcelShopSearcher
        },

        initialize: function() {
            this._super();

            this.shippingPostcode.subscribe(function(newVal) {
                if (!this.shippingMethod()) {
                    this.source.set('wexoShippingData.postcode', newVal);
                }
            }, this);

            return this;
        },

        /**
         * @returns {*}
         */
        getPopupText: function() {
            return ko.pureComputed(function() {
                return $t('%1 service points in postcode <u>%2</u>')
                    .replace('%1', this.parcelShops().length)
                    .replace('%2', this.wexoShippingData().postcode);
            }, this);
        },

        formatOpeningHours: function(parcelShop) {
            try {
                if (parcelShop.opening_hours.length) {
                    return '<table>' + _.map(parcelShop.opening_hours, function(day) {
                        var openingHour = JSON.parse(day);
                        return '<tr><th>%1</th><td>%2 - %3</td></tr>'.replace('%1', openingHour.day)
                            .replace('%2', openingHour.from1)
                            .replace('%3', openingHour.to1);
                    }).join('') + '</table>';
                }
                return '';
            }
            catch (e) {
                return '';
            }
        }
    });
});
