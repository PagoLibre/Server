<?php

/*
 * ==========================================================
 * ADMIN_CODE.PHP
 * ==========================================================
 *
 * Admin area. © 2022-2024 PagoLibre. All rights reserved.
 *
 */

function pagoL_box_admin() {
    pagoL_colors_admin();
    pagoL_load_custom_js_css();
    $is_shop = defined('PAGOL_SHOP');
    $css = pagoL_is_rtl(pagoL_language(true)) ? ' pagoL-rtl' : '';
    if (!PAGOL_CLOUD && !pagoL_ve_box()) {
        return;
    }
    pagoL_version_updates();
    if ($is_shop) {
        pagoL_shop_db_update();
    }
    if (pagoL_is_agent()) {
        $css .= ' pagoL-agent';
    }
    ?>
    <div class="pagoL-main pagoL-admin pagoL-area-transactions<?php echo $css ?>">
        <div class="pagoL-sidebar">
            <div>
                <img class="pagoL-logo" src="<?php echo PAGOL_CLOUD ? CLOUD_LOGO : (pagoL_settings_get('logo-admin') ? pagoL_settings_get('logo-url', PAGOL_URL . 'media/logo.svg') : PAGOL_URL . 'media/logo.svg') ?>" />
                <img class="pagoL-logo-icon" src="<?php echo PAGOL_CLOUD ? CLOUD_ICON : (pagoL_settings_get('logo-admin') ? pagoL_settings_get('logo-icon-url', PAGOL_URL . 'media/icon.svg') : PAGOL_URL . 'media/icon.svg') ?>" />
            </div>
            <div class="pagoL-nav">
                <div id="transactions" class="pagoL-active">
                    <i class="pagoL-icon-shuffle"></i>
                    <span>
                        <?php pagoL_e('Transactions') ?>
                    </span>
                </div>
                <div id="checkouts">
                    <i class="pagoL-icon-automation"></i>
                    <span>
                        <?php pagoL_e('Checkouts') ?>
                    </span>
                </div>
                <div id="balances">
                    <i class="pagoL-icon-bar-chart"></i>
                    <span>
                        <?php pagoL_e('Balances') ?>
                    </span>
                </div>
                <?php
                if (!pagoL_is_agent()) {
                    echo '<div id="settings"><i class="pagoL-icon-settings"></i><span>' . pagoL_('Settings') . '</span></div>';
                }
                if ($is_shop) {
                    echo '<div id="analytics"><i class="pagoL-icon-calendar"></i><span>' . pagoL_('Analytics') . '</span></div>';
                }
                if (PAGOL_CLOUD) {
                    echo '<div id="account"><i class="pagoL-icon-user"></i><span>' . pagoL_('Account') . '</span></div>';
                }
                ?>
            </div>
            <div class="pagoL-bottom">
                <div id="pagoL-request-payment" class="pagoL-btn">
                    <?php pagoL_e('Request a payment') ?>
                </div>
                <div id="pagoL-create-checkout" class="pagoL-btn">
                    <?php pagoL_e('Create checkout') ?>
                </div>
                <div id="pagoL-save-settings" class="pagoL-btn">
                    <?php pagoL_e('Save settings') ?>
                </div>
                <div class="pagoL-mobile-menu">
                    <i class="pagoL-icon-menu"></i>
                    <div class="pagoL-flex">
                        <div class="pagoL-link" id="pagoL-logout">
                            <?php pagoL_e('Logout') ?>
                        </div>
                        <div id="pagoL-version">
                            <?php echo PAGOL_VERSION ?>
                        </div>
                        <a class="pagoL-btn-icon" href="<?php echo PAGOL_CLOUD ? CLOUD_DOCS : 'https://PagoLibre/docs' ?>" target="_blank">
                            <i class="pagoL-icon-help"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="pagoL-body">
            <main>
                <div data-area="transactions" class="pagoL-active">
                    <div class="pagoL-nav-wide">
                        <div class="pagoL-input pagoL-search">
                            <input type="text" id="pagoL-search-transactions" class="pagoL-search-input" name="pagoL-search" placeholder="<?php pagoL_e('Search all transactions') ?>" autocomplete="false" />
                            <input type="text" class="pagoL-hidden" />
                            <i class="pagoL-icon-search"></i>
                        </div>
                        <div id="pagoL-download-transitions" class="pagoL-btn-icon">
                            <i class="pagoL-icon-download"></i>
                        </div>
                        <?php pagoL_admin_filters() ?>
                        <div id="pagoL-filters" class="pagoL-btn-icon">
                            <i class="pagoL-icon-filters"></i>
                        </div>
                    </div>
                    <hr />
                    <table id="pagoL-table-transactions" class="pagoL-table">
                        <thead>
                            <tr>
                                <th data-field="id"></th>
                                <th data-field="date">
                                    <?php pagoL_e('Date') ?>
                                </th>
                                <th data-field="from">
                                    <?php pagoL_e('From') ?>
                                </th>
                                <th data-field="to">
                                    <?php pagoL_e('To') ?>
                                </th>
                                <th data-field="status">
                                    <?php pagoL_e('Status') ?>
                                </th>
                                <th data-field="amount">
                                    <?php pagoL_e('Amount') ?>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div data-area="checkouts" class="pagoL-loading">
                    <table id="pagoL-table-checkouts" class="pagoL-table">
                        <tbody></tbody>
                    </table>
                    <div id="pagoL-checkouts-form">
                        <form>
                            <div class="pagoL-info"></div>
                            <div class="pagoL-top">
                                <div id="pagoL-checkouts-list" class="pagoL-btn pagoL-btn-border">
                                    <i class="pagoL-icon-back"></i>
                                    <?php pagoL_e('Checkouts list') ?>
                                </div>
                            </div>
                            <div id="pagoL-checkout-title" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('Title') ?>
                                </span>
                                <input type="text" required />
                            </div>
                            <div id="pagoL-checkout-description" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('Description') ?>
                                </span>
                                <input type="text" />
                            </div>
                            <div class="pagoL-flex">
                                <div id="pagoL-checkout-price" data-type="select" class="pagoL-input">
                                    <span>
                                        <?php pagoL_e('Price') ?>
                                    </span>
                                    <input type="number" />
                                </div>
                                <div id="pagoL-checkout-currency" data-type="select" class="pagoL-input">
                                    <select>
                                        <option value="" selected>Default</option>
                                        <option value="crypto" selected>
                                            <?php pagoL_e('Cryptocurrency') ?>
                                        </option>
                                        <option value="AED">United Arab Emirates Dirham</option>
                                        <option value="AFN">Afghan Afghani</option>
                                        <option value="ALL">Albanian Lek</option>
                                        <option value="AMD">Armenian Dram</option>
                                        <option value="ANG">Netherlands Antillean Guilder</option>
                                        <option value="AOA">Angolan Kwanza</option>
                                        <option value="ARS">Argentine Peso</option>
                                        <option value="AUD">Australian Dollar</option>
                                        <option value="AWG">Aruban Florin</option>
                                        <option value="AZN">Azerbaijani Manat</option>
                                        <option value="BAM">Bosnia-Herzegovina Convertible Mark</option>
                                        <option value="BBD">Barbadian Dollar</option>
                                        <option value="BDT">Bangladeshi Taka</option>
                                        <option value="BGN">Bulgarian Lev</option>
                                        <option value="BHD">Bahraini Dinar</option>
                                        <option value="BIF">Burundian Franc</option>
                                        <option value="BMD">Bermudan Dollar</option>
                                        <option value="BND">Brunei Dollar</option>
                                        <option value="BOB">Bolivian Boliviano</option>
                                        <option value="BRL">Brazilian Real</option>
                                        <option value="BSD">Bahamian Dollar</option>
                                        <option value="BTN">Bhutanese Ngultrum</option>
                                        <option value="BWP">Botswanan Pula</option>
                                        <option value="BYN">Belarusian Ruble</option>
                                        <option value="BZD">Belize Dollar</option>
                                        <option value="CAD">Canadian Dollar</option>
                                        <option value="CDF">Congolese Franc</option>
                                        <option value="CHF">Swiss Franc</option>
                                        <option value="CLF">Chilean Unit of Account (UF)</option>
                                        <option value="CLP">Chilean Peso</option>
                                        <option value="CNH">Chinese Yuan (Offshore)</option>
                                        <option value="CNY">Chinese Yuan</option>
                                        <option value="COP">Colombian Peso</option>
                                        <option value="CRC">Costa Rican Colón</option>
                                        <option value="CUC">Cuban Convertible Peso</option>
                                        <option value="CUP">Cuban Peso</option>
                                        <option value="CVE">Cape Verdean Escudo</option>
                                        <option value="CZK">Czech Republic Koruna</option>
                                        <option value="DJF">Djiboutian Franc</option>
                                        <option value="DKK">Danish Krone</option>
                                        <option value="DOP">Dominican Peso</option>
                                        <option value="DZD">Algerian Dinar</option>
                                        <option value="EGP">Egyptian Pound</option>
                                        <option value="ERN">Eritrean Nakfa</option>
                                        <option value="ETB">Ethiopian Birr</option>
                                        <option value="EUR">Euro</option>
                                        <option value="FJD">Fijian Dollar</option>
                                        <option value="FKP">Falkland Islands Pound</option>
                                        <option value="GBP">British Pound Sterling</option>
                                        <option value="GEL">Georgian Lari</option>
                                        <option value="GGP">Guernsey Pound</option>
                                        <option value="GHS">Ghanaian Cedi</option>
                                        <option value="GIP">Gibraltar Pound</option>
                                        <option value="GMD">Gambian Dalasi</option>
                                        <option value="GNF">Guinean Franc</option>
                                        <option value="GTQ">Guatemalan Quetzal</option>
                                        <option value="GYD">Guyanaese Dollar</option>
                                        <option value="HKD">Hong Kong Dollar</option>
                                        <option value="HNL">Honduran Lempira</option>
                                        <option value="HRK">Croatian Kuna</option>
                                        <option value="HTG">Haitian Gourde</option>
                                        <option value="HUF">Hungarian Forint</option>
                                        <option value="IDR">Indonesian Rupiah</option>
                                        <option value="ILS">Israeli New Sheqel</option>
                                        <option value="IMP">Manx pound</option>
                                        <option value="INR">Indian Rupee</option>
                                        <option value="IQD">Iraqi Dinar</option>
                                        <option value="IRR">Iranian Rial</option>
                                        <option value="ISK">Icelandic Króna</option>
                                        <option value="JEP">Jersey Pound</option>
                                        <option value="JMD">Jamaican Dollar</option>
                                        <option value="JOD">Jordanian Dinar</option>
                                        <option value="JPY">Japanese Yen</option>
                                        <option value="KES">Kenyan Shilling</option>
                                        <option value="KGS">Kyrgystani Som</option>
                                        <option value="KHR">Cambodian Riel</option>
                                        <option value="KMF">Comorian Franc</option>
                                        <option value="KPW">North Korean Won</option>
                                        <option value="KRW">South Korean Won</option>
                                        <option value="KWD">Kuwaiti Dinar</option>
                                        <option value="KYD">Cayman Islands Dollar</option>
                                        <option value="KZT">Kazakhstani Tenge</option>
                                        <option value="LAK">Laotian Kip</option>
                                        <option value="LBP">Lebanese Pound</option>
                                        <option value="LKR">Sri Lankan Rupee</option>
                                        <option value="LRD">Liberian Dollar</option>
                                        <option value="LSL">Lesotho Loti</option>
                                        <option value="LYD">Libyan Dinar</option>
                                        <option value="MAD">Moroccan Dirham</option>
                                        <option value="MDL">Moldovan Leu</option>
                                        <option value="MGA">Malagasy Ariary</option>
                                        <option value="MKD">Macedonian Denar</option>
                                        <option value="MMK">Myanma Kyat</option>
                                        <option value="MNT">Mongolian Tugrik</option>
                                        <option value="MOP">Macanese Pataca</option>
                                        <option value="MRU">Mauritanian Ouguiya</option>
                                        <option value="MUR">Mauritian Rupee</option>
                                        <option value="MVR">Maldivian Rufiyaa</option>
                                        <option value="MWK">Malawian Kwacha</option>
                                        <option value="MXN">Mexican Peso</option>
                                        <option value="MYR">Malaysian Ringgit</option>
                                        <option value="MZN">Mozambican Metical</option>
                                        <option value="NAD">Namibian Dollar</option>
                                        <option value="NGN">Nigerian Naira</option>
                                        <option value="NIO">Nicaraguan Córdoba</option>
                                        <option value="NOK">Norwegian Krone</option>
                                        <option value="NPR">Nepalese Rupee</option>
                                        <option value="NZD">New Zealand Dollar</option>
                                        <option value="OMR">Omani Rial</option>
                                        <option value="PAB">Panamanian Balboa</option>
                                        <option value="PEN">Peruvian Nuevo Sol</option>
                                        <option value="PGK">Papua New Guinean Kina</option>
                                        <option value="PHP">Philippine Peso</option>
                                        <option value="PKR">Pakistani Rupee</option>
                                        <option value="PLN">Polish Zloty</option>
                                        <option value="PYG">Paraguayan Guarani</option>
                                        <option value="QAR">Qatari Rial</option>
                                        <option value="RON">Romanian Leu</option>
                                        <option value="RSD">Serbian Dinar</option>
                                        <option value="RUB">Russian Ruble</option>
                                        <option value="RWF">Rwandan Franc</option>
                                        <option value="SAR">Saudi Riyal</option>
                                        <option value="SBD">Solomon Islands Dollar</option>
                                        <option value="SCR">Seychellois Rupee</option>
                                        <option value="SDG">Sudanese Pound</option>
                                        <option value="SEK">Swedish Krona</option>
                                        <option value="SGD">Singapore Dollar</option>
                                        <option value="SHP">Saint Helena Pound</option>
                                        <option value="SLL">Sierra Leonean Leone</option>
                                        <option value="SOS">Somali Shilling</option>
                                        <option value="SRD">Surinamese Dollar</option>
                                        <option value="SSP">South Sudanese Pound</option>
                                        <option value="STD">São Tomé and Príncipe Dobra (pre-2018)</option>
                                        <option value="STN">São Tomé and Príncipe Dobra</option>
                                        <option value="SVC">Salvadoran Colón</option>
                                        <option value="SYP">Syrian Pound</option>
                                        <option value="SZL">Swazi Lilangeni</option>
                                        <option value="THB">Thai Baht</option>
                                        <option value="TJS">Tajikistani Somoni</option>
                                        <option value="TMT">Turkmenistani Manat</option>
                                        <option value="TND">Tunisian Dinar</option>
                                        <option value="TOP">Tongan Pa'anga</option>
                                        <option value="TRY">Turkish Lira</option>
                                        <option value="TTD">Trinidad and Tobago Dollar</option>
                                        <option value="TWD">New Taiwan Dollar</option>
                                        <option value="TZS">Tanzanian Shilling</option>
                                        <option value="UAH">Ukrainian Hryvnia</option>
                                        <option value="UGX">Ugandan Shilling</option>
                                        <option value="USD">United States Dollar</option>
                                        <option value="UYU">Uruguayan Peso</option>
                                        <option value="UZS">Uzbekistan Som</option>
                                        <option value="VEF">Venezuelan Bolívar Fuerte (Old)</option>
                                        <option value="VES">Venezuelan Bolívar Soberano</option>
                                        <option value="VND">Vietnamese Dong</option>
                                        <option value="VUV">Vanuatu Vatu</option>
                                        <option value="WST">Samoan Tala</option>
                                        <option value="XAF">CFA Franc BEAC</option>
                                        <option value="XAG">Silver Ounce</option>
                                        <option value="XAU">Gold Ounce</option>
                                        <option value="XCD">East Caribbean Dollar</option>
                                        <option value="XDR">Special Drawing Rights</option>
                                        <option value="XOF">CFA Franc BCEAO</option>
                                        <option value="XPD">Palladium Ounce</option>
                                        <option value="XPF">CFP Franc</option>
                                        <option value="XPT">Platinum Ounce</option>
                                        <option value="YER">Yemeni Rial</option>
                                        <option value="ZAR">South African Rand</option>
                                        <option value="ZMW">Zambian Kwacha</option>
                                        <option value="ZWL">Zimbabwean Dollar</option>
                                        <?php
                                        $cryptocurrencies = pagoL_crypto_name();
                                        $code = '';
                                        foreach ($cryptocurrencies as $key => $value) {
                                            $code .= '<option value="' . strtoupper($key) . '">' . $value[1] . '</option>';
                                        }
                                        echo $code;
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div id="pagoL-checkout-type" data-type="select" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('Type') ?>
                                </span>
                                <select>
                                    <option value="I" selected>
                                        <?php pagoL_e('Inline') ?>
                                    </option>
                                    <option value="L">
                                        <?php pagoL_e('Link') ?>
                                    </option>
                                    <option value="P">
                                        <?php pagoL_e('Popup') ?>
                                    </option>
                                    <option value="H">
                                        <?php pagoL_e('Hidden') ?>
                                    </option>
                                </select>
                            </div>
                            <div id="pagoL-checkout-redirect" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('Redirect URL') ?>
                                </span>
                                <input type="url" />
                            </div>
                            <div id="pagoL-checkout-external_reference" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('External reference') ?>
                                </span>
                                <input type="text" />
                            </div>
                            <div id="pagoL-checkout-hide_title" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('Hide title') ?>
                                </span>
                                <input type="checkbox" />
                            </div>
                            <?php
                            if (defined('PAGOL_SHOP')) {
                                echo pagoL_shop_checkout_area();
                            }
                            if (defined('PAGOL_WP')) {
                                echo '<div id="pagoL-checkout-shortcode" class="pagoL-input"><span>Shortcode</span><div></div><i class="pagoL-icon-copy pagoL-clipboard pagoL-toolip-cnt"><span class="pagoL-toolip">' . pagoL_('Copy to clipboard') . '</span></i></div>';
                            }
                            if (pagoL_settings_get('url-rewrite-checkout') || PAGOL_CLOUD) {
                                echo '<div id="pagoL-checkout-slug" class="pagoL-input"><span>' . pagoL_('Slug') . '</span><input type="text" /></div>';
                            }
                            ?>
                            <div id="pagoL-checkout-embed-code" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('Embed code') ?>
                                </span>
                                <div></div>
                                <i class="pagoL-icon-copy pagoL-clipboard pagoL-toolip-cnt">
                                    <span class="pagoL-toolip">
                                        <?php pagoL_e('Copy to clipboard') ?>
                                    </span>
                                </i>
                            </div>
                            <div id="pagoL-checkout-payment-link" class="pagoL-input">
                                <span>
                                    <?php pagoL_e('Payment link') ?>
                                </span>
                                <div></div>
                                <i class="pagoL-icon-copy pagoL-clipboard pagoL-toolip-cnt">
                                    <span class="pagoL-toolip">
                                        <?php pagoL_e('Copy to clipboard') ?>
                                    </span>
                                </i>
                            </div>
                            <div class="pagoL-bottom">
                                <div id="pagoL-save-checkout" class="pagoL-btn">
                                    <?php pagoL_e('Save checkout') ?>
                                </div>
                                <a id="pagoL-delete-checkout" class="pagoL-btn-icon pagoL-btn-red">
                                    <i class="pagoL-icon-delete"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <div data-area="balance">
                    <div>
                        <div id="pagoL-balance-total" class="pagoL-title"></div>
                        <div class="pagoL-text">
                            <?php pagoL_e('Available balance') ?>
                        </div>
                    </div>
                    <table id="pagoL-table-balances" class="pagoL-table">
                        <thead>
                            <tr>
                                <th data-field="cryptocurrency">
                                    <?php pagoL_e('Crypto currency') ?>
                                </th>
                                <th data-field="balance">
                                    <?php pagoL_e('Balance') ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div data-area="settings" class="pagoL-loading">
                    <?php pagoL_settings_populate() ?>
                </div>
                <?php
                if ($is_shop) {
                    pagoL_shop_analytics_area();
                }
                if (PAGOL_CLOUD) {
                    require(__DIR__ . '/cloud/account.php');
                }
                ?>
            </main>
        </div>
        <div id="pagoL-card" class="pagoL-info-card"></div>
    </div>
    <div id="pagoL-lightbox">
        <div>
            <div class="pagoL-top">
                <div class="pagoL-title"></div>
                <div class="pagoL-flex">
                    <div class="pagoL-lightbox-buttons pagoL-flex"></div>
                    <div id="pagoL-lightbox-close" class="pagoL-btn-icon pagoL-btn-red">
                        <i class="pagoL-icon-close"></i>
                    </div>
                </div>
            </div>
            <div id="pagoL-lightbox-main" class="pagoL-scrollbar"></div>
        </div>
        <span></span>
    </div>
    <div id="pagoL-lightbox-loading" class="pagoL-loading"></div>
    <form id="pagoL-upload-form" action="#" method="post" enctype="multipart/form-data">
        <input type="file" name="files[]" />
    </form>
<?php } ?>

