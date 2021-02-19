$(document).ready(function () {
    n(),
        loadInline(document, 'https://secure.2checkout.com/checkout/client/twoCoInlineCart.js', 'TwoCoInlineCart');
}),
    window.TwocheckoutInlineCheckout = function () {
        var cartClosedhandlerGuid = null;
        $.ajax({
            url: tco_verify_url,
            type: "GET",
            dataType: 'json',
            success: function (result) {

                if (n(), result.status === 'success') {;
                    var inline_params = JSON.parse(result.inline_params);
                    if(result.inline_params.length > 0)
                    {
                        TwoCoInlineCart.setup.setConfig('cart', {'host': 'https://secure.2checkout.com'});
                        TwoCoInlineCart.setup.setMerchant(inline_params.merchant);
                        TwoCoInlineCart.setup.setMode(inline_params.mode);
                        TwoCoInlineCart.register();

                        TwoCoInlineCart.cart.setReset(true);
                        TwoCoInlineCart.cart.setAutoAdvance(true);
                        TwoCoInlineCart.cart.setLanguage(inline_params.language);
                        TwoCoInlineCart.cart.setCurrency(inline_params.currency);
                        TwoCoInlineCart.cart.setTest(inline_params.test);
                        TwoCoInlineCart.cart.setOrderExternalRef(inline_params['order-ext-ref']);
                        TwoCoInlineCart.cart.setExternalCustomerReference(inline_params['customer-ext-ref']);
                        TwoCoInlineCart.cart.setSource(inline_params.src);
                        TwoCoInlineCart.cart.setReturnMethod(inline_params['return-method']);

                        TwoCoInlineCart.products.removeAll();
                        TwoCoInlineCart.products.addMany(inline_params.products);
                        TwoCoInlineCart.billing.reset();
                        TwoCoInlineCart.billing.setData(inline_params.billing_address);
                        TwoCoInlineCart.shipping.reset();
                        TwoCoInlineCart.shipping.setData(inline_params.shipping_address);

                        TwoCoInlineCart.cart.setSignature(inline_params.signature);
                        if(reloadWhenInlineClose)
                        if(cartClosedhandlerGuid === null) {
                            cartClosedhandlerGuid = TwoCoInlineCart.events.subscribe('cart:closed', function () {
                                window.location.reload();
                            });
                        }
                        TwoCoInlineCart.cart.checkout();
                    }
                } else {
                    console.log('Result status is error');
                    window.location.replace(result.redirect_link)
                }
            },
            error: function (e, t, n) {
                alert("Error in ajax post ".concat(e.statusText))
            }
        })
    };
var n = function () {
    $("#conditions-to-approve").find('input[type="checkbox"]').is(":checked") && $("#payment-confirmation").find('button[type="submit"]').attr("disabled", !1)
}

var loadInline = function (document, src, libName){
    console.log('Loading Inline script');
    var script = document.createElement('script');
    script.src = src;
    script.async = true;
    var firstScriptElement = document.getElementsByTagName('script')[0];
    script.onload = function () {
        window[libName].register();
    };
    firstScriptElement.parentNode.insertBefore(script, firstScriptElement);
}
