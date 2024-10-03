<?php
/*
 * ==========================================================
 * ADMINISTRATION PAGE
 * ==========================================================
 *
 */

define('PAGOL_CLOUD', file_exists(__DIR__ . '/cloud'));
$installation = false;
if (!PAGOL_CLOUD) {
    if (!file_exists(__DIR__ . '/config.php')) {
        $installation = true;
        $file = fopen(__DIR__ . '/config.php', 'w');
        fwrite($file, '<?php define("PAGOL_URL", "") ?>');
        fclose($file);
    }
    require(__DIR__ . '/functions.php');
    if (!defined('PAGOL_USER')) {
        $installation = true;
    }
}
if (PAGOL_CLOUD) {
    require(__DIR__ . '/functions.php');
    require(__DIR__ . '/cloud/functions.php');
    pagoL_cloud_load();
    if (!defined('PAGOL_URL')) {
        define('PAGOL_URL', CLOUD_URL);
    }
}
$minify = $installation || isset($_GET['debug']) ? false : (PAGOL_CLOUD || pagoL_settings_get('minify'));

function pagoL_box_installation() { ?>
    <div class="pagoL-main pagoL-installation pagoL-box">
        <form>
            <div class="pagoL-info"></div>
            <div class="pagoL-top">
                <img src="media/logo.svg" />
                <div class="pagoL-title">
                    Installation
                </div>
                <div class="pagoL-text">
                    Please complete the installation process.
                </div>
            </div>
            <div id="user" class="pagoL-input">
                <span>
                    Username
                </span>
                <input type="text" required />
            </div>
            <div id="password" class="pagoL-input">
                <span>
                    Password
                </span>
                <input type="password" required />
            </div>
            <div id="password-check" class="pagoL-input">
                <span>
                    Repeat password
                </span>
                <input type="password" required />
            </div>
            <div id="db-name" class="pagoL-input">
                <span>
                    Database name
                </span>
                <input type="text" required />
            </div>
            <div id="db-user" class="pagoL-input">
                <span>
                    Database user
                </span>
                <input type="text" required />
            </div>
            <div id="db-password" class="pagoL-input">
                <span>
                    Database password
                </span>
                <input type="password" />
            </div>
            <div id="db-host" class="pagoL-input">
                <span>
                    Database host
                </span>
                <input type="text" placeholder="Default" />
            </div>
            <div id="db-port" class="pagoL-input">
                <span>
                    Database port
                </span>
                <input type="number" placeholder="Default" />
            </div>
            <div class="pagoL-bottom">
                <div id="pagoL-submit-installation" class="pagoL-btn">
                    Complete installation
                </div>
            </div>
        </form>
    </div>
<?php } ?>

<?php function pagoL_box_login() {
    pagoL_colors_admin();
    pagoL_load_custom_js_css();
    ?>
    <div class="pagoL-main pagoL-login pagoL-box">
        <form>
            <div class="pagoL-info"></div>
            <div class="pagoL-top">
                <img src="<?php echo pagoL_settings_get('logo-admin') ? pagoL_settings_get('logo-url', PAGOL_URL . 'media/logo.svg') : 'media/logo.svg' ?>" />
                <div class="pagoL-title">
                    <?php echo pagoL_('Sign into') ?>
                </div>
                <div class="pagoL-text">
                    <?php echo pagoL_('To continue to') . ' ' . pagoL_settings_get('brand-name', 'PagoLibre') ?>
                </div>
            </div>
            <div id="username" class="pagoL-input">
                <span>
                    <?php echo pagoL_('Username') ?>
                </span>
                <input type="text" required />
            </div>
            <div id="password" class="pagoL-input">
                <span>
                    <?php echo pagoL_('Password') ?>
                </span>
                <input type="password" required />
            </div>
            <?php
            if (pagoL_settings_get('two-fa-active')) {
                echo '<div id="two-fa" class="pagoL-input"><span>' . pagoL_('Verification code') . '</span><input type="password" required /></div>';
            }
            ?>
            <div class="pagoL-bottom">
                <div id="pagoL-submit-login" class="pagoL-btn">
                    <?php echo pagoL_('Sign in') ?>
                </div>
            </div>
        </form>
    </div>
<?php } ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no" />
    <title>
        <?php echo PAGOL_CLOUD && !pagoL_verify_admin() ? pagoL_settings_get('brand-name', CLOUD_NAME) . ' | Login' : pagoL_('Admin') . ' | ' . (PAGOL_CLOUD ? CLOUD_NAME : pagoL_settings_get('brand-name', 'PagoLibre')); ?>
    </title>
    <script><?php echo pagoL_settings_js_admin() ?></script>
    <script src="<?php echo PAGOL_URL . 'js/client' . ($minify ? '.min' : '') . '.js?v=' . PAGOL_VERSION ?>"></script>
    <script src="<?php echo PAGOL_URL . 'js/admin' . ($minify ? '.min' : '') . '.js?v=' . PAGOL_VERSION ?>"></script>
    <link rel="stylesheet" href="<?php echo PAGOL_URL . 'css/admin.css?v=' . PAGOL_VERSION ?>" media="all" />
    <?php
    if (defined('PAGOL_SHOP')) {
        echo '<script src="' . PAGOL_URL . 'apps/shop/shop.admin' . ($minify ? '.min' : '') . '.js?v=' . PAGOL_VERSION . '"></script>';
    }
    if (PAGOL_CLOUD) {
        pagoL_cloud_admin();
    }
    ?>
    <link rel="shortcut icon" type="image/svg" href="<?php echo PAGOL_CLOUD ? CLOUD_ICON : (pagoL_settings_get('logo-admin') ? pagoL_settings_get('logo-icon-url', PAGOL_URL . 'media/icon.svg') : PAGOL_URL . 'media/icon.svg') ?>" />
</head>
<body>
    <?php
    require(__DIR__ . '/admin_code.php');
    if ($installation) {
        pagoL_box_installation();
    } else if (pagoL_verify_admin()) {
        pagoL_box_admin();
    } else {
        if (PAGOL_CLOUD) {
            require(__DIR__ . '/cloud/registration.php');
            pagoL_cloud_registration_login_box();
        } else {
            pagoL_box_login();
        }
    }
    ?>
</body>
</html>
