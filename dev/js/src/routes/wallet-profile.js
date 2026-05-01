(function (window, _, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig['wallet-profile'];
    const options = GeckoClient.getOptions('wallet-profile');
    if (!route) return;

    GeckoClient.router.addRoute({
        name: 'wallet-profile',
        path: route.path,
        component: {
            template: '#route-wallet-profile',
            data: function () {
                const snapshot = GeckoClient.tonConnect ? GeckoClient.tonConnect.snapshot() : {};
                return {
                    connection: snapshot,
                    unsubscribeTonConnect: null,
                    watchlistSnapshot: {entries: []},
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.subscribeWallet();
                this.refreshWatchlistSnapshot();
                this.subscribeWatchlist();
            },
            beforeDestroy: function () {
                if (this.unsubscribeTonConnect) this.unsubscribeTonConnect();
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            computed: {
                featureEnabled: function () {
                    return !!_.get(this.connection, 'enabled');
                },
                wallet: function () {
                    return _.get(this.connection, 'wallet') || null;
                },
                isConnected: function () {
                    return _.get(this.connection, 'status') === 'connected' && !!this.wallet;
                },
                isConnecting: function () {
                    return _.get(this.connection, 'status') === 'connecting';
                },
                surfaceLabel: function () {
                    return GeckoClient.telegram && GeckoClient.telegram.active ? 'Telegram Mini App' : 'Browser';
                },
                statusLabel: function () {
                    if (!this.featureEnabled) return 'Disabled';
                    if (this.isConnecting) return 'Connecting';
                    if (this.isConnected) return 'Connected';
                    return 'Disconnected';
                },
                statusColor: function () {
                    if (!this.featureEnabled) return 'warning';
                    if (this.isConnected) return 'success';
                    if (this.isConnecting) return 'primary';
                    return 'grey';
                },
                statusIcon: function () {
                    if (!this.featureEnabled) return 'mdi-lock-alert-outline';
                    if (this.isConnected) return 'mdi-wallet-check-outline';
                    if (this.isConnecting) return 'mdi-progress-clock';
                    return 'mdi-wallet-outline';
                },
                connectionError: function () {
                    return _.get(this.connection, 'error') || '';
                },
                shortAddress: function () {
                    const address = _.get(this.wallet, 'address') || '';
                    if (address.length <= 18) return address;
                    return address.slice(0, 8) + '...' + address.slice(-6);
                },
                networkLabel: function () {
                    return _.startCase(_.get(this.wallet, 'network') || 'unknown');
                },
                walletName: function () {
                    return _.get(this.wallet, 'wallet_name') || _.get(this.wallet, 'app_name') || 'TON wallet';
                },
                walletMetaItems: function () {
                    if (!this.wallet) return [];

                    return [
                        {label: 'Wallet app', value: _.get(this.wallet, 'app_name') || this.walletName, icon: 'mdi-cellphone-link'},
                        {label: 'Network', value: this.networkLabel, icon: 'mdi-lan'},
                        {label: 'Platform', value: _.get(this.wallet, 'platform') || 'Unknown', icon: 'mdi-devices'},
                        {label: 'Provider', value: _.get(this.wallet, 'provider') || 'TON Connect', icon: 'mdi-connection'}
                    ];
                },
                supportedFeatures: function () {
                    return _.get(this.wallet, 'supported_features') || [];
                },
                watchlistCount: function () {
                    return (_.get(this.watchlistSnapshot, 'entries') || []).length;
                },
                watchlistStorageLabel: function () {
                    const mode = GeckoClient.watchlist ? GeckoClient.watchlist.storageMode : 'local';
                    if (mode === 'telegram_cloud') return 'Telegram CloudStorage';
                    if (mode === 'memory') return 'Memory';
                    return 'Local browser';
                },
                portfolioPlaceholders: function () {
                    return [
                        {label: 'Balances', icon: 'mdi-scale-balance', status: 'Read-only placeholder'},
                        {label: 'Jettons', icon: 'mdi-diamond-stone', status: 'Read-only placeholder'},
                        {label: 'Activity', icon: 'mdi-timeline-clock-outline', status: 'Read-only placeholder'}
                    ];
                },
                privacyRoute: function () {
                    return {name: 'privacy-policy'};
                }
            },
            methods: {
                subscribeWallet: function () {
                    const service = GeckoClient.tonConnect;
                    if (!service) return;

                    this.unsubscribeTonConnect = service.onChange(snapshot => {
                        this.connection = snapshot;
                    });
                    service.init();
                },
                subscribeWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(snapshot => {
                        this.watchlistSnapshot = snapshot || {entries: []};
                    });
                    watchlist.init().then(() => this.refreshWatchlistSnapshot());
                },
                refreshWatchlistSnapshot: function () {
                    this.watchlistSnapshot = GeckoClient.watchlist ? GeckoClient.watchlist.snapshot() : {entries: []};
                },
                connectWallet: function () {
                    if (!GeckoClient.tonConnect) return;
                    GeckoClient.telegram.hapticSelection();
                    GeckoClient.tonConnect.connect();
                },
                disconnectWallet: function () {
                    if (!GeckoClient.tonConnect) return;
                    GeckoClient.telegram.hapticSelection();
                    GeckoClient.tonConnect.disconnect();
                },
                copyAddress: function () {
                    const address = _.get(this.wallet, 'address');
                    if (!address || !_.get(navigator, 'clipboard.writeText')) return;
                    navigator.clipboard.writeText(address).then(() => {
                        this.$root.copiedModel = true;
                    }).catch(() => {});
                },
                featureLabel: function (feature) {
                    return _.startCase(String(feature || '').replace(/[_-]+/g, ' '));
                }
            }
        }
    });

})(window, _, GeckoClient);
