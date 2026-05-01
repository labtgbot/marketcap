(function (window, _, GeckoClient) {
    'use strict';

    const config = GeckoClient.tonConnect || {};
    const storageKey = config.storageKey || 'TONBANKCARD:ton-connect-wallet:v1';
    const changedEventName = 'tonbankcard:ton-connect-changed';
    const forbiddenSecretKeys = ['private_key', 'privateKey', 'seed', 'seedPhrase', 'seed_phrase', 'mnemonic'];
    const forbiddenSecretKeyPattern = /(private|seed|mnemonic|secret)/i;

    let state = {
        status: config.enabled ? 'disconnected' : 'feature_disabled',
        wallet: null,
        error: '',
        sdkLoaded: false
    };
    let initialized = false;
    let initializing = null;
    let tonConnectUI = null;
    let statusUnsubscribe = null;
    let listeners = [];

    const service = GeckoClient.tonConnect = {
        enabled: !!config.enabled,
        manifestUrl: config.manifestUrl || '/tonconnect-manifest.json',
        sdkUrl: config.sdkUrl || '',
        storageKey: storageKey,
        init: init,
        connect: connect,
        disconnect: disconnect,
        clear: clear,
        snapshot: snapshot,
        onChange: onChange,
        normalizeWallet: normalizeWallet
    };

    function clone(value) {
        return _.cloneDeep(value);
    }

    function nowIso() {
        return new Date().toISOString();
    }

    function normalizeAddress(value) {
        value = _.trim(value || '');
        return /^[A-Za-z0-9:_+=/-]{8,128}$/.test(value) ? value : null;
    }

    function normalizeText(value, maxLength) {
        value = _.trim(value || '');
        if (!value) return null;
        value = value.replace(/[\x00-\x1F\x7F]+/g, ' ');
        return value.slice(0, maxLength || 120);
    }

    function normalizeNetwork(value) {
        value = _.toLower(_.trim(value || ''));
        if (value === '-239' || value === 'mainnet' || value === 'ton-mainnet') return 'mainnet';
        if (value === '-3' || value === 'testnet' || value === 'ton-testnet') return 'testnet';
        return value ? normalizeText(value, 32) : 'unknown';
    }

    function normalizeFeature(feature) {
        if (_.isString(feature)) return normalizeText(feature, 48);
        if (_.isObject(feature)) return normalizeText(feature.name || feature.method || feature.type, 48);
        return null;
    }

    function containsForbiddenSecretShape(value) {
        if (!_.isObject(value)) return false;

        const stack = [value];
        while (stack.length) {
            const current = stack.pop();
            if (!_.isObject(current)) continue;

            const keys = Object.keys(current);
            for (let i = 0; i < keys.length; i++) {
                if (forbiddenSecretKeys.indexOf(keys[i]) >= 0 || forbiddenSecretKeyPattern.test(keys[i])) return true;
                if (_.isObject(current[keys[i]])) stack.push(current[keys[i]]);
            }
        }

        return false;
    }

    function normalizeWallet(rawWallet) {
        if (!rawWallet || containsForbiddenSecretShape(rawWallet)) return null;

        const account = _.get(rawWallet, 'account') || rawWallet;
        const address = normalizeAddress(_.get(account, 'address'));
        if (!address) return null;

        const device = _.get(rawWallet, 'device', {});
        const walletInfo = _.get(rawWallet, 'walletInfo', {});
        const rawFeatures = _.get(device, 'features') || _.get(rawWallet, 'features') || _.get(walletInfo, 'features') || [];
        const supportedFeatures = _.uniq((_.isArray(rawFeatures) ? rawFeatures : [])
            .map(normalizeFeature)
            .filter(Boolean))
            .slice(0, 12);

        return {
            address: address,
            network: normalizeNetwork(_.get(account, 'chain') || _.get(account, 'network')),
            chain: normalizeText(_.get(account, 'chain') || _.get(account, 'network'), 32),
            wallet_name: normalizeText(_.get(rawWallet, 'name') || _.get(walletInfo, 'name') || _.get(device, 'appName'), 80),
            app_name: normalizeText(_.get(device, 'appName') || _.get(walletInfo, 'name') || _.get(rawWallet, 'name'), 80),
            app_version: normalizeText(_.get(device, 'appVersion'), 32),
            platform: normalizeText(_.get(device, 'platform'), 48),
            provider: normalizeText(_.get(rawWallet, 'provider') || _.get(walletInfo, 'bridgeUrl') || 'tonconnect', 96),
            image_url: GeckoClient.utils.validURLString(_.get(walletInfo, 'imageUrl') || _.get(rawWallet, 'imageUrl') || '', window.location.href),
            supported_features: supportedFeatures,
            connected_at: normalizeText(_.get(rawWallet, 'connected_at'), 40) || nowIso()
        };
    }

    function canUseLocalStorage() {
        try {
            const testKey = storageKey + ':test';
            window.localStorage.setItem(testKey, '1');
            window.localStorage.removeItem(testKey);
            return true;
        } catch (err) {
            return false;
        }
    }

    function readStoredWallet() {
        if (!canUseLocalStorage()) return null;

        try {
            const raw = window.localStorage.getItem(storageKey);
            return raw ? normalizeWallet(JSON.parse(raw)) : null;
        } catch (err) {
            return null;
        }
    }

    function writeStoredWallet(wallet) {
        if (!canUseLocalStorage()) return false;

        try {
            window.localStorage.setItem(storageKey, JSON.stringify(wallet));
            return true;
        } catch (err) {
            return false;
        }
    }

    function removeStoredWallet() {
        try {
            window.localStorage.removeItem(storageKey);
        } catch (err) {}
    }

    function setState(patch) {
        state = Object.assign({}, state, patch || {});
        notify();
        return snapshot();
    }

    function setWallet(wallet, persist) {
        wallet = normalizeWallet(wallet);
        if (!wallet) {
            removeStoredWallet();
            return setState({
                status: service.enabled ? 'disconnected' : 'feature_disabled',
                wallet: null,
                error: ''
            });
        }

        if (persist !== false) {
            writeStoredWallet(wallet);
        }

        return setState({
            status: 'connected',
            wallet: wallet,
            error: ''
        });
    }

    function snapshot() {
        return clone({
            enabled: service.enabled,
            status: state.status,
            wallet: state.wallet,
            error: state.error,
            sdkLoaded: state.sdkLoaded,
            manifestUrl: service.manifestUrl
        });
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function sdkConstructor() {
        return _.get(window, 'TON_CONNECT_UI.TonConnectUI') || _.get(window, 'TonConnectUI') || null;
    }

    function loadSdk() {
        const Constructor = sdkConstructor();
        if (Constructor) {
            state.sdkLoaded = true;
            return Promise.resolve(Constructor);
        }

        if (!service.sdkUrl || !GeckoClient.loadScript) {
            return Promise.reject(new Error('TON Connect UI SDK is not configured'));
        }

        return GeckoClient.loadScript(service.sdkUrl).then(() => {
            const LoadedConstructor = sdkConstructor();
            if (!LoadedConstructor) {
                throw new Error('TON Connect UI SDK did not expose TON_CONNECT_UI');
            }
            state.sdkLoaded = true;
            return LoadedConstructor;
        });
    }

    function ensureUi() {
        if (tonConnectUI) return Promise.resolve(tonConnectUI);

        return loadSdk().then(Constructor => {
            tonConnectUI = new Constructor({
                manifestUrl: service.manifestUrl
            });

            if (_.isFunction(tonConnectUI.onStatusChange)) {
                statusUnsubscribe = tonConnectUI.onStatusChange(wallet => {
                    if (wallet) setWallet(wallet, true);
                    else clear();
                });
            }

            const existingWallet = _.get(tonConnectUI, 'wallet') || (_.get(tonConnectUI, 'connected') ? tonConnectUI : null);
            if (existingWallet) {
                setWallet(existingWallet, true);
            }

            return tonConnectUI;
        });
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = Promise.resolve()
            .then(() => {
                initialized = true;
                const storedWallet = readStoredWallet();
                if (storedWallet) {
                    setWallet(storedWallet, false);
                } else {
                    notify();
                }
                return service;
            });

        return initializing;
    }

    function connect() {
        if (!service.enabled) {
            return Promise.resolve(setState({
                status: 'feature_disabled',
                error: 'TON Connect is disabled in this environment.'
            }));
        }

        setState({status: 'connecting', error: ''});

        return ensureUi()
            .then(ui => {
                if (_.isFunction(ui.connectWallet)) {
                    return ui.connectWallet();
                }
                if (_.isFunction(ui.openModal)) {
                    ui.openModal();
                    return null;
                }
                throw new Error('TON Connect UI connect method is unavailable');
            })
            .then(wallet => {
                if (wallet) {
                    return setWallet(wallet, true);
                }
                return setState({status: state.wallet ? 'connected' : 'disconnected'});
            })
            .catch(err => setState({
                status: state.wallet ? 'connected' : 'disconnected',
                error: err && err.message ? err.message : 'TON Connect wallet connection failed.'
            }));
    }

    function disconnect() {
        const disconnectUi = tonConnectUI && _.isFunction(tonConnectUI.disconnect)
            ? Promise.resolve().then(() => tonConnectUI.disconnect()).catch(() => null)
            : Promise.resolve(null);

        return disconnectUi.then(() => {
            return clear();
        });
    }

    function clear() {
        removeStoredWallet();
        return setState({
            status: service.enabled ? 'disconnected' : 'feature_disabled',
            wallet: null,
            error: ''
        });
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};

        listeners.push(callback);
        callback(snapshot());
        return function unsubscribe() {
            listeners = listeners.filter(listener => listener !== callback);
        };
    }

    service.destroy = function () {
        if (_.isFunction(statusUnsubscribe)) {
            statusUnsubscribe();
        }
        statusUnsubscribe = null;
    };

})(window, _, GeckoClient);