<?php function pagoL_admin_filters($show_all_statues = true) { ?>
    <div class="pagoL-nav-filters">
        <div class="pagoL-input">
            <input class="pagoL-filter-date" placeholder="<?php pagoL_e('Start date...') ?>" type="text" readonly />
            <input class="pagoL-filter-date-2" placeholder="<?php pagoL_e('End date...') ?>" type="text" readonly />
        </div>
        <div class="pagoL-filter-status pagoL-select pagoL-right">
            <p>
                <?php pagoL_e($show_all_statues ? 'All statuses' : 'Completed') ?>
            </p>
            <ul>
                <?php
                if ($show_all_statues) {
                    echo ' <li data-value="" class="pagoL-active">' . pagoL_('All statuses') . '</li>';
                }
                ?>
                <li data-value="C" <?php echo $show_all_statues ? '' : ' class="pagoL-active"' ?>>
                    <?php pagoL_e('Completed') ?>
                </li>
                <li data-value="P">
                    <?php pagoL_e('Pending') ?>
                </li>
                <li data-value="R">
                    <?php pagoL_e('Refunded') ?>
                </li>
            </ul>
        </div>
        <div class="pagoL-filter-cryptocurrency pagoL-select pagoL-right">
            <p>
                <?php pagoL_e('All currencies') ?>
            </p>
            <ul>
                <li data-value="" class="pagoL-active">
                    <?php pagoL_e('All currencies') ?>
                </li>
                <?php
                $cryptocurrencies = pagoL_crypto_name(false, true);
                $currencies = pagoL_db_get('SELECT cryptocurrency FROM pagoL_transactions GROUP BY cryptocurrency UNION SELECT currency FROM pagoL_transactions GROUP BY currency', false);
                $code = '';
                for ($i = 0; $i < count($currencies); $i++) {
                    $currency = $currencies[$i]['cryptocurrency'];
                    if (!in_array($currency, ['stripe', 'verifone', 'paypal'])) {
                        $code .= ' <li data-value="' . $currency . '">' . (isset($cryptocurrencies[$currency]) ? $cryptocurrencies[$currency][1] . ' ' . pagoL_crypto_get_network($currency, true, true) : strtoupper($currency)) . '</li>';
                    }
                }
                echo $code;
                ?>
            </ul>
        </div>
        <div class="pagoL-filter-checkout pagoL-select pagoL-right">
            <p>
                <?php pagoL_e('All checkouts') ?>
            </p>
            <ul>
                <li data-value="" class="pagoL-active">
                    <?php pagoL_e('All checkouts') ?>
                </li>
                <?php
                $checkouts = pagoL_checkout_get();
                $code = '';
                for ($i = 0; $i < count($checkouts); $i++) {
                    $code .= ' <li data-value="' . $checkouts[$i]['id'] . '">' . $checkouts[$i]['title'] . '</li>';
                }
                echo $code;
                ?>
            </ul>
        </div>
    </div>
<?php } ?>