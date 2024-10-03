<?php

/*
 * ==========================================================
 * BITCOIN.PHP
 * ==========================================================
 *
 * � 2022-2024 PagoLibre. All rights reserved.
 *
 */

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;
use Kielabokkie\Bitcoin\AddressValidator;

/*
 * -----------------------------------------------------------
 * BITCOIN CORE
 * -----------------------------------------------------------
 *
 * 1. Loads the required libraries
 * 2. Generates a Bitcoin address from a node
 * 3. Generates a Bitcoin address from a node with an xpub key
 * 4. Generates a Bitcoin address via code with an xpub key
 * 5. Makes a Bitcoin transfer
 * 6. Get the unspent transaction outputs of a transaction
 * 7. Bitcoin node REST API call function
 * 8. Validate an address
 *
 */

function pagoL_btc_load() {
    require(__DIR__ . '/vendor/bitcoin/composer/autoload_real.php');
    ComposerAutoloaderInit9bdfd86aa6c5dea69b9fac2a253fcf91::getLoader();
}

function pagoL_btc_generate_address() {
    pagoL_btc_load();
    $addrReader = new AddressCreator();
    $privFactory = new PrivateKeyFactory();
    $priv = $privFactory->generateCompressed(new Random());
    $publicKey = $priv->getPublicKey();
    $helper = new P2pkhScriptDataFactory();
    $scriptData = $helper->convertKey($publicKey);
    $p2pkh = $scriptData->getAddress($addrReader);
    return ['address' => $p2pkh->getAddress(), 'private_key' => $priv->toWif()];
}

function pagoL_btc_generate_address_xpub_node($xpub = false, $range = [0, 99]) {
    if (!$xpub) {
        $xpub = trim(pagoL_settings_get('btc-node-xpub'));
    }
    if (!$xpub) {
        return pagoL_error('Xpub not found.', 'pagoL_btc_generate_address');
    }
    $response = pagoL_btc_curl('getdescriptorinfo', ['wpkh(' . $xpub . '/0/*)']);
    if ($response && empty($response['error']) && isset($response['descriptor'])) {
        return ['address' => pagoL_btc_curl('deriveaddresses', [$response['descriptor'], $range])];
    }
    pagoL_error($response, 'pagoL_btc_generate_address_xpub');
    return $response;
}

function pagoL_btc_generate_address_xpub($xpub = false, $path = '0/0') {
    pagoL_btc_load();
    if (!$xpub)
        $xpub = pagoL_settings_get('btc-node-xpub');
    try {
        $pubkeytype = substr($xpub, 0, 4);
        $bitcoin_prefixes = new BitcoinRegistry();
        $adapter = Bitcoin::getEcAdapter();
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        if ($pubkeytype == 'xpub') {
            $pubPrefix = $slip132->p2pkh($bitcoin_prefixes);
        }
        if ($pubkeytype == 'ypub') {
            $pubPrefix = $slip132->p2shP2wpkh($bitcoin_prefixes);
        }
        if ($pubkeytype == 'zpub') {
            $pubPrefix = $slip132->p2wpkh($bitcoin_prefixes);
        }
        if (is_array($path)) {
            $path = '0/' . $path[0];
        }
        $config = new GlobalPrefixConfig([new NetworkConfig(NetworkFactory::bitcoin(), [$pubPrefix])]);
        $serializer = new Base58ExtendedKeySerializer(new ExtendedKeySerializer($adapter, $config));
        $key = $serializer->parse(NetworkFactory::bitcoin(), $xpub);
        $child_key = $key->derivePath($path);
        $address = $child_key->getAddress(new AddressCreator())->getAddress();
        return $address ? ['address' => $address] : false;
    } catch (Exception $e) {
        pagoL_error($e->getMessage(), 'pagoL_btc_generate_address');
        return false;
    }
}

