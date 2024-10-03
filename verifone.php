<?php

/*
 * ==========================================================
 * VERIFONE.PHP
 * ==========================================================
 *
 * Process 2Checkout Verifone payments
 *
 */

header('Content-Type: application/json');
$raw = file_get_contents('php://input');

if ($raw) {
    require('functions.php');
    $response = [];
    $raws = explode('&', urldecode($raw));
    for ($i = 0; $i < count($raws); $i++) {
        $value = explode('=', $raws[$i]);
        $response[$value[0]] = str_replace('\/', '/', $value[1]);
    }
    if (pagoL_isset($response, 'message_type') == 'ORDER_CREATED' && pagoL_isset($response, 'invoice_status') == 'approved') {
        if (PAGOL_CLOUD) {
            if (isset($_GET['cloud'])) {
                pagoL_cloud_load();
                pagoL_cloud_spend_credit($response['invoice_list_amount'], $response['list_currency']);
            } else {
                die();
            }
        }
        $external_reference = explode('|||', pagoL_encryption($response['vendor_order_id'], false));
        if (is_array($external_reference) && count($external_reference) > 1 && $external_reference[1] == pagoL_settings_get('verifone-key')) {
            $transaction = pagoL_transactions_get($external_reference[0]);
            $invoice = pagoL_isset($transaction, 'billing') && pagoL_settings_get('invoice-active') ? pagoL_transactions_invoice($transaction) : false;
            pagoL_transactions_complete($transaction, $response['invoice_list_amount'], $response['order_ref'], $invoice);
        }
    }
}

function pagoL_array_expand($array) {
    $retval = '';
    foreach ($array as $i => $value) {
        if (is_array($value)) {
            $retval .= pagoL_array_expand($value);
        } else {
            $size = strlen($value);
            $retval .= $size . $value;
        }
    }
    return $retval;
}

function pagoL_hmac($key, $data) {
    $b = 64;
    if (strlen($key) > $b)
        $key = pack('H*', md5($key));
    $key = str_pad($key, $b, chr(0x00));
    $ipad = str_pad('', $b, chr(0x36));
    $opad = str_pad('', $b, chr(0x5c));
    $k_ipad = $key ^ $ipad;
    $k_opad = $key ^ $opad;
    return md5($k_opad . pack('H*', md5($k_ipad . $data)));
}

?>