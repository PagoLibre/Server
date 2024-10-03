<?php

/*
 * ==========================================================
 * API.PHP
 * ==========================================================
 *
 * API main file. This file listens the POST queries and return the result. � 2022-2024 PagoLibre. All rights reserved.
 *
 */

require('functions.php');

if (isset($_GET['cron']) && isset($_GET['api-key'])) {
    $_POST['api-key'] = pagoL_sanatize_string($_GET['api-key']);
    pagoL_api_verify_key();
    pagoL_cron();
    die();
}
if (!isset($_POST['function'])) {
    pagoL_api_error('Function name is required.', 'missing-function-name');
}
define('PAGOL_API', true);
pagoL_process_api();

function pagoL_process_api() {
    $function_name = $_POST['function'];
    $functions = [
        'get-balances' => [],
        'get-settings' => [],
        'save-settings' => ['settings'],
        'get-transactions' => [],
        'get-transaction' => ['transaction_id'],
        'download-transactions' => [],
        'check-transaction' => ['transaction'],
        'check-transactions' => ['transaction_id'],
        'update-transaction' => ['transaction_id', 'values'],
        'create-transaction' => ['amount', 'cryptocurrency_code'],
        'delete-transaction' => ['transaction_id'],
        'get-checkouts' => [],
        'save-checkout' => ['checkout'],
        'delete-checkout' => ['checkout_id'],
        'get-fiat-value' => ['amount', 'cryptocurrency_code', 'currency_code'],
        'cron' => [],
        'invoice' => ['transaction_id'],
        'update' => ['domain'],
        'vat' => ['amount'],
        'vat-validation' => ['vat_number'],
        'get-cryptocurrency-codes' => ['cryptocurrency_code'],
        'payment-link' => ['transaction_id'],
        'get-custom-tokens' => [],
        'settings-get-address' => ['cryptocurrency_code'],
        'eth-curl' => ['method'],
        'eth-transfer' => ['amount'],
        'eth-swap' => ['amount', 'cryptocurrency_code_from'],
        'eth-get-contract' => [],
        'eth-wait-confirmation' => ['hash'],
        'eth-get-transactions-after-timestamp' => ['timestamp'],
        'eth-generate-address' => [],
        'eth-get-balance' => [],
        'btc-curl' => ['method'],
        'btc-transfer' => ['amount'],
        'btc-generate-address' => [],
        'btc-generate-address-xpub' => ['xpub'],
        'btc-get-utxo' => [],
        'refund' => ['transaction_id'],
        'get-exchange-rates' => ['currency_code', 'cryptocurrency_code'],
        'get-usd-rates' => ['currency_code'],
        'validate-license-key' => ['license_key'],
        'encryption' => ['value']
    ];

    // Errors check
    if (!isset($functions[$function_name])) {
        pagoL_api_error('Function ' . $function_name . ' not found.', 'function-not-found');
    }
    pagoL_api_verify_key();
    if (count($functions[$function_name]) > 0) {
        for ($i = 0; $i < count($functions[$function_name]); $i++) {
            if (!isset($_POST[$functions[$function_name][$i]])) {
                pagoL_api_error('Missing argument: ' . $functions[$function_name][$i], 'missing-argument');
            }
        }
    }

    // Convert JSON to array
    $json_keys = [];
    switch ($function_name) {
        case 'save-settings':
            $json_keys = ['settings'];
            break;
        case 'btc-curl':
        case 'eth-curl':
            $json_keys = ['params'];
            break;
    }
    for ($i = 0; $i < count($json_keys); $i++) {
        if (isset($_POST[$json_keys[$i]])) {
            $_POST[$json_keys[$i]] = json_decode($_POST[$json_keys[$i]], true);
        }
    }

    require_once('ajax.php');
}

function pagoL_api_error($message, $code) {
    die(json_encode(['success' => false, 'error_code' => $code, 'message' => $message]));
}

function sb_api_success($response) {
    die(json_encode(['success' => true, 'response' => $response]));
}

function pagoL_api_verify_key() {
    if (empty($_POST['api-key'])) {
        pagoL_api_error('API key is required. ' . (PAGOL_CLOUD ? 'Get it from Settings > Account.' : 'Set it from Settings > API key.'), 'api-key-not-found');
    }
    if (PAGOL_CLOUD) {
        require(__DIR__ . '/cloud/functions.php');
        pagoL_cloud_api_load_by_url();
    } else {
        if ($_POST['api-key'] !== pagoL_settings_get('api-key')) {
            pagoL_api_error('Invalid API key. Set it from Settings > API key.', 'invalid-api-key');
        } else {
            $GLOBALS['PAGOL_LOGIN'] = [PAGOL_USER, 'auto'];
        }
    }
}

?>