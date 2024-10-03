<?php
use Web3p\RLP\Types\Str;

/*
 * ==========================================================
 * INIT.PHP
 * ==========================================================
 *
 * This file loads and initilizes the payments
 *
 */

if (!file_exists(__DIR__ . '/config.php')) {
    die();
}
require_once(__DIR__ . '/functions.php');
if (isset($_POST['data'])) {
    $_POST = json_decode($_POST['data'], true);
}
if (PAGOL_CLOUD) {
    pagoL_cloud_load();
    if (floatval(pagoL_settings_db('credit_balance')) < 0) {
        pagoL_cloud_credit_email(-1);
        die('no-credit-balance');
    }
}
if (isset($_POST['init'])) {
    pagoL_checkout_init();
}
if (isset($_POST['checkout'])) {
    pagoL_checkout($_POST['checkout']);
}
if (isset($_POST['init_exchange'])) {
    require_once(__DIR__ . '/apps/exchange/exchange_code.php');
    pagoL_exchange_init();
}

function pagoL_checkout_init() {
    $qr_color = pagoL_settings_get('color-2');
    if ($qr_color) {
        if (strpos('#', $qr_color) !== false) {
            $qr_color = substr($qr_color, 1);
        } else {
            $qr_color = str_replace(['rgb(', ')', ',', ' '], ['', '', '-', ''], $qr_color);
        }
    } else {
        $qr_color = '23413e';
    }
    $language = pagoL_language();
    $translations = $language ? file_get_contents(__DIR__ . '/resources/languages/client/' . $language . '.json') : '{}';
    $settings = [
        'qr_code_color' => $qr_color,
        'countdown' => pagoL_settings_get('refresh-interval', 60),
        'webhook' => pagoL_settings_get('webhook-url'),
        'redirect' => pagoL_settings_get('payment-redirect'),
        'vat_validation' => pagoL_settings_get('vat-validation'),
        'names' => pagoL_crypto_name(),
        'cryptocurrencies' => pagoL_get_cryptocurrency_codes()
    ];
    if (defined('PAGOL_EXCHANGE')) {
        $settings['exchange'] = [
            'identity_type' => pagoL_settings_get('exchange-identity-type'),
            'email_verification' => pagoL_settings_get('exchange-email-verification'),
            'testnet_btc' => pagoL_settings_get('testnet-btc'),
            'testnet_eth' => pagoL_settings_get('testnet-eth'),
            'texts' => [pagoL_(pagoL_settings_get('exchange-manual-payments-text-send-complete', 'The payment of {amount} will be sent to the provided payment details:'))],
            'url_rewrite_checkout' => PAGOL_CLOUD ? 'checkout/' : pagoL_settings_get('url-rewrite-checkout')
        ];
    }
    if (defined('PAGOL_SHOP')) {
        $settings['shop'] = true;
    }
    echo 'var PAGOL_TRANSLATIONS = ' . ($translations ? $translations : '{}') . '; var PAGOL_URL = "' . PAGOL_URL . '"; var PAGOL_SETTINGS = ' . json_encode($settings, JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE) . ';';
}

function pagoL_checkout_custom_fields() {
    $code = '';
    $custom_fields = pagoL_settings_get_repeater(['checkout-custom-field-type', 'checkout-custom-field-name', 'checkout-custom-field-required']);
    for ($i = 0; $i < count($custom_fields); $i++) {
        $type = $custom_fields[$i][0];
        $field_name = $custom_fields[$i][1];
        if ($field_name) {
            if ($type != 'select') {
                $code .= '<div class="pagoL-input pagoL-input-' . $type . '"><span>' . pagoL_($field_name) . '</span>';
            }
            switch ($type) {
                case 'text':
                    $code .= '<input name="' . $field_name . '" type="text"' . ($custom_fields[$i][2] ? ' required' : '') . '>';
                    break;
                case 'checkbox':
                    $code .= '<input name="' . $field_name . '" type="checkbox"' . ($custom_fields[$i][2] ? ' required' : '') . '>';
                    break;
                case 'textarea':
                    $code .= '<textarea name="' . $field_name . '"' . ($custom_fields[$i][2] ? ' required' : '') . '></textarea>';
                    break;
                case 'select':
                    $select_values = explode(':', $field_name);
                    $code .= '<div class="pagoL-input pagoL-input-select"><span>' . pagoL_($select_values[0]) . '</span><select name="' . $select_values[0] . '"' . ($custom_fields[$i][2] ? ' required' : '') . '><option value=""></option>';
                    for ($j = 1; $j < count($select_values); $j++) {
                        $code .= '<option value="' . str_replace('"', '\'', $select_values[$j]) . '">' . pagoL_($select_values[$j]) . '</option>';
                    }
                    $code .= '</select>';
                    break;
            }
            $code .= '</div>';
        }
    }
    if ($code) {
        echo '<div class="pagoL-custom-fields"><div class="pagoL-title">' . pagoL_(pagoL_settings_get('checkout-custom-fields-title', 'Information')) . '</div>' . $code . '</div>';
    }
}

