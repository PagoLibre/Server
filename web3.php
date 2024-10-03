<?php

/*
 * ==========================================================
 * WEB3.PHP
 * ==========================================================
 *
 * � 2022-2024 PagoLibre. All rights reserved.
 *
 */

use Web3\Web3;
use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use kornrunner\Ethereum\Address;
use kornrunner\Keccak;

function pagoL_eth_load() {
    require_once(__DIR__ . '/vendor/web3/composer/autoload_real.php');
    require_once(__DIR__ . '/vendor/web3/composer_2/autoload_real.php');
    ComposerAutoloaderInit05e2d86f8150ca0d0233ceafbbc1f468::getLoader();
    ComposerAutoloaderInit973ba30e2e2a845742c00ce2013c1734::getLoader();
}

function pagoL_eth_swap($amount, $cryptocurrency_code_from, $cryptocurrency_code_to = false, $address = false) {
    pagoL_eth_load();
    if (!$address) {
        $address = pagoL_settings_get_address('eth');
    }
    if (!$cryptocurrency_code_to) {
        $cryptocurrency_code_to = pagoL_settings_get('eth-node-conversion-currency');
    }
    if (pagoL_crypto_whitelist_invalid($address)) {
        return 'whitelist-invalid';
    }
    $address_router = '0x7a250d5630B4cF539739dF2C5dAcb4c659F2488D';
    $node = new Web3(pagoL_settings_get('eth-node-url'));
    $contract = (new Contract($node->provider, json_decode(file_get_contents(__DIR__ . '/vendor/web3/UNIV2Router.json'))->abi))->at($address_router);
    $network = pagoL_settings_get('eth-network', 'mainnet');
    $is_mainnet = $network == 'mainnet';
    $contract_info = pagoL_eth_get_contract($cryptocurrency_code_to, $network);
    $is_eth = $cryptocurrency_code_from === 'eth';
    $path = [$is_mainnet ? '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2' : '0xb4fbf271143f4fbf7b91a5ded31805e42b2208d6', $contract_info[0]];
    if (!$is_eth) {
        array_unshift($path, pagoL_eth_get_contract($cryptocurrency_code_from, $network)[0]);
    }
    $chain_id = pagoL_web3_chain_id($is_mainnet ? 'eth' : 'sepolia');
    $amount = Utils::toWei(strval($amount), $is_eth ? 'ether' : pagoL_eth_decimals_to_name($contract_info[1]));
    $amount_out = '';
    $token = (new Contract($node->provider, json_decode(file_get_contents(__DIR__ . '/vendor/web3/ERC20.json'))->abi))->at($path[0]);
    $gas_price = '0x' . Utils::toWei($is_mainnet ? '25' : '50', 'gwei')->toHex();
    $hash = '';
    if (!$is_eth) {
        $contract->call('getAmountsOut', $amount, $path, ['from' => $address], function ($error, $result) use (&$amount_out) {
            $amount_out = $error ? $error->getMessage() : $result['amounts'][2];
        });
    }
    $data = $token->getData('approve', $address_router, $amount);
    $transaction = new Transaction([
        'nonce' => '0x' . pagoL_eth_nonce($node->eth, $address)->toHex(),
        'gas' => '0x30d40',
        'gasPrice' => $gas_price,
        'data' => '0x' . $data,
        'chainId' => $chain_id,
        'to' => $is_eth ? $address : $path[0],
        'value' => $is_eth ? '0x' . $amount->toHex() : ''
    ]);
    $transaction->sign(pagoL_encryption(pagoL_settings_get('eth-wallet-key'), false));
    $node->eth->sendRawTransaction('0x' . $transaction->serialize(), function ($error, $transaction) use (&$hash) {
        $hash = $error ? $error->getMessage() : $transaction;
    });
    if (strpos($hash, '0x') !== 0) {
        return pagoL_error($hash, 'pagoL_eth_swap');
    }
    pagoL_eth_wait_confirmation($hash);
    $data = $is_eth ? $contract->getData('swapExactETHForTokens', '0', $path, $address, time() + 180) : $contract->getData('swapExactTokensForTokens', $amount, $amount_out, $path, $address, time() + 180);
    $transaction = new Transaction([
        'nonce' => '0x' . pagoL_eth_nonce($node->eth, $address)->toHex(),
        'gas' => '0x30d40',
        'gasPrice' => $gas_price,
        'data' => '0x' . $data,
        'chainId' => $chain_id,
        'to' => $address_router,
        'value' => $is_eth ? '0x' . $amount->toHex() : ''
    ]);
    $transaction->sign(pagoL_encryption(pagoL_settings_get('eth-wallet-key'), false));
    $node->eth->sendRawTransaction('0x' . $transaction->serialize(), function ($error, $transaction) use (&$hash) {
        $hash = $error ? $error->getMessage() : $transaction;
    });
    return $hash;
}

