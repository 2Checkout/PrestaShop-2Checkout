<!-- Header part menu -->
<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
</head>

<body>
{hook h='displayAfterBodyOpeningTag'}
<main>
    <!-- Menu part-->
    <header id="header">
        {block name='header'}
            {include file='_partials/header.tpl'}
        {/block}
    </header>

    <!-- Header part ends -->

    <section id="wrapper">
        <div class="container">

            <section id="main">
                <section id="content" class="page-content card card-block">
                    {include file='_partials/breadcrumb.tpl'}
                    {if $warn_msg }
                        <h2>{l s='Payment Warning' mod='twocheckout'}</h2>
                    {else}
                        <h2>{l s='Payment Error' mod='twocheckout'}</h2>
                    {/if}

                    <div class="table-responsive-row clearfix">
                        {if $error_msg }
                            <div class="alert alert-danger" role="alert">
                                <p class="twocheckout_error_msg">
                                    <br>
                                    <span class="long">{l s='Error message : '
                                        mod='twocheckout'}{$error_msg|escape:'htmlall':'UTF-8'}</span>
                                </p>
                            </div>
                        {/if}
                        {if $warn_msg }
                            <div class="alert alert-warning" role="alert">
                                <p class="twocheckout_warning_msg">
                                    <br>
                                    <span class="long">{l s='Message : '
                                        mod='twocheckout'}{$warn_msg|escape:'htmlall':'UTF-8'}</span>
                                </p>
                            </div>
                        {/if}
                        {if $show_retry}
                            <a class="btn btn-secondary" href="{$link->getPageLink('order', true)}">{l s='Try to pay
                            again' mod='twocheckout'}</a>
                        {/if}
                    </div>
                </section>
            </section>
        </div>
    </section>
    <!-- Footer starts -->

    <footer id="footer">
        {block name="footer"}
            {include file="_partials/footer.tpl"}
        {/block}
    </footer>
    <!-- Footer Ends -->
    {block name='javascript_bottom'}
        {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
    {/block}
    {hook h='displayBeforeBodyClosingTag'}
</main>

</body>

