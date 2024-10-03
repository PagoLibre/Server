<?php

/*
 * ==========================================================
 * UPLOAD.PHP
 * ==========================================================
 *
 * Manage all uploads of front-end and admin. � 2022-2024 PagoLibre. All rights reserved.
 *
 */

require_once('functions.php');
if (PAGOL_CLOUD) {
    pagoL_cloud_load();
}
if (defined('PAGOL_CROSS_DOMAIN') && PAGOL_CROSS_DOMAIN) {
    header('Access-Control-Allow-Origin: *');
}
if (isset($_FILES['file'])) {
    if (0 < $_FILES['file']['error']) {
        die(json_encode(['error', 'PagoLibre: Error into upload.php file.']));
    } else {
        $file_name = pagoL_sanatize_file_name($_FILES['file']['name']);
        $infos = pathinfo($file_name);
        $path_cloud = pagoL_cloud_path_part();
        $path = __DIR__ . '/uploads' . $path_cloud;
        $url = PAGOL_URL . 'uploads' . $path_cloud;
        $is_checkout = pagoL_isset($_GET, 'target') == 'checkout-file';
        $directory = $is_checkout ? '/checkout' : false;
        if (sb_is_allowed_extension(pagoL_isset($infos, 'extension'))) {
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            if ($directory) {
                $path .= $directory;
                $url .= $directory;
                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }
            }
            if (!PAGOL_CLOUD && !file_exists($path . '/index.html')) {
                pagoL_file($path . '/index.html', '');
            }
            $file_name = pagoL_random_string() . '_' . pagoL_string_slug($file_name);
            $path .= '/' . $file_name;
            $url .= '/' . $file_name;
            move_uploaded_file($_FILES['file']['tmp_name'], $path);
            if ($is_checkout && PAGOL_CLOUD && defined('CLOUD_AWS_S3')) {
                $url_aws = pagoL_cloud_aws_s3($path);
                if (strpos($url_aws, 'http') === 0) {
                    $url = $url_aws;
                    unlink($path);
                }
            }
            die(json_encode([true, $url, $file_name]));
        } else {
            die(json_encode([false, 'The file you are trying to upload has an extension that is not allowed.']));
        }
    }
} else {
    die(json_encode([false, 'PagoLibre Error: Key file in $_FILES not found.']));
}

function sb_is_allowed_extension($extension) {
    $extension = strtolower($extension);
    $allowed_extensions = ['oga', 'json', 'psd', 'ai', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'key', 'ppt', 'odt', 'xls', 'xlsx', 'zip', 'rar', 'mp3', 'm4a', 'ogg', 'wav', 'mp4', 'mov', 'wmv', 'avi', 'mpg', 'ogv', '3gp', '3g2', 'mkv', 'txt', 'ico', 'csv', 'ttf', 'font', 'css', 'scss'];
    return in_array($extension, $allowed_extensions) || (defined('PAGOL_FILE_EXTENSIONS') && in_array($extension, PAGOL_FILE_EXTENSIONS));
}

?>