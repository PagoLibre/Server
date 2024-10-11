<script>
    if (!window.location.search.includes("cryptocurrency")) {
        let PAGOLIBRE_URL = "http://example.com/pagolibre";
        let PAGOLIBRE_PAYMENT_METHOD_TEXT = ["Pay with Crypto"];
        let PAGOLIBRE_INTERVAL = setInterval(function () {
            let element = document.getElementsByClassName("payment-method-list__item__info");
            if (element.length) {
                if (PAGOLIBRE_PAYMENT_METHOD_TEXT.includes(element[0].innerHTML)) {
                    document.location = PAGOLIBRE_URL + "/pay.php?checkout_id=custom-shopify&price=" + Shopify.checkout.payment_due + "&currency=" + Shopify.checkout.currency + "&external-reference=shopify_" + Shopify.checkout.order_id + "&redirect=" + encodeURIComponent(document.location.href) + "&note=Shopify Order ID " + Shopify.checkout.order_id
                }
                clearInterval(PAGOLIBRE_INTERVAL);
            }
        }, 50);
    }
</script>