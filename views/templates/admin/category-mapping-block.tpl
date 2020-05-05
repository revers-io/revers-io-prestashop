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

<form action="{$current|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data">
    <div class="panel col-lg-12">
        <div class="panel-heading">{l s='Category mapping' mod='reversio'}</div>

        <div class="alert alert-info">
            {l s='Before you start managing your returns with Revers.io module you should first map your product catalog with the Revers.io catalog.' mod='reversio'}
            {l s='The mapping allows you to: offer to your Customer a list of return reasons specific for each category of product, suggest relevant transport options depending on product characteristics (weight, dimensions...) etc.' mod='reversio'}
            {l s='Simply choose for each category of your catalog the most relevant option from the dropdown list.' mod='reversio'}
            {l s='Please note that there is an inheritance mechanism: if you have mapped parent categories, but haven\'t mapped their child subcategories, they will inherit values from their parents.' mod='reversio'}
            {l s='It is especially useful for Sellers with big catalogs, as it saves time and enables a quick start.' mod='reversio'}
        </div>

        <div class="table-responsive-row clearfix">
            <div class="category-map-container accordion js-category-container">
                {foreach $categoryTree as $category}
                    {include file='./partials/category-mapping-part.tpl' category=$category}
                {/foreach}
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right" name="submitCategoryMapping">
                <i class="process-icon-save"></i>
                {l s='Save' mod='reversio'}
            </button>
        </div>
    </div>
</form>
