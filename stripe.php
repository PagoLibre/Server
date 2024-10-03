<?php

/*
 * ==========================================================
 * STRIPE.PHP
 * ==========================================================
 *
 * Process Stripe payments
 *
 */

header('Content-Type: application/json');
$raw = file_get_contents('php://input');
$response = json_decode($raw, true);

if ($response && empty($response['error']) && !empty($response['id'])) {
    require('functions.php');
    $metadata = pagoL_isset($response['data']['object'], 'metadata');
    if ($metadata && isset($metadata['source']) && $metadata['source'] === 'pagolibre') {
        if (PAGOL_CLOUD) {
            if (isset($metadata['cloud'])) {
                $_POST['cloud'] = $metadata['cloud'];
                pagoL_cloud_load();
            } else {
                die();
            }
        }
        $response = pagoL_stripe_curl('events/' . $response['id'], 'GET');
        $data = $response['data']['object'];
        switch ($response['type']) {
            case 'checkout.session.completed':
                $transaction = pagoL_transactions_get($data['client_reference_id']);
                if ($transaction) {
                    pagoL_transactions_complete($transaction, $data['amount_total'] / pagoL_stripe_get_divider($transaction['currency']), $data['customer']);
                    if (PAGOL_CLOUD) {
                        pagoL_cloud_spend_credit($data['amount_total'] / pagoL_stripe_get_divider($transaction['currency']), $transaction['currency']);
                    }
                }
                break;
        }
    }
}

?>