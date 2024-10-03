<?php
require_once(__DIR__ . '/functions.php');
$minify = isset($_GET['debug']) ? false : (PAGOL_CLOUD || pagoL_settings_get('minify'));
pagoL_cloud_load();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no" />
    <title>
        <?php echo pagoL_settings_get('shop-page-name', PAGOL_CLOUD ? CLOUD_NAME : 'PagoLibre') ?>
    </title>
    <script src="<?php echo PAGOL_URL . 'js/client' . ($minify ? '.min' : '') . '.js?v=' . PAGOL_VERSION ?>"></script>
    <script src="<?php echo PAGOL_URL . 'apps/shop/shop.admin' . ($minify ? '.min' : '') . '.js?v=' . PAGOL_VERSION ?>"></script>
    <link rel="stylesheet" href="<?php echo PAGOL_URL . 'css/client.css?v=' . PAGOL_VERSION ?>" type="text/css" />
    <link rel="stylesheet" href="<?php echo PAGOL_URL . 'apps/shop/shop.css?v=' . PAGOL_VERSION ?>" media="all" />
    <link rel="shortcut icon" type="image/svg" href="<?php echo (pagoL_settings_get('logo-icon-url', PAGOL_CLOUD ? CLOUD_ICON : PAGOL_URL . 'media/icon.svg')) ?>" />
    <?php echo pagoL_settings_get('color-1') ? '<style>#pagoL-shop-grid > div:hover { border-color: ' . pagoL_settings_get('color-1') . '; } </style>' : '' ?>
</head>
<body>
    <?php echo pagoL_shop_page() ?>
</body>
</html>
