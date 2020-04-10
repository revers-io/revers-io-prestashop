{*
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 * International Registered Trademark & Property of INVERTUS, UAB
 *
 *}

<div id="configuration_form" class="defaultForm form-horizontal AdminPatterns">
	<div class="panel">
		<div class="form-wrapper">
			<div class="clearfix">
				<ul class="nav nav-tabs col-lg-11">
					{foreach $menuTabs as $tab}
						<li{if $tab.current} class="active"{/if}>
							<a href="{$tab.href|escape:'htmlall':'UTF-8'}">
								{$tab.name|escape:'htmlall':'UTF-8'}
							</a>
						</li>
					{/foreach}
				</ul>
			{*</div>*}
		{*</div>*}
