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

<div class="accordion-group category-map-part">
    <div class="js-accordion-heading accordion-heading heading-container">
        <a class="js-accordion-toggle accordion-toggle category-map-heading collapsed" data-toggle="collapse" href="#{$category.link_rewrite}" data-category-id="{$category.id_category}">
            {if isset($category.children)}
                <i class="icon-circle-arrow-down"></i>
            {/if}
            {$category.name}
        </a>
    </div>

    <div class="model-list-container">
        <select class="model-list-select chosen" name="{$category.id_category}-modelList">
            <option value="0" {if $category.modelType == 0}selected{/if}>
                {l s='Select model type' mod='reversio'}
            </option>
            {html_options options=$modelTypesList selected=$category.modelType}
        </select>
    </div>

    <div class="reversio-category-childrens-{$category.id_category}">
    </div>
</div>
