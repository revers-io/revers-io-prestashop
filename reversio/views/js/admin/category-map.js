/*
 *Copyright (c) 2019 Revers.io
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
 */

$(document).ready(function () {
    $('body').on('click', '.js-accordion-toggle.accordion-toggle.category-map-heading.collapsed', displayCategories);
    $('body').on('click', '.js-accordion-toggle.accordion-toggle.category-map-heading.do-collapsed', hideCategories);
    $('body').on('click', '.js-accordion-toggle.accordion-toggle.category-map-heading.collapsed-again', displayHideCategories);

    function displayCategories() {
        var $category = $(this);

        var categoryId = $category.attr('data-category-id');

        $.ajax(categoryDisplayAjax, {
            method: 'POST',
            data: {
                action: 'displayCategories',
                ajax: 1,
                categoryId: categoryId,
                current: currentIndex,
                token_bo: token_bo,
            },
            success: function (response) {
                $('.reversio-category-childrens-'+categoryId).html(response);
                $category.addClass('do-collapsed');
                $('.model-list-select.chosen').each(function(k, item){
                    $(item).chosen({disable_search_threshold: 10, search_contains: true, width: '100%', });
                });
            },
            error: function (response) {
            }
        });
    }

    function hideCategories() {
        var $category = $(this);

        var categoryId = $category.attr('data-category-id');
        $('.reversio-category-childrens-'+categoryId).hide();
        $category.removeClass('do-collapsed');
        childNodes = $category[0].childNodes;
        for (var i = 0; i < childNodes.length; i++) {
            if (childNodes[i].nodeType !== 3) {
                childNodes[i].className = "icon-circle-arrow-right";
            }
        }
        $category.addClass('collapsed-again');
    }

    function displayHideCategories() {
        var $category = $(this);

        var categoryId = $category.attr('data-category-id');
        $('.reversio-category-childrens-'+categoryId).show();
        $category.removeClass('collapsed collapsed-again');
        childNodes = $category[0].childNodes;
        for (var i = 0; i < childNodes.length; i++) {
            if (childNodes[i].nodeType !== 3) {
                childNodes[i].className = "icon-circle-arrow-down";
            }
        }
        $category.addClass('do-collapsed');
    }
});