function pagoL_btc_transfer($amount, $to = false, $from = false, $wallet_key = false) {
    $response = false;
    $params = [[], []];
    $utxo_amount = 0;
    $fee = pagoL_isset(pagoL_btc_curl('estimatesmartfee', [5]), 'feerate', 0.00015) / 4;
    $amount_plus_fee = $amount + $fee;
    if (!$from) {
        $from = pagoL_settings_get_address('btc');
    }
    if (!$to) {
        $to = pagoL_settings_get('btc-node-transfer-address');
    }
    if (!$wallet_key) {
        $wallet_key = pagoL_encryption(pagoL_settings_get('btc-wallet-key'), false);
    }
    $to = trim($to);
    if (pagoL_crypto_whitelist_invalid($to, false, 'btc')) {
        return 'whitelist-invalid';
    }
    $utxo = pagoL_btc_get_utxo($from);
    if (is_string($utxo) || isset($utxo['error'])) {
        pagoL_error($utxo, 'pagoL_btc_transfer');
        return $utxo;
    }
    for ($i = 0; $i < count($utxo); $i++) {
        if ($utxo_amount < $amount_plus_fee) {
            array_push($params[0], ['txid' => $utxo[$i]['txid'], 'vout' => $utxo[$i]['n']]);
            $utxo_amount += $utxo[$i]['value'];
        } else
            break;
    }
    if ($utxo_amount < $amount_plus_fee) {
        $amount = $utxo_amount - $fee;
        $amount_plus_fee = $amount + $fee;
    }
    $param_1 = [];
    $param_1[$to] = pagoL_crypto_get_value_with_decimals(pagoL_decimal_number($amount), 'btc');
    array_push($params[1], $param_1);
    $param_1 = [];
    $param_1[$from] = pagoL_crypto_get_value_with_decimals(pagoL_decimal_number($utxo_amount - $amount_plus_fee), 'btc');
    if ($param_1[$from]) {
        array_push($params[1], $param_1);
    }
    $response = pagoL_btc_curl('createrawtransaction', $params);
    if (empty($response['error'])) {
        $response = pagoL_btc_curl('signrawtransactionwithkey', [$response, [$wallet_key]]); //[$wallet_key, $wallet_key2] if utxo from different keys
        if (empty($response['error']) && isset($response['hex'])) {
            return pagoL_btc_curl('sendrawtransaction', [$response['hex']]);
        }
    }
    pagoL_error($response, 'pagoL_btc_transfer');
    return $response;
}

function pagoL_btc_get_utxo($address = false, $transaction_hashes = false) {
    if (!$address) {
        $address = pagoL_settings_get_address('btc');
    }
    $address_lowercase = strtolower($address);
    $transactions = json_decode(pagoL_settings_db('btc-transactions-' . $address_lowercase, false, '[]'), true);
    $save = false;
    $unspent_outputs = [];
    if (!$transaction_hashes) {
        $transaction_hashes = [];
        $transactions_blockchain = pagoL_blockchain('btc', 'transactions', false, $address);
        if (is_string($transactions_blockchain)) {
            return pagoL_error($transactions_blockchain, 'pagoL_btc_get_utxo');
        }
        for ($i = 0; $i < count($transactions_blockchain); $i++) {
            array_push($transaction_hashes, $transactions_blockchain[$i]['hash']);
        }
    }
    for ($i = 0; $i < count($transaction_hashes); $i++) {
        $id = $transaction_hashes[$i];
        if (!isset($transactions[$id])) {
            $response = pagoL_btc_curl('getrawtransaction', [$id, true]);
            if (isset($response['txid'])) {
                $transactions[$id] = $response;
                $save = true;
            } else {
                pagoL_error($response, 'pagoL_btc_get_unspent');
                return $response;
            }
        }
    }
    foreach ($transactions as $id => $transaction) {
        $outputs = pagoL_isset($transaction, 'vout', []);
        $transaction_id = $transaction['txid'];
        for ($i = 0; $i < count($outputs); $i++) {
            $script_pub_key = pagoL_isset($outputs[$i], 'scriptPubKey', []);
            if (pagoL_isset($script_pub_key, 'address') === $address || pagoL_isset($script_pub_key, 'addresses', [''])[0] === $address) {
                $output_number = $outputs[$i]['n'];
                $spent = false;
                foreach ($transactions as $transaction_2) {
                    $inputs = pagoL_isset($transaction_2, 'vin', []);
                    for ($y = 0; $y < count($inputs); $y++) {
                        if ($transaction_2['txid'] != $transaction_id && $inputs[$y]['txid'] === $transaction_id && $inputs[$y]['vout'] === $output_number) {
                            $spent = true;
                            break;
                        }
                    }
                    if ($spent)
                        break;
                }
                if (!$spent) {
                    $outputs[$i]['txid'] = $transaction_id;
                    $outputs[$i]['value'] = pagoL_decimal_number($outputs[$i]['value']);
                    array_push($unspent_outputs, $outputs[$i]);
                }
            }
        }
    }
    if ($save) {
        pagoL_settings_db('btc-transactions-' . $address_lowercase, $transactions);
    }
    return $unspent_outputs;
}

