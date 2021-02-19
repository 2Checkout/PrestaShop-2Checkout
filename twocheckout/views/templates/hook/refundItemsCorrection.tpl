<script>
    {literal}
    $(document).ready(function() {
        var prods_list = '{/literal}{$tco_refund_products_list}{literal}';
        var not_all_products_alert = '{/literal}{$not_all_products_alert}{literal}';

        var obj = jQuery.parseJSON(prods_list);
        $(document).on('click', '#desc-order-standard_refund', function () {

            $.each(obj, function(key,value) {
                if ( document.getElementById('id_order_detail['+key+']') != null ){
                    document.getElementById('id_order_detail['+key+']').checked = true;
                }
                if ($('#cancelQuantity_'+key).length != 0){
                    $('#cancelQuantity_'+key).val(value);
                    $('#cancelQuantity_'+key).prop("readonly", true) ;
                }
            });
        });

        $(document).on('click', 'input[name="cancelProduct"]', function (e) {
            var selectedProducts = $('input[name^="id_order_detail"]:checked').length;
            if (Object.entries(obj).length > 0) {
                if (selectedProducts != Object.entries(obj).length) {
                    alert(not_all_products_alert);
                    e.preventDefault();
                }
            }

        });
    });
    {/literal}
</script>
