$(document).ready(function () {
    n()
}), window.TwocheckoutCPCheckout = function () {
    $.ajax({
        url: tco_verify_url,
        type: "GET",
        success: function (result) {
            if (n(), result.status === 'success') {
                window.location.replace(result.redirect_link)
            } else {
                window.location.replace(result.redirect_link)
            }
        },
        error: function (e, t, n) {
            alert("Error in ajax post ".concat(e.statusText))
        }
    })
}
var n = function () {
    $("#conditions-to-approve").find('input[type="checkbox"]').is(":checked") && $("#payment-confirmation").find('button[type="submit"]').attr("disabled", !1)
}