function pagoL_btc_curl($method, $params = []) {
    return pagoL_node_rpc('btc', $method, $params);
}

function pagoL_btc_validate_address($address) {
    require(__DIR__ . '/vendor/bitcoin/composer_2/autoload_real.php');
    ComposerAutoloaderInit5d597655eb7f7a3802cc6d0e1a91ebd1::getLoader();
    $addressValidator = new AddressValidator;
    return $addressValidator->isValid(trim($address));
}

/*
 * -----------------------------------------------------------
 * LIGHTING NETWORK
 * -----------------------------------------------------------
 *
 * 1. Lighitng node REST API call function
 * 2. Create an invoice
 * 3. Return the details of an invoice
 *
 */

function pagoL_btc_ln_curl($url_part, $body = '', $type = 'POST') {
    $node_url = pagoL_settings_get('ln-node-url');
    $node_headers = pagoL_settings_get('btc-ln-headers', []);
    $macaroon = pagoL_encryption(pagoL_settings_get('ln-macaroon'), false);
    if (!$node_url) {
        pagoL_error('Bitcoin LN node URL not found', 'pagoL_btc_ln_curl', true);
    }
    if (!$macaroon) {
        pagoL_error('Bitcoin LN macaroon not found', 'pagoL_btc_ln_curl', true);
    }
    if (!strpos($node_url, 'https://')) {
        $node_url = 'https://' . $node_url;
    }
    if (!strpos($node_url, '8080')) {
        $node_url = $node_url . ':8080';
    }
    if (substr($node_url, -1) !== '/') {
        $node_url .= '/';
    }
    if ($node_headers) {
        $node_headers = explode(',', $node_headers);
    }
    array_push($node_headers, 'Grpc-Metadata-macaroon: ' . $macaroon);
    $response_json = pagoL_curl($node_url . $url_part, json_encode($body), array_merge(['accept: application/json', 'content-type: application/json'], $node_headers), $type);
    $response = json_decode($response_json, true);
    if (!$response) {
        pagoL_error($response_json, 'pagoL_btc_ln_curl');
    }
    return $response;
}

function pagoL_btc_ln_create_invoice($amount) {
    $response = pagoL_btc_ln_curl('v1/balance/channels', '', 'GET');
    $satoshi = pagoL_decimal_number(intval(floatval($amount) * 100000000));
    $balance = pagoL_isset($response, 'remote_balance');
    if ($balance) {
        $response = intval($balance['sat']) >= $satoshi ? pagoL_btc_ln_curl('v1/invoices', ['value' => $satoshi], 'POST') : ['error' => 'Insufficient remote balance.'];
        if (isset($response['payment_request'])) {
            return $response;
        }
    }
    if (pagoL_settings_get('notifications-ln')) {
        pagoL_email_notification(pagoL_m('Lightning Network error', pagoL_settings_get('language-admin')), json_encode($response));
    }
    pagoL_error($response, 'pagoL_btc_ln_create_invoice');
    return ['error' => $response];
}

function pagoL_btc_ln_get_invoice($r_hash) {
    return pagoL_btc_ln_curl('v2/invoices/lookup/' . bin2hex(base64_decode($r_hash)), '', 'GET');
}

?>