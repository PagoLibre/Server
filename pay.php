<?php

/*
 * ==========================================================
 * PAY.PHP
 * ==========================================================
 *
 * Payment page
 *
 */

if (!file_exists(__DIR__ . '/config.php')) {
    die();
}
require_once(__DIR__ . '/functions.php');
if (PAGOL_CLOUD) {
    pagoL_cloud_load();
}
$logo = pagoL_settings_get('logo-pay');
$minify = isset($_GET['debug']) ? false : (PAGOL_CLOUD || pagoL_settings_get('minify'));
$invoice_form = false;
$invoice_get = pagoL_sanatize_string(pagoL_isset($_GET, 'invoice'));
if ($invoice_get) {
    $transaction_id = pagoL_encryption($invoice_get, false);
    if ($transaction_id) {
        if (isset($_GET['download'])) {
            $invoice = pagoL_transactions_invoice($transaction_id);
            die($invoice ? '<script>document.location = "' . $invoice . '"</script>' : 'Transaction not found or not confirmed.');
        }
        $transaction = pagoL_transactions_get($transaction_id);
        if ($transaction) {
            if ($transaction['status'] != 'P') {
                $invoice_form = '<div class="pagoL-main pagoL-billing-page">' . pagoL_transactions_invoice_form(true) . '<div id="pagoL-generate-invoice" class="pagoL-btn">' . pagoL_('View Invoice') . '</div></div>';
                $invoice_form .= '<style>.pagoL-main { text-align: center; } .pagoL-main > #pagoL-generate-invoice.pagoL-btn { margin-top: 30px !important; }</style>';
                $invoice_form .= '<script>
                             (function () {
                                 let _ = window._query;
                                 let billing = ' . ($transaction['billing'] ? $transaction['billing'] : '_.storage(\'pagoL-billing\')') . ';
                                 _(\'body\').on(\'click\', \'#pagoL-generate-invoice\', function () {
                                     let billing_details = PAGOLibre.checkout.getBillingDetails(_(\'#pagoL-billing\'));
                                     if (billing_details) {
                                        PAGOLibre.ajax(\'invoice-user\', {
                                            encrypted_transaction_id: "' . $invoice_get . '",
                                            billing_details: billing_details
                                        }, (response) => {
                                            if (response && response.includes(\'http\')) {
                                                _.storage(\'pagoL-billing\', billing_details);
                                                window.open(response);
                                            } else {
                                                alert(JSON.stringify(response));
                                            }
                                        });
                                     }
                                 });
                                 if (billing) {
                                    for (var key in billing) {
                                        _(\'#pagoL-billing\').find(`#pagoL-billing [name="${key}"]`).val(billing[key]);
                                    }
                                 }
                             }());
                             </script>';
            } else {
                die('Transaction not confirmed.');
            }
        } else {
            die('Transaction not found.');
        }
    } else {
        die('Transaction ID not found.');
    }
}
if (defined('PAGOL_SHOP')) {
    if (isset($_GET['downloads'])) {
        pagoL_shop_downloads(pagoL_sanatize_string($_GET['downloads']));
    }
}

$code_transaction = '';
if (isset($_GET['id']) && !isset($_GET['demo'])) {
    $transaction = pagoL_transactions_get(pagoL_encryption(pagoL_sanatize_string($_GET['id']), false));
    $fiat_redirect = '';
    if (!$transaction) {
        die('Transaction not found.');
    }
    if ($transaction['status'] != 'P') {
        die('Transaction not in pending status.');
    }
    if (pagoL_crypto_is_fiat($transaction['cryptocurrency'])) {
        $fiat_redirect = 'document.location = "' . pagoL_checkout_url($transaction['amount_fiat'], $transaction['cryptocurrency'], $transaction['currency'], PAGOL_URL . 'pay.php?id=' . pagoL_sanatize_string($_GET['id']), $transaction['id'], $transaction['title']) . '";';
    }
    $_GET['checkout_id'] = 'custom-pay-page';
    $code_transaction = '<script>PAGOLibre.checkout.storageTransaction("custom-pay-page", "delete"); PAGOLibre.checkout.storageTransaction("custom-pay-page", { id: ' . $transaction['id'] . ', amount: "' . $transaction['amount'] . '", to: "' . $transaction['to'] . '", cryptocurrency: "' . $transaction['cryptocurrency'] . '", external_reference: "' . $transaction['external_reference'] . '", vat: "' . $transaction['vat'] . '", encrypted: "' . pagoL_encryption($transaction) . '", min_confirmations: ' . pagoL_settings_get_confirmations($transaction['cryptocurrency'], $transaction['amount']) . ', prevent_cancel: true });' . $fiat_redirect . '</script>';
}
if (pagoL_settings_get('css-pay')) {
    $code_transaction .= PHP_EOL . '<link rel="stylesheet" href="' . pagoL_settings_get('css-pay') . '?v=' . PAGOL_VERSION . '" media="all" />';
}
$favicon = PAGOL_CLOUD ? CLOUD_ICON : ($logo ? pagoL_settings_get('logo-icon-url', PAGOL_URL . 'media/icon.svg') : PAGOL_URL . 'media/icon.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <title>
        <?php pagoL_e(pagoL_settings_get('form-title', 'Payment method')) ?>
    </title>
    <?php
    if (isset($_GET['lang'])) {
        echo '<script>var PAGOL_LANGUAGE = "' . substr(pagoL_sanatize_string($_GET['lang']), 0, 2) . '";</script>';
    }
    ?>
    <link rel="shortcut icon" type="image/<?php strpos($favicon, '.png') ? 'png' : 'svg' ?>" href="<?php echo $favicon ?>" />
    <script id="pagolibre" src="<?php echo PAGOL_URL . 'js/client' . ($minify ? '.min' : '') ?>.js?v=<?php echo PAGOL_VERSION ?>"></script>
    <?php
    if (PAGOL_CLOUD) {
        pagoL_cloud_front();
    }
    echo $code_transaction;
    ?>
    <style>
        body {
            text-align: center;
            padding: 100px 0;
        }

        .pagoL-main {
            text-align: left;
            margin: auto;
        }

        .pagoL-pay-logo {
            text-align: center;
        }

        .pagoL-pay-logo img {
            margin: 0 auto 30px auto;
            max-width: 200px;
        }
    </style>
</head>
<body style="display: none">
    <script>(function () { setTimeout(() => { document.body.style.removeProperty('display') }, 500) }())</script>
    <?php
    if ($logo) {
        echo '<div class="pagoL-pay-logo"><img src="' . pagoL_settings_get('logo-url') . '" alt="" /></div>';
    }
    if ($invoice_form) {
        echo $invoice_form;
    } else {
        pagoL_checkout_direct();
        echo pagoL_settings_get('pay-text');
    }
    ?>
</body>
</html>