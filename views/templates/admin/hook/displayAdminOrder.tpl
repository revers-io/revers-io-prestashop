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

<!-- Revers.io block -->
<div id="formReversIoBlock" class="panel">
    <div class="panel-heading">
        <i class="icon-refresh"></i>
        {l s="Revers.io" mod='reversiointegration'}
    </div>
    <div>
        <div class="table-responsive">
            <table class="table reversio row-margin-bottom">
                <tbody>
                    <tr>
                        <td style="background-color:#DC143C;color:white">{l s="Error."  mod='reversiointegration'}
                            <a href="{$logLink}">{l s="Click here" mod='reversiointegration'}</a>
                            {l s=" to go to error log." mod='reversiointegration'}
                        </td>
                        <td style="background-color:#DC143C;color:white">{dateFormat date=$logCreated full=true}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
