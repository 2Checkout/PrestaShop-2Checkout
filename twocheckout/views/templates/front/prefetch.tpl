{if isset($resources) && is_array($resources) && empty($resources) == false}
    {foreach from=$resources item=resource}
      <link href="{$resource|addslashes}" rel="prefetch" as="script">
    {/foreach}
{/if}

