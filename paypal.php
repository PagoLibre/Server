<?php

/*
 * ==========================================================
 * PAYPAL.PHP
 * ==========================================================
 *
 * Process PayPal payments
 *
 */

header('Content-Type: application/json');

if (isset($_POST['txn_id'])) {
    require('functions.php');
    if (pagoL_paypal_verify_transaction($_POST)) {
        if (in_array($_POST['payment_status'], ['Pending', 'Completed'])) {
            $custom = explode('|', $_POST['custom']);
            if (PAGOL_CLOUD) {
                if (isset($custom[1])) {
                    $_POST['cloud'] = $custom[1];
                    pagoL_cloud_load();
                    pagoL_cloud_spend_credit($_POST['payment_gross'], $_POST['mc_currency']);
                } else
                    die();
            }
            $description = pagoL_transactions_get_description($transaction_id);
            $transaction = pagoL_transactions_get($custom[0]);
            $invoice = pagoL_isset($transaction, 'billing') && pagoL_settings_get('invoice-active') ? pagoL_transactions_invoice($transaction) : false;
            array_push($description, pagoL_('PayPal user details: ') . '#' . $_POST['payer_id'] . ' ' . $_POST['payer_email']);
            pagoL_transactions_complete($transaction, pagoL_isset($_POST, 'payment_gross', $_POST['mc_gross']), $_POST['payer_id'], $invoice);
            pagoL_transactions_update($custom[0], ['description' => $description]);
        } else {
            pagoL_error('Bad PayPal payment status: ' . $_POST['payment_status'], 'paypal.php', true);
        }
    } else {
        pagoL_error('Bad PayPal signature. Details: ' . json_encode($_POST), 'paypal.php', true);
    }
    die();
}

function pagoL_paypal_verify_transaction($data) {
    $req = 'cmd=_notify-validate';
    foreach ($data as $key => $value) {
        $value = urlencode(stripslashes($value));
        $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value); // IPN fix
        $req .= "&$key=$value";
    }
    $ch = curl_init('https://www.' . (pagoL_settings_get('paypal-sandbox') ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
    $res = curl_exec($ch);
    if (!$res) {
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: [$errno] $errstr");
    }
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $info['http_code'] == 200;
}

?>