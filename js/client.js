/*
 * ==========================================================
 * CLIENT SCRIPT
 * ==========================================================
 *
 * Main JavaScript file used on both admin and client sides. © 2022-2024 PagoLibre. All rights reserved.
 * 
 */

'use strict';
(function () {

    var PAGOL_VERSION = '1.0.0';
    var body;
    var checkouts;
    var timeout = false;
    var previous_search = '';
    var countdown;
    var intervals = {};
    var busy = {};
    var ND = 'undefined';
    var admin = typeof PAGOL_ADMIN !== ND || document.getElementsByClassName('pagoL-admin').length;
    var active_checkout;
    var active_checkout_id;
    var active_button;
    var scripts = document.getElementsByTagName('script');
    var language = typeof PAGOL_LANGUAGE !== ND ? PAGOL_LANGUAGE : false;
    var responsive = window.innerWidth < 769;
    var cloud = false;
    var exchange = false;

    /*
    * ----------------------------------------------------------
    * _query
    * ----------------------------------------------------------
    */

    var _ = function (selector) {
        return typeof selector === 'object' && 'e' in selector ? selector : (new _.init(typeof selector === 'string' ? document.querySelectorAll(selector) : selector));
    }

    _.init = function (e) {
        this.e = e.tagName != 'SELECT' && (typeof e[0] !== 'undefined' || NodeList.prototype.isPrototypeOf(e)) ? e : [e];
    }

    _.ajax = function (url, paramaters = false, onSuccess = false, method = 'POST') {
        let xhr = new XMLHttpRequest();
        let fd = '';
        xhr.open(method, url, true);
        if (paramaters) {
            if (paramaters.action == 'pagoL_wp_ajax') {
                for (var key in paramaters) {
                    if (typeof paramaters[key] === 'object') paramaters[key] = JSON.stringify(paramaters[key]);
                }
                fd = new URLSearchParams(paramaters).toString();
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            } else {
                fd = new FormData();
                fd.append('data', JSON.stringify(paramaters));
            }
        }
        xhr.onload = () => { if (onSuccess) onSuccess(xhr.responseText) };
        xhr.onerror = () => { return false };
        xhr.send(fd);
    }

    _.extend = function (a, b) {
        for (var key in b) if (b.hasOwnProperty(key)) a[key] = b[key];
        return a;
    }

    _.documentHeight = function () {
        let body = document.body, html = document.documentElement;
        return Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight);
    }

    _.init.prototype.on = function (event, sel, handler) {
        for (var i = 0; i < this.e.length; i++) {
            this.e[i].addEventListener(event, function (event) {
                var t = event.target;
                while (t && t !== this) {
                    if (t.matches(sel)) {
                        handler.call(t, event);
                    }
                    t = t.parentNode;
                }
            });
        }
    }

    _.init.prototype.addClass = function (value) {
        for (var i = 0; i < this.e.length; i++) {
            this.e[i].classList.add(value);
        }
        return _(this.e);
    }

    _.init.prototype.removeClass = function (value) {
        value = value.trim().split(' ');
        for (var i = 0; i < value.length; i++) {
            for (var j = 0; j < this.e.length; j++) {
                this.e[j].classList.remove(value[i]);
            }
        }
        return _(this.e);
    }

    _.init.prototype.toggleClass = function (value) {
        for (var i = 0; i < this.e.length; i++) {
            this.e[i].classList.toggle(value);
        }
        return _(this.e);
    }

    _.init.prototype.setClass = function (class_name, add = true) {
        for (var i = 0; i < this.e.length; i++) {
            if (add) {
                _(this.e[i]).addClass(class_name);
            } else {
                _(this.e[i]).removeClass(class_name);
            }
        }
        return _(this.e);
    }

    _.init.prototype.hasClass = function (value) {
        return this.e.length && this.e[0].classList ? this.e[0].classList.contains(value) : false;
    }

    _.init.prototype.find = function (selector) {
        if (selector.indexOf('>') === 0) selector = ':scope' + selector;
        try {
            return this.e.length && this.e[0].querySelectorAll ? _(this.e[0].querySelectorAll(selector)) : false;
        } catch (e) {
            console.warn(e);
            return false;
        }
    }

    _.init.prototype.parent = function () {
        return this.e.length ? _(this.e[0].parentElement) : false;
    }

    _.init.prototype.prev = function () {
        return this.e.length ? _(this.e[0].previousElementSibling) : false;
    }

    _.init.prototype.next = function () {
        return this.e.length ? _(this.e[0].nextElementSibling) : false;
    }

    _.init.prototype.attr = function (name, value = false) {
        if (!this.e.length || typeof this.e[0].getAttribute !== 'function') {
            return;
        }
        if (value === false) {
            return this.e[0].getAttribute(name);
        }
        if (value) {
            this.e[0].setAttribute(name, value);
        } else {
            this.e[0].removeAttribute(name);
        }
        return _(this.e);
    }

    _.init.prototype.data = function (attribute = false, value = false) {
        if (!this.e.length) {
            return;
        }
        let response = {};
        let el = this.e[0];
        if (attribute) {
            return _(this).attr('data-' + attribute, value);
        }
        for (var i = 0, atts = el.attributes, n = atts.length; i < n; i++) {
            response[atts[i].nodeName.substr(5)] = atts[i].nodeValue;
        }
        return response;
    }

    _.init.prototype.html = function (value = false) {
        if (!this.e.length) {
            return;
        }
        if (value === false) {
            return this.e[0].innerHTML;
        }
        if (typeof value === 'string' || typeof value === 'number') {
            this.e[0].innerHTML = value;
        } else {
            this.e[0].appendChild(value);
        }
        return _(this.e);
    }

    _.init.prototype.append = function (value) {
        if (!this.e.length) {
            return;
        }
        var template = document.createElement('template');
        template.innerHTML = value.trim();
        while (template.content.childNodes.length) {
            this.e[0].appendChild(template.content.firstChild);
        }
        return _(this.e);
    }

    _.init.prototype.prepend = function (value) {
        this.e[0].innerHTML = value + this.e[0].innerHTML;
        return _(this.e);
    }

    _.init.prototype.insert = function (value, before = true) {
        var template = document.createElement('template');
        template.innerHTML = value.trim();
        this.e[0].parentNode.insertBefore(template.content.firstChild, before ? this.e[0] : this.e[0].nextElementSibling);
        return _(this.e);
    }

    _.init.prototype.replace = function (content) {
        this.e[0].outerHTML = content;
        return _(this.e);
    }

    _.init.prototype.remove = function () {
        for (var i = 0; i < this.e.length; i++) {
            this.e[i].remove();
        }
    }

    _.init.prototype.is = function (type) {
        if (this.e[0].nodeType == 1 && this.e.length) {
            type = type.toLowerCase();
            return this.e[0].tagName.toLowerCase() == type || _(this).attr('type') == type;
        }
        return false;
    }

    _.init.prototype.index = function () {
        return [].indexOf.call(this.e[0].parentElement.children, this.e[0]);
    }

    _.init.prototype.siblings = function () {
        return this.e[0].parentNode.querySelectorAll(':scope > *')
    }

    _.init.prototype.closest = function (selector) {
        return _(this.e[0].closest(selector));
    }

    _.init.prototype.scrollTo = function () {
        this.e[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    _.init.prototype.val = function (value = null) {
        if (value !== null) {
            for (var i = 0; i < this.e.length; i++) {
                this.e[i].value = value;
            }
        }
        return this.e.length ? this.e[0].value : '';
    }

    _.load = function (src = false, js = true, onLoad = false, content = false) {
        let resource = document.createElement(js ? 'script' : 'link');
        if (src) {
            if (js) resource.src = src; else resource.href = src;
            resource.type = js ? 'text/javascript' : 'text/css';
        } else {
            resource.innerHTML = content;
        }
        if (onLoad) {
            resource.onload = function () {
                onLoad();
            }
        }
        if (!js) {
            resource.rel = 'stylesheet';
        }
        document.head.appendChild(resource);
    }

    _.storage = function (name, value = -1, default_value = false) {
        if (value === -1) {
            let value = localStorage.getItem(name);
            return value ? JSON.parse(value) : default_value;
        }
        localStorage.setItem(name, JSON.stringify(value));
    }

    _.download = function (url) {
        var anchor = document.createElement('a');
        var checks = ['?', '#', '&'];
        anchor.href = url;
        url = url.substr(url.lastIndexOf('/') + 1);
        for (var i = 0; i < checks.length; i++) {
            if (url.includes(checks[i])) {
                url = url.substr(0, url.lastIndexOf(checks[i]));
            }
        }
        anchor.download = url;
        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);
    }

    _.init.prototype.i = function (index) {
        return _(this.e[index]);
    }

    window._query = _;

    /*
    * ----------------------------------------------------------
    * Functions
    * ----------------------------------------------------------
    */

    var PAGOLibre = {
        loading: function (element, action = -1) {
            element = _(element);
            let is_loading = element.hasClass('pagoL-loading');
            if (action == 'check') {
                return is_loading;
            }
            if (action !== -1) {
                return element.setClass('pagoL-loading', action === true);
            }
            if (is_loading) {
                return true;
            } else {
                this.loading(element, true);
            }
            return false;
        },

        activate: function (element, activate = true) {
            return _(element).setClass('pagoL-active', activate);
        },

        ajax: function (function_name, data = false, onSuccess = false) {
            data.function = function_name;
            data.language = language;
            data.cloud = cloud;
            _.ajax(PAGOL_URL + 'ajax.php', data, (response) => {
                let error = false;
                let result = false;
                try {
                    result = JSON.parse(response);
                    error = !(result && 'success' in result && result.success);
                } catch (e) {
                    error = true;
                }
                if (error) {
                    body.find('.pagoL-loading:not([data-area])').removeClass('pagoL-loading');
                    console.error(response);
                    busy[active_checkout_id] = false;
                } else if (onSuccess) {
                    onSuccess(result.response);
                }
            });
        },

        cookie: function (name, value = false, expiration_days = false, action = 'get', seconds = false) {
            let https = location.protocol == 'https:' ? 'SameSite=None;Secure;' : '';
            if (action == 'get') {
                let cookies = document.cookie.split(';');
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = cookies[i];
                    while (cookie.charAt(0) == ' ') {
                        cookie = cookie.substring(1);
                    }
                    if (cookie.indexOf(name) == 0) {
                        let value = cookie.substring(name.length + 1, cookie.length);
                        return value ? value : false;
                    }
                }
                return false;
            } else if (action == 'set') {
                let date = new Date();
                date.setTime(date.getTime() + (expiration_days * (seconds ? 1 : 86400) * 1000));
                document.cookie = name + "=" + value + ";expires=" + date.toUTCString() + ";path=/;" + https;
            } else if (this.cookie(name)) {
                document.cookie = name + "=" + value + ";expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;" + https;
            }
        },

        beautifyTime: function (datetime, extended = false, future = false) {
            let date;
            if (datetime == '0000-00-00 00:00:00') return '';
            if (datetime.indexOf('-') > 0) {
                let arr = datetime.split(/[- :]/);
                date = new Date(arr[0], arr[1] - 1, arr[2], arr[3], arr[4], arr[5]);
            } else {
                let arr = datetime.split(/[. :]/);
                date = new Date(arr[2], arr[1] - 1, arr[0], arr[3], arr[4], arr[5]);
            }
            let now = new Date();
            let date_string = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds()));
            let diff_days = ((now - date_string) / 86400000) * (future ? -1 : 1);
            let time = date_string.toLocaleTimeString('en-EN', { hour: '2-digit', minute: '2-digit' });
            if (time.charAt(0) === '0' && (time.includes('PM') || time.includes('AM'))) {
                time = time.substring(1);
            }
            if (diff_days < 1 && now.getDate() == date_string.getDate()) {
                return `<span>${pagoL_('Today')}</span>${extended ? ` <span>${time}</span>` : ''}`;
            } else {
                return `<span>${date_string.toLocaleDateString()}</span>${extended ? ` <span>${time}</span>` : ''}`;
            }
        },

        search: function (input, searchFunction) {
            let icon = _(input).parent().find('i');
            let search = _(input).val().toLowerCase().trim();
            if (loading(icon, 'check')) {
                return;
            }
            if (search == previous_search) {
                this.loading(icon, false);
                return;
            }
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                previous_search = search;
                searchFunction(search, icon);
                this.loading(icon);
            }, 500);
        },

        searchClear: function (icon, onSuccess) {
            let search = _(icon).next().val();
            if (search) {
                _(icon).next().val('');
                onSuccess();
            }
        },

        getURL: function (name = false, url = false) {
            if (!url) url = location.href;
            if (!name) {
                var c = url.split('?').pop().split('&');
                var p = {};
                for (var i = 0; i < c.length; i++) {
                    var d = c[i].split('=');
                    p[d[0]] = PAGOLibre.escape(d[1]);
                }
                return p;
            }
            return PAGOLibre.escape(decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(url) || [, ""])[1].replace(/\+/g, '%20') || ""));
        },

        escape: function (string) {
            if (!string) return string;
            return string.replaceAll('<script', '&lt;script').replaceAll('</script', '&lt;/script').replaceAll('javascript:', '').replaceAll('onclick=', '').replaceAll('onerror=', '');
        },

        checkout: {
            settings: function (checkout) {
                checkout = _(checkout);
                let checkout_id = checkout.data('pagoL');
                if (!checkout_id) checkout_id = checkout.data('pagolibre'); // Deprecated
                let settings = { checkout_id: checkout_id };
                if (checkout_id.includes('custom')) {
                    return _.extend(settings, _(checkout).data());
                }
                return settings;
            },

            init: function (settings, area, onSuccess = false) {
                let active_transaction = this.storageTransaction(settings.checkout_id);
                let user_details = storage('pagoL-user-details', -1, {});
                let is_fiat_payment = active_transaction.id && (PAGOLibre.getURL('cc') || PAGOLibre.isFiat(active_transaction.external_reference) || PAGOLibre.isFiat(active_transaction.cryptocurrency));
                let payment_status = PAGOLibre.getURL('payment_status');
                let redirect = PAGOLibre.getURL('redirect').replace('payment_status=paid', '');
                let exchange_quote = storage('pagoL-quote');
                if (redirect) {
                    redirect += (payment_status ? (redirect.includes('?') ? '&' : '?') + 'payment_status=' + payment_status : '');
                }

                // Exchange
                if (exchange_quote && redirect && (payment_status == 'cancelled' || PAGOLibre.getURL('cc'))) {
                    return document.location = redirect;
                }

                // Checkout init
                _.ajax(PAGOL_URL + 'init.php', { checkout: settings, language: language, cloud: cloud }, (response) => {
                    area = _(area);
                    area.html(response);
                    if (active_transaction) {
                        active_checkout_id = area.data('pagoL');
                        if (!active_transaction) active_transaction = checkout.data('pagolibre'); // Deprecated
                        active_checkout = area.find('> .pagoL-main');
                        if (active_transaction.detected || is_fiat_payment) {
                            if (payment_status == 'cancelled') {
                                return this.cancelTransaction(true);
                            } else {
                                this.monitorTransaction(active_transaction.encrypted);
                            }
                        } else {
                            let time = parseInt((Date.now() - active_transaction.storage_time) / 1000);
                            let minutes = PAGOL_SETTINGS.countdown - Math.floor(time / 60);
                            let seconds = 60 - (time % 60);
                            if (seconds) minutes--;
                            this.initTransaction(active_transaction.id, active_transaction.amount, active_transaction.to, active_transaction.cryptocurrency, active_transaction.external_reference, [minutes, seconds], active_transaction.custom_token, active_transaction.redirect, active_transaction.vat, active_transaction.encrypted, active_transaction.amount_extra, active_transaction.confirmations);
                        }
                        if (active_checkout.hasClass('pagoL-popup')) {
                            this.openPopup(active_checkout_id);
                        }
                    }
                    for (var key in user_details) {
                        area.find(`.pagoL-user-details [name="${key}"]`).val(user_details[key]);
                    };
                    if (storage('pagoL-billing')) {
                        PAGOLibre.checkout.showInvoiceBox(area.find('#pagoL-btn-invoice').e[0]);
                    }
                    if (is_fiat_payment) {
                        active_checkout.removeClass('pagoL-tx-cnt-active').addClass('pagoL-complete-cnt-active');
                        active_checkout.find('.pagoL-complete-cnt .pagoL-text').addClass('pagoL-order-processing');
                        this.showCancelButton('.pagoL-complete-cnt');
                    } else if (PAGOLibre.getURL('pay') && !active_transaction) {
                        if (!payment_status) {
                            area.find('.pagoL-payment-methods > div').addClass('pagoL-hidden');
                            area.find(`.pagoL-payment-methods [data-cryptocurrency="${PAGOLibre.getURL('pay').toLowerCase()}"]`).removeClass('pagoL-hidden').e[0].click();
                            if (!exchange_quote || (!PAGOLibre.getURL('checkout_id').includes(exchange_quote.id) && !location.href.includes(exchange_quote.id))) {
                                body.removeClass('pagoL-loading');
                            }
                        } else if (redirect) {
                            return document.location = redirect
                        }
                    }
                    if (PAGOLibre.metamask.active()) {
                        body.find('#metamask').removeClass('pagoL-hidden');
                    }
                    if (onSuccess) {
                        onSuccess(response);
                    }
                });
            },

            initTransaction: function (transaction_id, amount, address, cryptocurrency, external_reference, countdown_partial = false, custom_token = false, redirect = false, vat = false, encrypted_transaction = false, amount_extra = false, min_confirmations = 3) {
                let pay_cnt = active_checkout.find('.pagoL-pay-cnt');
                let area = pay_cnt.find('.pagoL-pay-address');
                let cryptocurrency_uppercase = PAGOLibre.baseCode(cryptocurrency.toUpperCase());
                let countdown_area = pay_cnt.find('[data-countdown]');
                let data = { id: transaction_id, amount: amount, to: address, cryptocurrency: cryptocurrency, external_reference: external_reference, custom_token: custom_token, redirect: redirect, vat: vat, encrypted: encrypted_transaction, amount_extra: amount_extra, min_confirmations: min_confirmations };
                let network_label = PAGOLibre.network(cryptocurrency, false, true);
                let ln = cryptocurrency == 'btc_ln';
                active_checkout.addClass('pagoL-pay-cnt-active').data('active', cryptocurrency).data('custom-token', custom_token ? custom_token.type : '');
                area.find('.pagoL-text').html(ln ? pagoL_('Lightning Invoice') : cryptocurrency_uppercase + network_label + ' ' + pagoL_('address'));
                area.find('.pagoL-title').html(address);
                area.find('.pagoL-clipboard').data('text', window.btoa(address));
                area = pay_cnt.find('.pagoL-pay-amount');
                area.find('.pagoL-title').html(`${amount} ${cryptocurrency_uppercase}<div>${amount_extra && !amount_extra.toUpperCase().includes(cryptocurrency_uppercase) ? amount_extra.toUpperCase() : ''}</div>`);
                area.find('.pagoL-clipboard').data('text', window.btoa(amount));
                active_checkout.data('transaction-id', transaction_id);
                area = pay_cnt.find('.pagoL-qrcode-text');
                pay_cnt.find('.pagoL-qrcode-link').attr('href', PAGOL_SETTINGS.names[cryptocurrency][0] + ':' + address + '&amount=' + amount);
                pay_cnt.find('.pagoL-qrcode').attr('src', PAGOL_URL + 'vendor/qr.php?s=qr&d=' + PAGOL_SETTINGS.names[cryptocurrency][0] + '%3A' + address + '%3Famount%3D' + amount + '&md=1&wq=0&fc=' + PAGOL_SETTINGS.qr_code_color);
                area.find('img').attr('src', custom_token ? custom_token.img : `${PAGOL_URL}media/icon-${cryptocurrency}.svg`);
                area.find('div').html(pagoL_(ln ? 'Bitcoin Lightning Network' : 'Only send {T} to this address').replace('{T}', cryptocurrency_uppercase + network_label));
                countdown_area.html('');
                countdown = countdown_partial ? [countdown_partial[0], countdown_partial[1], true] : [PAGOL_SETTINGS.countdown, 0, true];
                clearInterval(intervals[active_checkout_id]);
                intervals[active_checkout_id] = setInterval(() => {
                    countdown_area.html(`${countdown[0]}:${countdown[1] < 10 ? '0' : ''}${countdown[1]}`);
                    countdown[1]--;
                    if (countdown[1] <= 0) {
                        if (countdown[0] <= 0) {
                            setTimeout(() => {
                                this.initTransaction_cancel(data, transaction_id);
                            }, 500);
                        }
                        if (countdown[0] < 5 && countdown[2]) {
                            countdown_area.parent().addClass('pagoL-countdown-expiring');
                            countdown[2] = false;
                        }
                        countdown[0]--;
                        countdown[1] = 59;
                    }
                }, 1000);
                clearInterval(intervals['check-' + active_checkout_id]);
                intervals['check-' + active_checkout_id] = setInterval(() => {
                    if (!busy[active_checkout_id]) {
                        busy[active_checkout_id] = true;
                        ajax('check-transactions', { transaction_id: transaction_id }, (response) => {
                            busy[active_checkout_id] = false;
                            if (response) {
                                if (response == 'expired') {
                                    this.initTransaction_cancel(data, transaction_id, 'Transaction expired');
                                    return console.error(response);
                                }
                                if (Array.isArray(response) && response[0] == 'error') {
                                    return console.error(response[1]);
                                }
                                if (ln) {
                                    if (response.confirmed) {
                                        this.completeTransaction(response, this.storageTransaction(active_checkout_id));
                                    }
                                } else {
                                    this.monitorTransaction(response);
                                }
                            }
                        });
                    }
                }, ln ? 1000 : 5000);
                this.storageTransaction(active_checkout_id, data);
                PAGOLibre.event('TransactionStarted', data);
                loading(pay_cnt, false);
                loading(body, false);
            },

            initTransaction_cancel: function (data, transaction_id, title = false, text = false) {
                PAGOLibre.event('TransactionCancelled', data);
                active_checkout.find('#pagoL-expired-tx-id').html(transaction_id);
                if (title) {
                    active_checkout.find('.pagoL-failed-cnt .pagoL-title').html(pagoL_(title));
                }
                if (text) {
                    active_checkout.find('.pagoL-failed-cnt .pagoL-title + .pagoL-text').html(pagoL_(text));
                }
                active_checkout.removeClass('pagoL-tx-cnt-active pagoL-pay-cnt-active').addClass('pagoL-failed-cnt-active');
                this.cancelTransaction(true);
            },

            monitorTransaction: function (encrypted_transaction) {
                let active_transaction = this.storageTransaction(active_checkout_id);
                let interval_id = 'check-' + active_checkout_id;
                clearInterval(intervals[active_checkout_id]);
                clearInterval(intervals[interval_id]);
                intervals[interval_id] = setInterval(() => {
                    if (active_checkout && !busy[active_checkout_id]) {
                        busy[active_checkout_id] = true;
                        ajax('check-transaction', { transaction: encrypted_transaction }, (response) => {
                            busy[active_checkout_id] = false;
                            active_checkout.find('.pagoL-tx-confirmations').html(response.confirmations + ' / ' + active_transaction.min_confirmations);
                            if (response.confirmations) {
                                active_checkout.find('.pagoL-tx-status').addClass('pagoL-tx-status-confirmed').html(pagoL_('Confirmed'));
                            }
                            if (response.confirmed) {
                                if (response.underpayment) {
                                    active_checkout.removeClass('pagoL-tx-cnt-active').addClass('pagoL-underpayment-cnt-active');
                                    clearInterval(intervals[interval_id]);
                                    active_checkout.find('#pagoL-underpaid-tx-id').html(active_transaction.id);
                                    this.cancelTransaction();
                                } else {
                                    this.completeTransaction(active_transaction, response, interval_id);
                                }
                            }
                        });
                    }
                }, 3000);
                if (active_transaction) {
                    active_transaction.detected = true;
                    active_transaction.encrypted = encrypted_transaction;
                }
                if (active_checkout_id) {
                    this.storageTransaction(active_checkout_id, active_transaction);
                }
                if (active_checkout) {
                    active_checkout.addClass('pagoL-tx-cnt-active');
                    active_checkout.find('.pagoL-tx-status').removeClass('pagoL-tx-status-confirmed').html(pagoL_('Pending'));
                    active_checkout.find('.pagoL-tx-confirmations').html('0 / ' + active_transaction.min_confirmations);
                    this.showCancelButton('.pagoL-tx-cnt', PAGOLibre.isFiat(active_transaction.external_reference) || PAGOLibre.isFiat(active_transaction.cryptocurrency) ? 30000 : 5000);
                }
            },

            completeTransaction: function (active_transaction, response, interval_id = false) {
                let area = active_checkout.find('.pagoL-complete-cnt');
                if (response.invoice) {
                    area.append(`<a href="${response.invoice}" target="_blank" class="pagoL-link pagoL-underline">${pagoL_('View Invoice')}</div>`);
                }
                if (response.redirect || PAGOL_SETTINGS.redirect) {
                    setTimeout(() => { document.location.href = response.redirect ? response.redirect : PAGOL_SETTINGS.redirect }, 300);
                }
                if (response.downloads_url) {
                    ajax('shop-downloads', { encrypted_transaction_id: PAGOLibre.getURL('downloads', response.downloads_url) }, (response) => {
                        for (var i = 0; i < response[0].length; i++) {
                            _.download(response[0][i]);
                        }
                        setTimeout(() => { ajax('shop-delete-downloads', { file_names: response[1] }) }, 1000);
                    });
                }
                if (response.license_key) {
                    area.append(`<div class="pagoL-input pagoL-input-license-key"><span>${pagoL_('License Key')}</span><input value="${response.license_key}" type="text" readonly></div>`);
                }
                if (active_transaction.redirect) {
                    setTimeout(() => { document.location.href = encodeURI(`${active_transaction.redirect}${active_transaction.redirect.includes('?') ? '&' : '?'}transaction_id=${active_transaction.id}&amount=${active_transaction.amount}&address=${active_transaction.address}&cryptocurrency=${active_transaction.cryptocurrency}&external_reference=${active_transaction.external_reference}`) }, 300);
                } else {
                    active_checkout.removeClass('pagoL-tx-cnt-active').addClass('pagoL-complete-cnt-active');
                }
                clearTimeout(timeout);
                clearInterval(intervals[interval_id]);
                area.find('.pagoL-text').removeClass('pagoL-order-processing');
                area.find('.pagoL-cancel-transaction').remove();
                PAGOLibre.event('TransactionCompleted', active_transaction);
                this.cancelTransaction();
            },

            cancelTransaction: function (delete_db_transaction = false) {
                if (delete_db_transaction && !PAGOLibre.getURL('demo')) {
                    let active_transaction = this.storageTransaction(active_checkout_id);
                    if (active_transaction && active_transaction.encrypted && !active_transaction.prevent_cancel) {
                        ajax('cancel-transaction', { transaction: active_transaction.encrypted });
                    }
                }
                clearInterval(intervals[active_checkout_id]);
                clearInterval(intervals['check-' + active_checkout_id]);
                active_checkout.removeClass('pagoL-pay-cnt-active');
                busy[active_checkout_id] = false;
                this.storageTransaction(active_checkout_id, 'delete');
                active_checkout_id = false;
                active_checkout = false;
            },

            storageTransaction: function (checkout_id, transaction = false) {
                let transactions = storage('pagoL-active-transaction', -1, {});
                let exists = checkout_id in transactions;
                if (transaction) {
                    if (transaction == 'delete') {
                        delete transactions[checkout_id];
                    } else {
                        if (exists) {
                            transaction = Object.assign(transactions[checkout_id], transaction);
                            transaction.storage_time = transactions[checkout_id].storage_time;
                        } else {
                            transaction.storage_time = Date.now();
                        }
                        transaction.storage_time = exists ? transactions[checkout_id].storage_time : Date.now();
                        transactions[checkout_id] = transaction;
                    }
                } else {
                    if (exists) {
                        if (transactions[checkout_id].detected || ((transactions[checkout_id].storage_time + (PAGOL_SETTINGS.countdown * 60000)) > Date.now())) {
                            return transactions[checkout_id];
                        } else {
                            delete transactions[checkout_id];
                        }
                    }
                }
                storage('pagoL-active-transaction', transactions);
                return false;
            },

            show: function (checkout_id) {
                body.find(`[data-pagoL="${checkout_id}"] > div, [data-pagolibre="${checkout_id}"] > div`).removeClass('pagoL-hidden'); // Deprecated: remove , [data-pagolibre="${checkout_id}"] > div
            },

            openPopup(checkout_id, open = true) {
                activate(body.find(`[data-pagoL="${checkout_id}"],[data-pagolibre="${checkout_id}"]`).find('.pagoL-popup,.pagoL-popup-overlay'), open); // Deprecated: remove ,[data-pagolibre="${checkout_id}"]
            },

            getBillingDetails: function (area) {
                if (area.find('#pagoL-billing [name="name"]').val().trim()) {
                    let billing = {};
                    area.find('#pagoL-billing input, #pagoL-billing select').e.forEach(e => {
                        billing[_(e).attr('name')] = _(e).val();
                    });
                    storage('pagoL-billing', billing);
                    return billing;
                }
                return '';
            },

            vat: function (checkout) {
                let vat = checkout.find('.pagoL-vat');
                let select = checkout.find('#pagoL-billing [name="country"]').e[0];
                if (vat.e.length) {
                    loading(vat);
                    ajax('vat', {
                        amount: checkout.data('discount-price') ? checkout.data('discount-price') : checkout.data('start-price'),
                        country_code: _(select.options[select.selectedIndex]).data('country-code'),
                        currency: checkout.data('currency'),
                        vat_number: checkout.find('[name="vat"]').val()
                    }, (response) => {
                        vat.html(response[4]);
                        vat.attr('data-country', response[3]).attr('data-country-code', response[2]).attr('data-amount', response[1]).attr('data-percentage', response[5]);
                        checkout.attr('data-price', response[0]);
                        checkout.find('.pagoL-amount-fiat-total').html(response[0]);
                        loading(vat, false);
                    });
                }
            },

            showInvoiceBox: function (btn) {
                let billing = storage('pagoL-billing');
                let checkout = checkoutParent(btn);
                let countries = checkout.find('#pagoL-billing [name="country"]');
                if (billing) {
                    for (var key in billing) {
                        checkout.find(`#pagoL-billing [name="${key}"]`).val(billing[key]);
                    }
                } else {
                    countries.val(checkout.find('.pagoL-vat').attr('data-country'));
                }
                _(btn).addClass('pagoL-hidden');
                this.vat(checkout);
                _('#pagoL-billing').removeClass('pagoL-hidden');
            },

            showCancelButton: function (area, timeout_seconds = 5000) {
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    active_checkout.find(area).append(`<div class="pagoL-cancel-transaction pagoL-underline">${pagoL_('Cancel')}</div>`);
                }, timeout_seconds);
            }
        },

        event: function (name, data = {}) {
            data['checkout_id'] = active_checkout_id;
            document.dispatchEvent(new CustomEvent('PAGOL' + name, { detail: data }));
        },

        baseCode: function (cryptocurrency_code) {
            return cryptocurrency_code.replace('_tron', '').replace('_TRON', '').replace('_bsc', '').replace('_BSC', '').replace('_ln', '').replace('_LN', '').replace('_sol', '').replace('_SOL', '');
        },

        network: function (cryptocurrency_code, label = true, exclude_optional = false) {
            let networks = admin ? PAGOL_ADMIN_SETTINGS.cryptocurrencies : PAGOL_SETTINGS.cryptocurrencies;
            let code = label === 'code';
            cryptocurrency_code = cryptocurrency_code.toLowerCase();
            if (exclude_optional && ['btc', 'eth', 'ltc', 'xrp', 'algo', 'bch', 'doge', 'bnb', 'sol'].includes(cryptocurrency_code)) {
                return '';
            }
            for (var key in networks) {
                if (networks[key].includes(cryptocurrency_code)) {
                    if (code) return key.toLowerCase();
                    let text = key.replace('TRX', 'TRON').replace('SOL', 'SOLANA') + ' ' + pagoL_('network');
                    return label ? `<span class="pagoL-label">${text}</span>` : ' ' + pagoL_('on') + ' ' + text;
                }
            }
            return code ? cryptocurrency_code : '';
        },

        isFiat: function (value) {
            return ['stripe', 'verifone', 'paypal'].includes(value) || (value.length == 3 && !PAGOLibre.network(value));
        },

        lightbox: function (title, content, buttons = [], id = '') {
            let lightbox = body.find('#pagoL-lightbox');
            let code = '';
            for (var i = 0; i < buttons.length; i++) {
                code += `<div id="${buttons[i][0]}" class="pagoL-btn">${buttons[i][1]}</div>`;
            }
            lightbox.find('.pagoL-title').html(title);
            lightbox.find('.pagoL-lightbox-buttons').html(code);
            lightbox.find('#pagoL-lightbox-main').html(content).e[0].style.maxHeight = (window.innerHeight - (responsive ? 130 : 183)) + 'px';
            lightbox.data('lightbox-id', id);
            activate(body.find('#pagoL-lightbox-loading'), false);
            activate(lightbox);
        },

        lightboxClose: function () {
            activate(body.find('#pagoL-lightbox'), false);
        },

        metamask: {
            accounts: false,

            transactionRequest: async function (value, to, cryptocurrency_code = false, onSuccess = false) {
                let params = {
                    from: this.accounts[0],
                    to: cryptocurrency_code ? PAGOLibre.web3.tokens[cryptocurrency_code][0] : to,
                    value: cryptocurrency_code ? '' : value,
                    data: cryptocurrency_code ? PAGOLibre.web3.getData(to, value) : ''
                };
                let active_cryptocurrency = PAGOLibre.checkout.storageTransaction(active_checkout_id);
                if (active_cryptocurrency && !['BAT', 'ETH'].includes(active_cryptocurrency.cryptocurrency.toUpperCase())) {
                    params.gas = "10000";
                }
                await ethereum.request({
                    method: 'eth_sendTransaction',
                    params: [params]
                }).then((hash) => {
                    if (onSuccess) onSuccess(hash);
                });
            },

            transaction: async function (value, to, token = false, onSuccess = false) {
                let chainID = [1, '1'];
                if (token) {
                    token = token.toUpperCase();
                    if (['ETH', 'BNB'].includes(token)) {
                        if (token == 'BNB') {
                            chainID = [56, '38'];
                        }
                        token = false;
                    } else {
                        if (typeof Web3 === ND) {
                            return _.load(PAGOL_URL + 'vendor/web3.js', true, () => { this.transaction(value, to, token, onSuccess) });
                        }
                        if (!PAGOLibre.web3.tokens) {
                            return PAGOLibre.web3.getTokens(() => { this.transaction(value, to, token, onSuccess) });
                        }
                    }
                }
                if (!value.substr(0, 2) !== '0x') {
                    value = PAGOLibre.web3.toHex(value, token ? PAGOLibre.web3.tokens[token][1] : 18);
                }
                if (ethereum.networkVersion != chainID[0]) {
                    try {
                        await ethereum.request({
                            method: 'wallet_switchEthereumChain',
                            params: [{ chainId: '0x' + chainID[1] }]
                        }).then(() => {
                            this.transactionCheckAccount(value, to, token, onSuccess);
                        });
                    } catch (error) {
                        if (error.code === 4902) {
                            await ethereum.request({
                                method: 'wallet_addEthereumChain',
                                params: [
                                    {
                                        chainName: 'Polygon Mainnet',
                                        chainId: web3.toHex(chainId),
                                        nativeCurrency: { name: 'MATIC', decimals: 18, symbol: 'MATIC' },
                                        rpcUrls: ['https://polygon-rpc.com/']
                                    }
                                ]
                            });
                        } else {
                            console.error(error);
                        }
                    }
                } else {
                    this.transactionCheckAccount(value, to, token, onSuccess);
                }
                if (active_button) {
                    loading(active_button, false);
                    active_button = false;
                }
            },

            transactionCheckAccount: async function (value, to, token = false, onSuccess = false) {
                if (this.accounts) {
                    this.transactionRequest(value, to, token, onSuccess);
                } else {
                    await ethereum.request({ method: 'eth_requestAccounts' }).then((response) => {
                        this.accounts = response;
                        this.transactionRequest(value, to, token, onSuccess);
                    });
                }
            },

            active: function () {
                return window.ethereum && window.ethereum.isMetaMask;
            }
        },

        web3: {
            tokens: false,

            getData: function (to, amount) {
                const web3 = new Web3();
                return web3.eth.abi.encodeFunctionCall({ constant: false, inputs: [{ name: '_to', type: 'address' }, { name: '_value', type: 'uint256' }], name: 'transfer', outputs: [], payable: false, stateMutability: 'nonpayable', type: 'function' }, [to, amount]);
            },

            toHex: function (value, decimals) {
                return '0x' + (parseFloat(value) * (10 ** decimals)).toString(16);
            },

            getTokens: function (onSuccess = false) {
                ajax('get-tokens', {}, (response) => {
                    PAGOLibre.web3.tokens = response;
                    if (onSuccess) onSuccess();
                });
            }
        }
    }

    window.PAGOLibre = PAGOLibre;

    function pagoL_(text) {
        return PAGOL_TRANSLATIONS && text in PAGOL_TRANSLATIONS ? PAGOL_TRANSLATIONS[text] : text;
    }

    function loading(element, action = -1) {
        return PAGOLibre.loading(element, action);
    }

    function ajax(function_name, data = {}, onSuccess = false) {
        return PAGOLibre.ajax(function_name, data, onSuccess);
    }

    function activate(element, activate = true) {
        return PAGOLibre.activate(element, activate);
    }

    function checkoutParent(element) {
        return _(element.closest('.pagoL-main'));
    }

    function getScriptParameters(url) {
        var c = url.split('?').pop().split('&');
        var p = {};
        for (var i = 0; i < c.length; i++) {
            var d = c[i].split('=');
            p[d[0]] = d[1]
        }
        return p;
    }

    function storage(name, value = -1, default_value = false) {
        return _.storage(name, value, default_value);
    }

    /*
    * ----------------------------------------------------------
    * Init
    * ----------------------------------------------------------
    */

    document.addEventListener('DOMContentLoaded', () => {
        body = _(document.body);
        if (!admin) {
            if (typeof PAGOL_URL != ND) {
                return;
            }
            if (PAGOLibre.getURL('pay')) {
                body.addClass('pagoL-loading');
            }
            cloud = PAGOLibre.getURL('cloud');
            exchange = body.find('#pagolibre-exchange-init');
            for (var i = 0; i < scripts.length; i++) {
                if (['pagolibre', 'pagolibre-js', 'pagoL-cloud'].includes(scripts[i].id)) {
                    let url = scripts[i].src.replace('js/client.js', '').replace('js/client.min.js', '');
                    let parameters = getScriptParameters(url);
                    if (url.includes('?')) {
                        url = url.substr(0, url.indexOf('?'));
                    }
                    _.load(url + 'css/client.css?v=' + PAGOL_VERSION, false);
                    if ('lang' in parameters) {
                        language = parameters.lang;
                    }
                    if ('cloud' in parameters) {
                        cloud = parameters.cloud;
                    } else if (typeof PAGOL_CLOUD_TOKEN !== ND) {
                        cloud = PAGOL_CLOUD_TOKEN;
                    }
                    if (url.includes('?')) {
                        url = url.substr(0, url.indexOf('?'));
                    }
                    checkouts = admin ? [] : body.find('[data-pagoL],[data-pagolibre]'); // Deprecated: remove ,[data-pagolibre]
                    let parameters_ajax = { language: language, cloud: cloud };
                    parameters_ajax['init'] = true;
                    _.ajax(url + 'init.php', parameters_ajax, (response) => {
                        if (response === 'no-credit-balance') {
                            return console.warn('PagoLibre: No credit balance.');
                        }
                        _.load(false, true, false, response);
                        if (exchange.e.length) {
                            _.ajax(url + 'init.php', { language: language, cloud: cloud, init_exchange: true }, (response) => {
                                _.load(url + 'apps/exchange/exchange.css?v=' + PAGOL_VERSION, false, () => {
                                    exchange.html(response);
                                    _.load(url + 'apps/exchange/exchange.js?v=' + PAGOL_VERSION);
                                });
                            });
                        } else {
                            checkouts.e.forEach(e => {
                                PAGOLibre.checkout.init(PAGOLibre.checkout.settings(e), e);
                            });
                        }
                    });
                    globalInit();
                    PAGOLibre.event('Init');

                    /*
                    * ----------------------------------------------------------
                    * Checkout
                    * ----------------------------------------------------------
                    */

                    body.on('click', '.pagoL-payment-methods > div', function () {
                        let checkout = checkoutParent(this);
                        let id = checkout.parent().attr('data-pagoL');
                        if (!id) id = checkout.parent().attr('data-pagolibre'); // Deprecated
                        let user_details = {};
                        let custom_fields = {};
                        let user_details_cnt = checkout.find('.pagoL-user-details');
                        let custom_fields_cnt = checkout.find('.pagoL-custom-fields');
                        let error = false;
                        let error_box = checkout.find('#pagoL-error-message');
                        let cryptocurrency_code = _(this).attr('data-cryptocurrency');
                        let external_reference = checkout.attr('data-external-reference');
                        let amount = checkout.attr('data-price');
                        let input = checkout.find('#user-amount');
                        let custom_token = _(this).attr('data-custom-coin');
                        let vat = checkout.find('.pagoL-vat');
                        let discount = checkout.find('#pagoL-discount-field input').val().trim();
                        let url = document.location.href;
                        let notes = '';
                        checkout.find('.pagoL-input').removeClass('pagoL-error');
                        if (active_checkout_id && id != active_checkout_id) {
                            error = 'Another transaction is being processed. Complete the transaction or cancel it to start a new one.';
                        }
                        if (user_details_cnt.e.length) {
                            user_details_cnt.find('input').e.forEach(e => {
                                let value = e.value.trim();
                                if (value) {
                                    user_details[e.getAttribute('name')] = value;
                                } else if (!PAGOLibre.getURL('currency')) {
                                    e.parentElement.classList.add('pagoL-error');
                                    error = 'Please provide your personal information';
                                }
                            });
                            storage('pagoL-user-details', user_details);
                        }
                        if (custom_fields_cnt.e.length) {
                            custom_fields_cnt.find('input, select, textarea').e.forEach(e => {
                                let value = e.type == 'checkbox' ? e.checked : e.value.trim();
                                if (value) {
                                    let field_name = e.getAttribute('name');
                                    custom_fields[field_name] = value;
                                    notes += field_name + ': ' + value + '\n';
                                } else if (e.hasAttribute('required')) {
                                    e.parentElement.classList.add('pagoL-error');
                                    error = 'Please provide the required information';
                                }
                            });
                            storage('pagoL-custom-fields', custom_fields);
                        }
                        if (!amount || amount == -1 || amount == '0') {
                            amount = input.find('input');
                            if (amount) {
                                amount = amount.val();
                                if (!amount) {
                                    input.addClass('pagoL-error');
                                    error = 'Please specify the desired amount';
                                }
                            }
                        }
                        error_box.html(error ? pagoL_(error) : '');
                        if (error) {
                            body.removeClass('pagoL-loading');
                            error_box.scrollTo();
                            return;
                        }
                        active_checkout = checkout;
                        active_checkout_id = id;
                        notes += active_checkout.attr('data-note');
                        if (custom_token) {
                            custom_token = { type: custom_token, img: _(this).find('img').attr('src') };
                        }
                        let billing = PAGOLibre.checkout.getBillingDetails(active_checkout);
                        if (vat.e.length && vat.attr('data-amount')) {
                            vat = { amount: vat.attr('data-amount'), percentage: vat.attr('data-percentage'), country: vat.attr('data-country'), country_code: vat.attr('data-country-code') }
                        } else {
                            vat = false;
                        }
                        if (PAGOLibre.getURL('cc')) {
                            url = url.replace('cc=' + PAGOLibre.getURL('cc'), '');
                            if (url.slice(-1) == '?') {
                                url = url.slice(0, -1);
                            }
                            window.history.replaceState({}, document.title, url);
                        }
                        active_checkout.addClass('pagoL-pay-cnt-active');
                        loading(active_checkout.find('.pagoL-pay-cnt'));
                        ajax('create-transaction', {
                            amount: amount,
                            cryptocurrency_code: cryptocurrency_code,
                            currency_code: active_checkout.attr('data-currency'),
                            external_reference: active_checkout.attr('data-external-reference'),
                            title: active_checkout.attr('data-title'),
                            note: notes.trim(),
                            custom_token: custom_token,
                            url: url,
                            billing: billing ? JSON.stringify(billing) : '',
                            vat: vat,
                            checkout_id: active_checkout_id,
                            user_details: user_details,
                            discount_code: checkout.data('discount-code'),
                            type: PAGOL_SETTINGS.exchange && PAGOLibre.getURL('type') == 3 ? 3 : (PAGOL_SETTINGS.shop && !active_checkout_id.includes('custom-') ? 2 : 1)
                        }, (response) => {
                            if (response[0] == 'error') {
                                error_box.html(pagoL_('Something went wrong. Please try again or select another cryptocurrency.'));
                                return PAGOLibre.checkout.cancelTransaction(true);
                            }
                            if (!checkout.data('price')) {
                                return PAGOLibre.checkout.completeTransaction({}, response);
                            }
                            if (PAGOLibre.isFiat(cryptocurrency_code)) {
                                let data = { id: response[0], amount: amount, external_reference: cryptocurrency_code, redirect: active_checkout.attr('data-redirect'), encrypted: response[3] };
                                PAGOLibre.checkout.storageTransaction(active_checkout_id, data);
                                PAGOLibre.event('TransactionStarted', data);
                                document.location = response[2];
                            } else {
                                PAGOLibre.checkout.initTransaction(response[0], response[1], response[2], cryptocurrency_code, external_reference, false, custom_token, active_checkout.attr('data-redirect'), vat, response[4], amount + ' ' + active_checkout.attr('data-currency'), response[3]);
                            }
                        });
                    });

                    body.on('click', '.pagoL-back', function () {
                        active_checkout.find('.pagoL-pay-top-main').addClass('pagoL-hidden');
                    });

                    body.on('click', '#pagoL-abort-cancel, #pagoL-confirm-cancel', function () {
                        active_checkout.find('.pagoL-pay-top-main').removeClass('pagoL-hidden');
                    });

                    body.on('click', '#pagoL-confirm-cancel', function () {
                        let transaction = PAGOLibre.checkout.storageTransaction(active_checkout_id);
                        PAGOLibre.event('TransactionCancelled', transaction);
                        PAGOLibre.checkout.cancelTransaction(true);
                        if (storage('pagoL-quote') && transaction.checkout_id.includes(storage('pagoL-quote').id)) {
                            let url = transaction.redirect;
                            body.addClass('pagoL-loading');
                            document.location = url.includes('payment_status') ? url : (url + (url.includes('?') ? '&' : '?') + 'payment_status=cancelled');
                        }
                    });

                    body.on('click', '.pagoL-failed-cnt .pagoL-btn', function () {
                        body.find('.pagoL-failed-cnt-active').removeClass('pagoL-failed-cnt-active');
                    });

                    body.on('click', '#metamask', function () {
                        if (loading(this)) return;
                        active_button = this;
                        let active_transaction = PAGOLibre.checkout.storageTransaction(active_checkout_id);
                        PAGOLibre.metamask.transaction(active_transaction.amount, active_transaction.to, active_transaction.cryptocurrency);
                    });

                    body.on('click', '.pagoL-cancel-transaction', function () {
                        storage('pagoL-active-transaction', {});
                        location.reload();
                    });

                    /*
                    * ----------------------------------------------------------
                    * Miscellaneous
                    * ----------------------------------------------------------
                    */

                    body.on('click', '.pagoL-btn-popup,.pagoL-popup-close', function () {
                        activate(_(this.closest('[data-pagoL],[data-pagolibre]')).find('.pagoL-popup,.pagoL-popup-overlay'), _(this).hasClass('pagoL-btn-popup')); // Deprecated: remove ,[data-pagolibre]
                    });

                    body.on('click', '.pagoL-collapse-btn', function () {
                        _(this).parent().removeClass('pagoL-collapse');
                        _(this).remove();
                    });

                    /*
                    * ----------------------------------------------------------
                    * Invoice & VAT
                    * ----------------------------------------------------------
                    */

                    body.on('click', '#pagoL-btn-invoice', function () {
                        PAGOLibre.checkout.showInvoiceBox(this);
                    });

                    body.on('click', '#pagoL-btn-invoice-close', function () {
                        let checkout = checkoutParent(this);
                        _(this).parent().addClass('pagoL-hidden');
                        checkout.find('#pagoL-btn-invoice').removeClass('pagoL-hidden');
                        checkout.find('#pagoL-billing').find('input, select').val('');
                        storage('pagoL-billing', false);
                        PAGOLibre.checkout.vat(checkout);
                    });

                    body.on('change', '#pagoL-billing [name="country"]', function () {
                        PAGOLibre.checkout.vat(checkoutParent(this));
                    });

                    body.on('focusout', '#pagoL-billing [name="vat"]', function () {
                        PAGOLibre.checkout.vat(checkoutParent(this));
                    });

                    /*
                    * ----------------------------------------------------------
                    * Shop
                    * ----------------------------------------------------------
                    */

                    body.on('click', '#pagoL-discount-field .pagoL-btn', function () {
                        let discount_code = _(this).parent().find('input').val().trim();
                        let checkout = checkoutParent(this);
                        let amount = parseFloat(checkout.data('start-price'));
                        if (!discount_code || loading(this)) {
                            return;
                        }
                        checkout.data('discount-code', '');
                        ajax('apply-discount', {
                            discount_code: discount_code,
                            checkout_id: checkout.data('checkout-id'),
                            amount: amount,
                        }, (response) => {
                            loading(this, false);
                            let valid = response !== false && response !== amount && !isNaN(response) && parseFloat(response) < amount;
                            checkout.find('.pagoL-amount-fiat-total').html(valid ? response : checkout.data('start-price'));
                            checkout.data('price', valid ? response : checkout.data('start-price')).data('discount-code', valid ? discount_code : '');
                            checkout.data('discount-price', valid ? (response === 0 ? '0' : response) : '');
                            checkout.find('#pagoL-error-message').html(valid ? '' : pagoL_('Invalid discount code'));
                            if (valid) {
                                PAGOLibre.checkout.vat(checkout);
                            }
                        });
                    });
                    break;
                }
            }
        } else {
            globalInit();
        }

        body.on('click', '#pagoL-lightbox-close', function () {
            PAGOLibre.lightboxClose();
        });

        body.on('click', '.pagoL-select', function () {
            let ul = _(this).find('ul');
            let active = ul.hasClass('pagoL-active');
            activate(ul, !active);
            if (admin && !active) {
                setTimeout(() => { PAGOLAdmin.active_element = ul.e[0] }, 300);
            }
        });

        body.on('click', '.pagoL-select li', function () {
            let select = _(this.closest('.pagoL-select'));
            let value = _(this).attr('data-value');
            var item = select.find(`[data-value="${value}"]`);
            activate(select.find('li'), false);
            select.find('p').attr('data-value', value).html(item.html());
            activate(item, true);
            if (admin) PAGOLAdmin.active_element = false;
        });

    }, false);

    /*
    * ----------------------------------------------------------
    * Global
    * ----------------------------------------------------------
    */

    function globalInit() {
        body.on('click', '.pagoL-clipboard', function () {
            let tooltip = _(this).find('span');
            let text = tooltip.html();
            navigator.clipboard.writeText(window.atob(_(this).attr('data-text')));
            tooltip.html(pagoL_('Copied'));
            activate(this);
            setTimeout(() => {
                activate(this, false);
                tooltip.html(text);
            }, 2000);
        });
    }
}());