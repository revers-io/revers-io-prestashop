{**
* Copyright (c) 2019 Revers.io
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is furnished
* to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*
* @author revers.io
* @copyright Copyright (c) permanent, Revers.io
* @license   Revers.io
* @see       /LICENSE
*}

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
    <p class="help-block">{l s='Start typing to see suggestions' mod='reversio'}</p>
</div>
