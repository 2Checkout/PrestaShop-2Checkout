<script>
    {literal}
    $(document).ready(function () {
        var tco_prods_list = '{/literal}{$tco_refund_products_list}{literal}';
        var not_all_products_alert = '{/literal}{$not_all_products_alert}{literal}';
        var has_paid_shipping = '{/literal}{$has_paid_shipping}{literal}';
        var shipping_msg_alert = '{/literal}{$shipping_msg_alert}{literal}';
        var obj = jQuery.parseJSON(tco_prods_list);
        $(document).on('click', '.return-product-display', function () {

            $.each(obj, function (key, value) {
                if ($('#cancel_product_selected_' + key).length != 0) {
                    $('#cancel_product_selected_' + key).trigger('click');
                    $('#cancel_product_quantity_' + key).attr('readonly', 'readonly');
                }
            });
            if ($('#cancel_product_shipping').length != 0) {
                $('#cancel_product_shipping').trigger('click');
            }
        });

        $(document).on('click', '#cancel_product_save', function (e) {
            var selectedProducts = $('input[id^="cancel_product_selected_"]:checked').length;
            if (Object.entries(obj).length > 0) {
                if (selectedProducts != Object.entries(obj).length) {
                    alert(not_all_products_alert);
                    e.preventDefault();
                }
            }
            //check for shipping
            if (has_paid_shipping && $('input[id="cancel_product_shipping"]:checked').length != 1) {
                alert(shipping_msg_alert);
                e.preventDefault();
            }

        });
    });
    {/literal}
</script>