function pagoL_eth_transfer($amount, $cryptocurrency_code = 'eth', $to = false, $from = false, $wallet_key = false) {
    pagoL_eth_load();
    $node = new Web3(pagoL_settings_get('eth-node-url'));
    $data = false;
    $response = false;
    $is_eth = $cryptocurrency_code === 'eth';
    $network = pagoL_settings_get('eth-network', 'mainnet');
    $gas = $is_eth ? '0x5208' : '0x186a0';
    $gas_price = pagoL_eth_curl('eth_gasPrice');
    if (!$from)
        $from = pagoL_settings_get_address('eth');
    if (!$to)
        $to = pagoL_settings_get('eth-node-transfer-address');
    $to = trim($to);
    if (pagoL_crypto_whitelist_invalid($to, false, $cryptocurrency_code))
        return 'whitelist-invalid';
    $balance = pagoL_eth_get_balance('eth', $from, 'wei');
    $contract_info = $is_eth ? false : pagoL_eth_get_contract($cryptocurrency_code, $network);
    if (!$balance) {
        $hash = pagoL_eth_transfer((hexdec($gas) * hexdec($gas_price) * 1.1) / 1000000000000000000, 'eth', $from, pagoL_settings_get_address('eth'));
        if (strpos($hash, '0x') !== 0)
            return pagoL_error($hash, 'pagoL_eth_transfer');
        pagoL_eth_wait_confirmation($hash);
        $balance = pagoL_eth_get_balance('eth', $from, 'wei');
    }
    if ($balance - bcmul(strval($amount), 10 ** ($is_eth ? 18 : $contract_info[1])) - (hexdec($gas) * hexdec($gas_price)) < 0) {
        $amount = ($balance - (hexdec($gas) * hexdec($gas_price) * 1.1)) / (10 ** ($is_eth ? 18 : $contract_info[1]));
    }
    if (!$is_eth) {
        $contract = (new Contract($node->provider, json_decode(file_get_contents(__DIR__ . '/vendor/web3/ERC20.json'))->abi))->at($contract_info[0]);
        $data = $contract->getData('transfer', $to, '0x' . Utils::toHex(bcmul(strval($amount), 10 ** $contract_info[1])));
    }
    $nonce = pagoL_eth_nonce($node->eth, $from)->toHex();
    $transaction = new Transaction([
        'nonce' => '0x' . ($nonce ? $nonce : '0'),
        'to' => $is_eth ? $to : $contract_info[0],
        'gas' => $gas,
        'gasPrice' => $gas_price,
        'value' => $is_eth ? '0x' . Utils::toWei(strval($amount), 'ether')->toHex() : '',
        'data' => $data ? '0x' . $data : '',
        'chainId' => pagoL_web3_chain_id($network == 'mainnet' ? 'eth' : 'sepolia')
    ]);
    $transaction->sign($wallet_key ? $wallet_key : pagoL_encryption(pagoL_settings_get('eth-wallet-key'), false));
    $node->eth->sendRawTransaction('0x' . $transaction->serialize(), function ($error, $transaction) use (&$response) {
        $response = $error ? pagoL_error($error->getMessage(), 'pagoL_eth_transfer') : $transaction;
    });
    return $response;
}

function pagoL_eth_nonce($eth, $address) {
    $nonce = 0;
    $eth->getTransactionCount($address, function ($error, $count) use (&$nonce) {
        $nonce = $error ? $error->getMessage() : $count;
    });
    return $nonce;
}

function pagoL_eth_get_contract($cryptocurrency_code = false, $network = 'mainnet') {
    $tokens = json_decode(file_get_contents(__DIR__ . '/resources/tokens.json'), true)[$network];
    return $cryptocurrency_code ? pagoL_isset($tokens, strtoupper($cryptocurrency_code)) : $tokens;
}

