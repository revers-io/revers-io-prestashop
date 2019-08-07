<div class="col-lg-5">
    <select class="chosen searchable-multiselect fixed-width-xl" name="orders_status[]" multiple>
        {foreach $options as $option}
            {if empty($ordersStatuses)}
                <option value="{$option['id_order_state']}"> {$option['name']}</option>
            {/if}
        {/foreach}

        {foreach $ordersStatuses as $statuses}
            <option value="{$statuses['id_order_state']}" {if $statuses['selected'] === 'yes'} selected="selected"{/if}> {$statuses['name']}</option>
        {/foreach}
    </select>
</div>
<br>
<br>
<div>
    <p class="help-block">{l s='Start typing to see suggestions' mod='fruugo'}</p>
</div>
