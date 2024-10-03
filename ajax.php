<?php

/*
 * ==========================================================
 * AJAX.PHP
 * ==========================================================
 *
 * AJAX functions. This file must be executed only via AJAX. � 2022 PagoLibre. All rights reserved.
 *
 */

if (!isset($_POST['function'])) {
    if (!isset($_POST['data']))
        die();
    $_POST = json_decode($_POST['data'], true);
    if (!isset($_POST['function']))
        die();
}
require_once('functions.php');
pagoL_cloud_load();
if (pagoL_security_error()) {
    die(pagoL_json_response('Security error', false));
}

switch ($_POST['function']) {
    case 'installation':
        die(pagoL_json_response(pagoL_installation($_POST['installation_data'])));
    case 'login':
        die(pagoL_json_response(pagoL_login($_POST['username'], $_POST['password'], pagoL_post('code'))));
    case 'get-balances':
        die(pagoL_json_response(pagoL_crypto_balances()));
    case 'get-settings':
        die(pagoL_json_response(pagoL_settings_get_all()));
    case 'save-settings':
        die(pagoL_json_response(pagoL_settings_save($_POST['settings'])));
    case 'get-transactions':
        die(pagoL_json_response(pagoL_transactions_get_all(pagoL_post('pagination', 0), pagoL_post('search'), pagoL_post('status'), pagoL_post('cryptocurrency'), pagoL_post('date_range'), pagoL_post('checkout_id'))));
    case 'get-transaction':
        die(pagoL_json_response(pagoL_transactions_get($_POST['transaction_id'])));
    case 'download-transactions':
        die(pagoL_json_response(pagoL_transactions_download(pagoL_post('search'), pagoL_post('status'), pagoL_post('cryptocurrency'), pagoL_post('date_range'))));
    case 'check-transaction':
        die(pagoL_json_response(pagoL_transactions_check_single($_POST['transaction'])));
    case 'check-transactions':
        die(pagoL_json_response(pagoL_transactions_check($_POST['transaction_id'])));
    case 'update-transaction':
        die(pagoL_json_response(pagoL_transactions_update($_POST['transaction_id'], $_POST['values'])));
    case 'create-transaction':
        die(pagoL_json_response(pagoL_transactions_create($_POST['amount'], $_POST['cryptocurrency_code'], pagoL_post('currency_code'), pagoL_post('external_reference'), pagoL_post('title'), pagoL_post('note', pagoL_post('description')), pagoL_post('url'), pagoL_post('billing', ''), pagoL_post('vat'), pagoL_post('checkout_id'), pagoL_post('user_details'), pagoL_post('discount_code'), pagoL_post('type', 1)))); // temp rimuovo pagoL_post('description')
    case 'cancel-transaction':
        die(pagoL_json_response(pagoL_transactions_cancel($_POST['transaction'])));
    case 'delete-transaction':
        die(pagoL_json_response(pagoL_transactions_delete($_POST['transaction_id'])));
    case 'get-checkouts':
        die(pagoL_json_response(pagoL_checkout_get(pagoL_post('checkout_id', 0))));
    case 'save-checkout':
        die(pagoL_json_response(pagoL_checkout_save($_POST['checkout'])));
    case 'delete-checkout':
        die(pagoL_json_response(pagoL_checkout_delete($_POST['checkout_id'])));
    case 'get-fiat-value':
        die(pagoL_json_response(pagoL_crypto_get_fiat_value($_POST['amount'], $_POST['cryptocurrency_code'], $_POST['currency_code'])));
    case 'cron':
        die(pagoL_json_response(pagoL_cron()));
    case 'invoice':
        die(pagoL_json_response(pagoL_transactions_invoice($_POST['transaction_id'])));
    case 'invoice-user':
        die(pagoL_json_response(pagoL_transactions_invoice_user($_POST['encrypted_transaction_id'], pagoL_post('billing_details'))));
    case 'update':
        die(pagoL_json_response(pagoL_update($_POST['domain'])));
    case 'evc':
        die(pagoL_json_response(pagoL_ve($_POST['code'], $_POST['domain'], pagoL_post('a'))));
    case 'vat':
        die(pagoL_json_response(pagoL_vat($_POST['amount'], pagoL_post('country_code'), pagoL_post('currency'), pagoL_post('vat_number'))));
    case 'vat-validation':
        die(pagoL_json_response(pagoL_vat_validation($_POST['vat_number'])));
    case 'email-test':
        die(pagoL_json_response(pagoL_email_notification('This is a test', 'Lorem ipsum dolor sit amet tempor.')));
    case 'get-tokens':
        require_once(__DIR__ . '/web3.php');
        die(pagoL_json_response(pagoL_eth_get_contract()));
    case 'payment-link':
        die(pagoL_json_response(pagoL_payment_link($_POST['transaction_id'])));
    case 'refund':
        die(pagoL_json_response(pagoL_transactions_refund($_POST['transaction_id'])));
    case 'get-exchange-rates':
        die(pagoL_json_response(pagoL_exchange_rates($_POST['currency_code'], $_POST['cryptocurrency_code'])));
    case 'get-usd-rates':
        die(pagoL_json_response(pagoL_usd_rates(pagoL_post('currency_code'))));
    case 'exchange-quotation':
        die(pagoL_json_response(pagoL_exchange_quotation($_POST['send_amount'], $_POST['send_code'], $_POST['get_code'])));
    case 'exchange-is-payment-completed':
        die(pagoL_json_response(pagoL_exchange_is_payment_completed($_POST['external_reference_base64'])));
    case 'exchange-finalize':
        die(pagoL_json_response(pagoL_exchange_finalize($_POST['external_reference_base64'], pagoL_post('identity'), pagoL_post('manual'), pagoL_post('user_payment_details'))));
    case 'exchange-finalize-manual':
        die(pagoL_json_response(pagoL_exchange_finalize_manual($_POST['amount'], $_POST['cryptocurrency_code'], $_POST['currency_code'], $_POST['external_reference'], $_POST['note'], pagoL_post('identity'), pagoL_post('user_payment_details'))));
    case 'email-verification':
        die(pagoL_json_response(pagoL_exchange_email_verification($_POST['email'], pagoL_post('saved_email'), pagoL_post('verification_code'))));
    case 'validate-address':
        die(pagoL_json_response(pagoL_crypto_validate_address($_POST['address'], $_POST['cryptocurrency_code'])));
    case 'complycube':
        die(pagoL_json_response(pagoL_complycube($_POST['first_name'], $_POST['last_name'], $_POST['email'])));
    case 'complycube-create-check':
        die(pagoL_json_response(pagoL_complycube_create_check($_POST['client_id'], $_POST['live_photo_id'], $_POST['document_id'])));
    case 'complycube-check':
        die(pagoL_json_response(pagoL_complycube_check($_POST['check_id'], $_POST['email'])));
    case 'cloud':
        require_once(__DIR__ . '/cloud/functions.php');
        die(pagoL_json_response(pagoL_cloud_ajax($_POST['action'], pagoL_post('arguments'))));
    case 'get-explorer-link':
        die(pagoL_json_response(pagoL_crypto_get_explorer_link($_POST['hash'], $_POST['cryptocurrency_code'])));
    case 'get-network-fee':
        die(pagoL_json_response(pagoL_crypto_get_network_fee($_POST['cryptocurrency_code'], pagoL_post('returned_currency_code'))));
    case 'update-license-key-status':
        die(pagoL_json_response(pagoL_shop_license_key_update_status($_POST['transaction_id'], $_POST['status'])));
    case 'apply-discount':
        die(pagoL_json_response(pagoL_shop_discounts_apply($_POST['discount_code'], $_POST['checkout_id'], $_POST['amount'])));
    case 'validate-license-key':
        die(pagoL_json_response(pagoL_shop_license_key_validate($_POST['license_key'])));
    case 'encryption':
        die(pagoL_json_response(pagoL_encryption($_POST['string'], pagoL_post('encrypt', true))));
    case 'get-customer':
        die(pagoL_json_response(pagoL_customers_get(pagoL_post('customer_id'))));
    case 'analytics':
        die(pagoL_json_response(pagoL_shop_analytics(pagoL_post('status', 'C'), pagoL_post('currency'), pagoL_post('date_range'), pagoL_post('checkout_id'))));
    case 'delete-file':
        die(pagoL_json_response(pagoL_file_delete($_POST['file_name'], pagoL_post('folder'))));
    case 'shop-delete-downloads':
        die(pagoL_json_response(pagoL_shop_downloads_delete($_POST['file_names'])));
    case 'shop-downloads':
        die(pagoL_json_response(pagoL_shop_downloads($_POST['encrypted_transaction_id'], true)));
    case '2fa':
        die(pagoL_json_response(pagoL_2fa(pagoL_post('code'))));
    default:
        die(pagoL_json_response('No function with name "' . $_POST['function'] . '"', false));
}

function pagoL_json_response($response, $success = true) {
    return json_encode(['success' => $success, 'response' => $response]);
}

function pagoL_post($key, $default = false) {
    return isset($_POST[$key]) ? ($_POST[$key] == 'false' ? false : ($_POST[$key] == 'true' ? true : $_POST[$key])) : $default;
}

function pagoL_security_error() {
    $admin_functions = ['delete-transaction', 'delete-file', 'analytics', 'get-customer', 'encryption', 'update-license-key-status', 'refund', 'get-transaction', 'payment-link', 'email-test', 'update-transaction', 'invoice', 'download-transactions', 'get-settings', 'save-settings', 'update', 'get-balances', 'get-transactions', 'get-checkouts', 'save-checkout', 'delete-checkout'];
    $agent_forbidden_functions = ['delete-file', 'save-settings', 'save-checkout', 'delete-checkout'];
    $verify = pagoL_verify_admin();
    return in_array($_POST['function'], $admin_functions) && ($verify === false || ($verify === 'agent' && in_array($_POST['function'], $agent_forbidden_functions)));
}

?>