function pagoL_checkout($settings) {
    $checkout_id = $settings['checkout_id'];
    $custom = strpos($checkout_id, 'custom') !== false;
    $cryptocurrencies = pagoL_get_cryptocurrency_codes();
    $cryptocurrencies_code = '';
    $collapse = pagoL_settings_get('collapse');
    $body_classes = '';
    if (!$custom) {
        $settings = pagoL_checkout_get($checkout_id);
    }
    if (!$settings) {
        die();
    }
    $title = ($custom && !empty($settings['title'])) || (empty($settings['hide_title']) && !pagoL_settings_get('hide-title'));
    $image = pagoL_isset($settings, 'image');
    $currency_code = empty($settings['currency']) ? pagoL_settings_get('currency', 'USD') : $settings['currency'];
    if (pagoL_settings_get('stripe-active') || pagoL_settings_get('verifone-active')) {
        $cryptocurrencies_code .= '<div data-cryptocurrency="' . (pagoL_settings_get('stripe-active') ? 'stripe' : 'verifone') . '" class="pagoL-flex"><img src="' . PAGOL_URL . 'media/icon-cc.svg" alt="' . pagoL_('Credit or debit card') . '" /><span>' . pagoL_('Credit or debit card') . '</span></div>';
    }
    if (pagoL_settings_get('paypal-active')) {
        $cryptocurrencies_code .= '<div data-cryptocurrency="paypal" class="pagoL-flex"><img src="' . PAGOL_URL . 'media/icon-pp-2.svg" alt="PayPal" /><span>PayPal</span></div>';
    }
    if (pagoL_settings_get('ln-node-active')) {
        $cryptocurrencies_code .= '<div data-cryptocurrency="btc_ln" class="pagoL-flex"><img src="' . PAGOL_URL . 'media/icon-btc_ln.svg" alt="Bitcoin Lightning Network" /><span>Bitcoin<span class="pagoL-label">Lightning <div>Network</div></span></span><span>BTC</span></div>';
    }
    if ($title) {
        $title = pagoL_isset($settings, 'title', pagoL_settings_get('form-title'));
        if ($title) {
            $title = '<div class="pagoL-top pagoL-checkout-top"><div><div class="pagoL-title">' . pagoL_($title) . '</div><div class="pagoL-text">' . pagoL_render_text(empty($settings['description']) ? pagoL_settings_get('form-description', '') : $settings['description']) . '</div></div></div>';
        }
    }
    if ($image) {
        $image = '<div class="pagoL-background-image" style="background-image: url(\'' . PAGOL_URL . 'uploads' . pagoL_cloud_path_part() . '/' . $image . '\')"></div>';
    }
    foreach ($cryptocurrencies as $value) {
        for ($i = 0; $i < count($value); $i++) {
            $cryptocurrency_code = $value[$i];
            if (pagoL_settings_get_address($cryptocurrency_code)) {
                $cryptocurrencies_code .= '<div data-cryptocurrency="' . $cryptocurrency_code . '"' . (pagoL_crypto_is_custom_token($cryptocurrency_code) ? ' data-custom-coin="' . pagoL_get_custom_tokens()[$cryptocurrency_code]['type'] . '"' : '') . ' class="pagoL-flex"><img src="' . pagoL_crypto_get_image($cryptocurrency_code) . '" alt="' . strtoupper($cryptocurrency_code) . '" /><span>' . pagoL_crypto_name($cryptocurrency_code, true) . pagoL_crypto_get_network($cryptocurrency_code, true, true) . '</span><span>' . strtoupper(pagoL_crypto_get_base_code($cryptocurrency_code)) . '</span></div>';
            }
        }
    }
    $checkout_price = floatval($settings['price']);
    if ($checkout_price == -1) {
        $checkout_price = '';
    }
    $checkout_start_price = $checkout_price;
    $checkout_type = empty($_POST['payment_page']) ? pagoL_isset($settings, 'type', 'I') : 'I';
    $checkout_type = pagoL_isset(['I' => 'inline', 'L' => 'link', 'P' => 'popup', 'H' => 'hidden'], $checkout_type, $checkout_type);
    echo '<!-- PagoLibre - https://PagoLibre -->';
    if ($checkout_type == 'popup') {
        echo '<div class="pagoL-btn pagoL-btn-popup"><img src="' . PAGOL_URL . 'media/icon-cryptos.svg" alt="" />' . pagoL_(pagoL_settings_get('button-text', 'Pay now')) . '</div><div class="pagoL-popup-overlay"></div>';
    }
    $css = false;
    $color_1 = pagoL_settings_get('color-1');
    $color_2 = pagoL_settings_get('color-2');
    $color_3 = pagoL_settings_get('color-3');
    $vat = pagoL_settings_get('vat');
    if ($vat && $checkout_price) {
        $vat_details = pagoL_vat($checkout_price, false, $currency_code);
        $checkout_price = $vat_details[0];
        $vat = '<span class="pagoL-vat" data-country="' . $vat_details[3] . '" data-country-code="' . $vat_details[2] . '" data-amount="' . $vat_details[1] . '" data-percentage="' . $vat_details[5] . '">' . $vat_details[4] . '</span>';
    }
    if ($color_1) {
        $css = '.pagoL-payment-methods>div:hover .pagoL-label,.pagoL-payment-methods>div:hover,.pagoL-btn.pagoL-btn-border:hover, .pagoL-btn.pagoL-btn-border:active { border-color: ' . $color_1 . '; color: ' . $color_1 . '; }';
        $css .= '.pagoL-complete-cnt>i, .pagoL-failed-cnt>i,.pagoL-payment-methods>div:hover span+span,.pagoL-clipboard:hover,.pagoL-tx-cnt .pagoL-loading:before,.pagoL-input-btn .pagoL-btn.pagoL-loading:before,.pagoL-loading:before,.pagoL-btn-text:hover,.pagoL-input input[type="checkbox"]:checked:before,.pagoL-link:hover,.pagoL-link:active,.pagoL-underline:hover,.pagoL-underline:active,.pagoL-complete-cnt .pagoL-link:hover { color: ' . $color_1 . '; }';
        $css .= '.pagoL-tx-status,.pagoL-select ul li:hover,.pagoL-underline:hover:after { background-color: ' . $color_1 . '; }';
        $css .= '.pagoL-input input:focus, .pagoL-input input.pagoL-focus, .pagoL-input select:focus, .pagoL-input select.pagoL-focus, .pagoL-input textarea:focus, .pagoL-input textarea.pagoL-focus { border-color: ' . $color_1 . '; box-shadow: 0 0 5px rgb(0 0 0 / 20%);';
    }
    if ($color_2) {
        $css .= '.pagoL-box { color: ' . $color_2 . '; }';
    }
    if ($color_3) {
        $css .= '.pagoL-text,.pagoL-payment-methods>div span+span { color: ' . $color_3 . '; }';
        $css .= '.pagoL-btn.pagoL-btn-border { border-color: ' . $color_3 . '; color: ' . $color_3 . '; }';
    }
    if ($css) {
        echo '<style>' . $css . '</style>';
    }
    $shop_code = defined('PAGOL_SHOP') ? pagoL_shop_checkout_init($checkout_id) : '';
    if (pagoL_is_rtl(pagoL_language())) {
        $body_classes .= ' pagoL-rtl';
    }
    ?>
    <div class="pagoL-main pagoL-start pagoL-<?php echo $checkout_type . $body_classes ?>" data-checkout-id="<?php echo $checkout_id ?>" data-currency="<?php echo $currency_code ?>" data-price="<?php echo $checkout_price ?>" data-external-reference="<?php echo pagoL_isset($settings, 'external_reference', pagoL_isset($settings, 'external-reference', '')) ?>" data-title="<?php echo str_replace('"', '', pagoL_isset($settings, 'title', '')) ?>" data-note="<?php echo str_replace('"', '', pagoL_isset($settings, 'note', '')) ?>" data-redirect="<?php echo pagoL_isset($settings, 'redirect', '') ?>" data-start-price="<?php echo $checkout_start_price ?>">
        <?php
        if ($checkout_type == 'popup') {
            echo '<i class="pagoL-popup-close pagoL-icon-close"></i>';
        }
        ?>
        <div class="pagoL-cnt pagoL-box">
            <?php echo $image . $title ?>
            <div class="pagoL-body">
                <div id="pagoL-error-message" class="pagoL-error pagoL-text"></div>
                <div class="pagoL-flex pagoL-amount-fiat<?php echo $checkout_price ? '' : ' pagoL-donation' ?>">
                    <div class="pagoL-title">
                        <?php
                        pagoL_e($checkout_price ? 'Total' : 'Amount');
                        if (!$checkout_price) {
                            echo '<div class="pagoL-text">' . pagoL_(pagoL_settings_get('user-amount-text', 'Pay what you want')) . '</div>';
                        }
                        ?>
                    </div>
                    <div class="pagoL-title">
                        <?php echo $checkout_price ? strtoupper($currency_code) . ' <span class="pagoL-amount-fiat-total">' . pagoL_decimal_number($checkout_price) . '</span>' . $vat : '<div class="pagoL-input" id="user-amount"><span>' . strtoupper($currency_code) . '</span><input type="number" min="0" /></div>' ?>
                    </div>
                </div>
                <?php
                echo $shop_code;
                if (!$custom || strpos($checkout_id, 'custom-ex') === false) {
                    pagoL_checkout_custom_fields();
                }
                if (pagoL_settings_get('invoice-active')) {
                    echo pagoL_transactions_invoice_form();
                }
                ?>
                <div class="pagoL-payment-methods-cnt">
                    <div <?php echo $collapse ? 'class="pagoL-collapse"' : '' ?>>
                        <div class="pagoL-payment-methods">
                            <?php echo $cryptocurrencies_code ?>
                        </div>
                        <?php
                        if ($collapse) {
                            echo '<div class="pagoL-btn-text pagoL-collapse-btn"><i class="pagoL-icon-arrow-down"></i>' . pagoL_('All cryptocurrencies') . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="pagoL-pay-cnt pagoL-box">
            <div class="pagoL-top">
                <div class="pagoL-pay-top-main">
                    <div class="pagoL-title">
                        <?php pagoL_e(pagoL_settings_get('form-payment-title', 'Send payment')) ?>
                        <div class="pagoL-flex">
                            <div class="pagoL-countdown pagoL-toolip-cnt">
                                <div data-countdown="<?php pagoL_settings_get('refresh-interval', 60) ?>"></div>
                                <span class="pagoL-toolip">
                                    <?php pagoL_e('Checkout timeout') ?>
                                </span>
                            </div>
                            <div class="pagoL-btn pagoL-btn-border pagoL-back">
                                <i class="pagoL-icon-back"></i>
                                <?php pagoL_e('Back') ?>
                            </div>
                        </div>
                    </div>
                    <?php echo '<div class="pagoL-text">' . pagoL_render_text(pagoL_settings_get('form-payment-description', '')) . '</div>' ?>
                </div>
                <div class="pagoL-pay-top-back">
                    <div class="pagoL-title">
                        <?php pagoL_e('Are you sure?') ?>
                    </div>
                    <div class="pagoL-text">
                        <?php pagoL_e('This transaction will be cancelled. If you already sent the payment please wait.') ?>
                    </div>
                    <div id="pagoL-confirm-cancel" class="pagoL-btn pagoL-btn-border pagoL-btn-red">
                        <?php pagoL_e('Yes, I\'m sure') ?>
                    </div>
                    <div id="pagoL-abort-cancel" class="pagoL-btn pagoL-btn-border pagoL-back">
                        <?php pagoL_e('Cancel') ?>
                    </div>
                </div>
            </div>
            <div class="pagoL-body">
                <div class="pagoL-flex">
                    <?php
                    if (!pagoL_settings_get('disable-qrcode')) {
                        echo '<a class="pagoL-qrcode-link pagoL-toolip-cnt"><img class="pagoL-qrcode" src="" alt="QR code" /><span class="pagoL-toolip">' . pagoL_('Open in wallet') . '</span></a>';
                    }
                    ?>
                    <div class="pagoL-flex pagoL-qrcode-text">
                        <img src="" alt="" />
                        <div class="pagoL-text"></div>
                    </div>
                </div>
                <div class="pagoL-flex pagoL-pay-address">
                    <div>
                        <div class="pagoL-text"></div>
                        <div class="pagoL-title"></div>
                    </div>
                    <i class="pagoL-icon-copy pagoL-clipboard pagoL-toolip-cnt">
                        <span class="pagoL-toolip">
                            <?php pagoL_e('Copy to clipboard') ?>
                        </span>
                    </i>
                </div>
                <div class="pagoL-flex pagoL-pay-amount">
                    <div>
                        <div class="pagoL-text">
                            <?php pagoL_e('Total amount') ?>
                        </div>
                        <div class="pagoL-title"></div>
                    </div>
                    <div class="pagoL-flex">
                        <?php
                        if (pagoL_settings_get('metamask')) {
                            echo '<div id="metamask" class="pagoL-btn pagoL-btn-img pagoL-hidden"><img src="' . PAGOL_URL . 'media/metamask.svg" alt="MetaMask" />MetaMask</div>';
                        }
                        ?>
                        <i class="pagoL-icon-copy pagoL-clipboard pagoL-toolip-cnt">
                            <span class="pagoL-toolip">
                                <?php pagoL_e('Copy to clipboard') ?>
                            </span>
                        </i>
                    </div>
                </div>
            </div>
        </div>
        <div class="pagoL-tx-cnt pagoL-box">
            <div class="pagoL-loading"></div>
            <div class="pagoL-title">
                <?php pagoL_e('Payment received') ?>
            </div>
            <div class="pagoL-flex">
                <div class="pagoL-tx-status"></div>
                <div class="pagoL-tx-confirmations">
                    <span></span>
                    /
                </div>
                <div>
                    <?php pagoL_e('confirmations') ?>
                </div>
            </div>
        </div>
        <div class="pagoL-complete-cnt pagoL-box">
            <i class="pagoL-icon-check"></i>
            <div class="pagoL-title">
                <?php pagoL_e(pagoL_settings_get('success-title', 'Payment completed')) ?>
            </div>
            <div class="pagoL-text">
                <span>
                    <?php echo pagoL_render_text(pagoL_settings_get('success-description', 'Thank you for your payment')) ?>
                </span>
                <span>
                    <?php echo pagoL_render_text(pagoL_settings_get('order-processing-text', 'We are processing the order, please wait...')) ?>
                </span>
            </div>
        </div>
        <div class="pagoL-failed-cnt pagoL-box">
            <i class="pagoL-icon-close"></i>
            <div class="pagoL-title">
                <?php pagoL_e(pagoL_settings_get('failed-title', 'No payment')) ?>
            </div>
            <div class="pagoL-text">
                <?php echo pagoL_render_text(pagoL_settings_get('failed-text', 'We didn\'t detect a payment. If you have already paid, please contact us.')) ?>
            </div>
            <div class="pagoL-text">
                <?php pagoL_e('Your transaction ID is:') ?>
                <span id="pagoL-expired-tx-id"></span>
            </div>
            <div class="pagoL-btn pagoL-btn-border ">
                <?php pagoL_e('Retry') ?>
            </div>
        </div>
        <div class="pagoL-underpayment-cnt pagoL-box">
            <i class="pagoL-icon-close"></i>
            <div class="pagoL-title">
                <?php pagoL_e(pagoL_settings_get('underpayment-title', 'Underpayment')) ?>
            </div>
            <div class="pagoL-text">
                <?php echo pagoL_render_text(pagoL_settings_get('underpayment-description', 'We have detected your payment but the amount is less than requested and the transaction cannot be completed, please contact us.')) ?>
                <?php pagoL_e('Your transaction ID is:') ?><span id="pagoL-underpaid-tx-id"></span>
            </div>
        </div>
        <?php
        if (PAGOL_CLOUD) {
            echo '<a href="' . CLOUD_POWERED_BY[0] . '" target="_blank" class="pagoL-cloud-branding" style="display:flex !important;"><span style="display:block !important;">Powered by</span><img style="display:block !important;" src="' . CLOUD_POWERED_BY[1] . '" alt="" /></a>';
        }
        ?>
    </div>
<?php } ?>