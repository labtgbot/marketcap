(function (window, _, Vue, GeckoClient) {
    'use strict';

    const preferences = GeckoClient.preferences;

    Vue.component('gc-cookies-dialog', {
        props: {
            expirationDays: {
                default: 30,
                validator: value => !isNaN(parseFloat(value))
            },
            persistent: {
                default: false
            }
        },
        template: '#component-cookies-dialog',
        data: function () {
            return {dialogModel: false};
        },
        created: function () {
            this.dialogModel = this.isExpired;
        },
        computed: {
            isExpired: function () {
                return _.now() - preferences.cookiesAccepted() > _.multiply(this.expirationDays, 24 * 60 * 60 * 1000);
            }
        },
        methods: {
            accept: function () {
                // save to local storage
                preferences.cookiesAccepted(true);
                this.dialogModel = false;
            },
            close: function () {
                this.dialogModel = false;
            }
        }
    });

})(window, _, Vue, GeckoClient);
