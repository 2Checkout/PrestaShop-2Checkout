<script>
    {literal}
    $(document).ready(() => {
        var prods_list = '{/literal}{$tco_refund_products_list}{literal}';
        $(document).on('click', '#desc-order-standard_refund', function () {
            var obj = jQuery.parseJSON(prods_list);
            $.each(obj, function(key,value) {
                if ($('#cancelQuantity_'+key).length != 0){
                    $('#cancelQuantity_'+key).val(value);
                    $('#cancelQuantity_'+key).prop("readonly", true) ;
                }
            });
        });
    });
    {/literal}
</script>
