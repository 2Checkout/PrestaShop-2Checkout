<script>
  {literal}
    $(document).ready(() => {
        $('#desc-order-partial_refund').attr('href', '#');
        $('#desc-order-partial_refund').unbind("dblclick");
        $('#desc-order-partial_refund').unbind("click");
        $('#desc-order-partial_refund').remove();
    });
  {/literal}
</script>