function pagoL_eth_wait_confirmation($hash) {
    $continue = 120;
    while ($continue) {
        $response = pagoL_eth_curl('eth_getTransactionByHash', [$hash]);
        if ($response && (!empty($response['transactionIndex']) || (isset($response['result']) && !empty($response['result']['transactionIndex'])))) {
            $continue = false;
        } else {
            sleep(1);
            $continue--;
        }
    }
    return true;
}

function pagoL_web3_chain_id($chain = 'eth') {
    $ids = ['eth' => 1, 'sepolia' => 11155111];
    return $ids[$chain];
}

function pagoL_eth_decimals_to_name($decimals) {
    $values = ['3' => 'kwei', '6' => 'mwei', '9' => 'gwei', '12' => 'szabo', '15' => 'finney', '18' => 'ether', '21' => 'kether'];
    return $values[strval($decimals)];
}

function pagoL_eth_curl($method, $params = []) {
    return pagoL_node_rpc('eth', $method, $params);
}

function pagoL_eth_get_transactions_after_timestamp($timestamp) {
    $limit = 10;
    $transactions = [];
    $block_hash = pagoL_eth_curl('eth_getBlockByNumber', ['latest', false])['hash'];
    while ($limit) {
        $block = pagoL_eth_curl('eth_getBlockByHash', [$block_hash, true]);
        $transactions = array_merge($transactions, $block['transactions']);
        if (hexdec($block['timestamp']) < $timestamp) {
            $limit = false;
        } else {
            $block_hash = $block['parentHash'];
        }
        $limit--;
    }
    return $transactions;
}

function pagoL_eth_generate_address() {
    pagoL_eth_load();
    $address = new Address;
    return ['address' => '0x' . $address->get(), 'private_key' => $address->getPrivateKey(), 'public_key' => $address->getPublicKey()];
}

function pagoL_eth_get_balance($cryptocurrency_code = 'eth', $address = false, $unit = 'dec') {
    $cryptocurrency_code = strtolower($cryptocurrency_code);
    if (!$address)
        $address = pagoL_settings_get_address($cryptocurrency_code);
    if ($address) {
        $balance = false;
        $is_ethereum = $cryptocurrency_code === 'eth';
        if ($is_ethereum) {
            $balance = pagoL_eth_curl('eth_getBalance', [$address, 'latest']);
            if (empty($balance['error'])) {
                if ($unit !== 'hex')
                    $balance = hexdec($balance);
                if ($unit === 'dec')
                    $balance = $balance / 1000000000000000000;
            } else {
                pagoL_error($balance['error']['message'], 'pagoL_eth_get_balance');
            }
        } else {
            pagoL_eth_load();
            $node = new Web3(pagoL_settings_get('eth-node-url'));
            $contract_info = pagoL_eth_get_contract($cryptocurrency_code, pagoL_settings_get('eth-network', 'mainnet'));
            $contract = new Contract($node->provider, json_decode(file_get_contents(__DIR__ . '/vendor/web3/ERC20.json'))->abi);
            $contract->at($contract_info[0])->call('balanceOf', $address, function ($error, $account) use (&$balance, &$unit, &$contract_info) {
                if ($error) {
                    $balance = pagoL_error($error->getMessage(), 'pagoL_eth_get_balance');
                } else {
                    if ($unit === 'hex') {
                        $balance = $account[0]->toHex();
                    } else {
                        $balance = $account[0]->toString();
                        if ($unit === 'dec')
                            $balance = intval($balance) / (10 ** $contract_info[1]);
                        else
                            $balance = intval(Utils::toWei($balance, pagoL_eth_decimals_to_name($contract_info[1]))->ToString());
                    }

                }
            });
        }
        return $balance;
    }
    return false;
}

function pagoL_eth_validate_address($address) {
    pagoL_eth_load();
    $address = trim($address);
    if (preg_match('/^(0x)?[0-9a-f]{40}$/i', $address)) {
        $match = preg_match('/^(0x)?[0-9a-f]{40}$/', $address) || preg_match('/^(0x)?[0-9A-F]{40}$/', $address);
        if ($match)
            return true;
        $address = str_replace('0x', '', $address);
        $hash = Keccak::hash(strtolower($address), 256);
        for ($i = 0; $i < 40; $i++) {
            if (ctype_alpha($address[$i])) {
                $charInt = intval($hash[$i], 16);
                if ((ctype_upper($address[$i]) && $charInt <= 7) || (ctype_lower($address[$i]) && $charInt > 7)) {
                    return false;
                }
            }
        }
        return true;
    }
    return false;
}
?>