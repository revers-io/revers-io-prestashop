{extends file="helpers/options/options.tpl"}

{block name="input" append}
    {if $field['type'] == 'orders_status'}
        <div class="col-lg-5 {if isset($field['class'])}{$field['class']}{/if}">
            {include file="../../../partials/orders-statuses.tpl"}
        </div>
    {/if}
{/block}
