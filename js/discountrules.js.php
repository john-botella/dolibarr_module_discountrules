<?php
/* Copyright (C) 2018 John BOTELLA
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');


/**
 * \file    js/discountrules.js.php
 * \ingroup discountrules
 * \brief   JavaScript file for module discountrules.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/../main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules@discountrules","other"));
?>

/* Javascript library of module discountrules */
$( document ).ready(function() {

	var discountRulesCheckSelectCat = true;

	//$("[name='qty']").change(function() {
	//
	//let FormmUpdateLine = 	!document.getElementById("addline");
	//// si nous sommes dans le formulaire Modification
	//if (FormmUpdateLine){
	//	discountUpdate($(this));
	//
	//	function discountUpdate(self){
	//
	//		var productId =  self.closest('tr').children('td').find("[name='productid']")[0];
	//		var lineId =  self.closest('tr').children('td').find("[name='lineid']")[0];
	//		//var productType =  self.closest('tr').children('td').find("[name='type']")[0];
	//
	//		console.log(productId.value);
	//		console.log(lineId.value);
	//		console.log($('#qty').val());
	//
	//		//alert(Pi.value)
	//		if(productId == undefined || $('#qty') == undefined ){  return 0; }
	//
	//		var lastidprod = 0;
	//		var lastqty = 0;
	//		var idprod = productId.value;
	//		var qty = $('#qty').val();
	//
	//		if(idprod != lastidprod || qty != lastqty)
	//		{
	//
	//			lastidprod = idprod;
	//			lastqty = qty;
	//
	//			var urlInterface = "<?php //print dol_buildpath('discountrules/scripts/interface.php',2); ?>//";
	//			// la compagnie et la soc  doivent être récuperées via interface ...
	//			data = {
	//				'get': "product-discount-update",
	//				'qty': qty,
	//				'fk_product': idprod,
	//				'type' : productType,
	//			};
	//
	//
	//			$.ajax({
	//				method: "POST",
	//				url: urlInterface,
	//				dataType: 'json',
	//				data: data,
	//				success: function (data) {
	//					if(!data.error) {
	//
	//					}else {
	//
	//					}
	//				},
	//				error: function (err) {
	//
	//				}
	//			})
	//
	//			//$.ajax({
	//			//	method: "POST",
	//			//	url: urlInterface,
	//			//	dataType: 'json',
	//			//	data: data
	//			//})
	//			//	.done(function( data ) {
	//			//		var $inputPriceHt = $('#price_ht');
	//			//		var $inputRemisePercent = $('#remise_percent');
	//			//		var discountTooltip = "<?php ////print $langs->transnoentities('Discountrule'); ?>//// :<br/>";
	//			//
	//			//
	//			//		if(data.result && data.element === "discountrule")
	//			//		{
	//			//			$inputRemisePercent.val(data.reduction);
	//			//			$inputRemisePercent.addClass("discount-rule-change --info");
	//			//			discountTooltip = discountTooltip + '<strong>' + data.label + '</strong>';
	//			//
	//			//			if (data.fk_project > 0){
	//			//				discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('InfosProject'); ?>//// : " + data.match_on.project;
	//			//			}
	//			//
	//			//			if(data.subprice > 0){
	//			//
	//			//				// application du prix de base
	//			//				$inputPriceHt.val(data.subprice);
	//			//
	//			//				if(data.fk_product > 0) {
	//			//					$inputPriceHt.addClass("discount-rule-change --info");
	//			//					if (data.product_price > 0) {
	//			//						discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('Price'); ?>//// : " + data.product_price;
	//			//					} else {
	//			//						discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('Price'); ?>//// : --";
	//			//					}
	//			//
	//			//					if (data.product_reduction_amount > 0) {
	//			//						discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('ReductionAmount'); ?>//// : -" + data.product_reduction_amount;
	//			//					}
	//			//				}
	//			//			}
	//			//
	//			//
	//			//			discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('Discount'); ?>//// : " +  data.reduction + "%"
	//			//				+ "<br/><?php ////print $langs->transnoentities('FromQty'); ?>//// : " +   data.from_quantity
	//			//				+ "<br/><?php ////print $langs->transnoentities('ThirdPartyType'); ?>//// : " +   data.typentlabel;
	//			//
	//			//			if(data.fk_product > 0) {
	//			//				discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('Product'); ?>//// : " + data.match_on.product_info;
	//			//			}
	//			//			else{
	//			//				discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('ProductCategory'); ?>//// : " + data.match_on.category_product;
	//			//			}
	//			//
	//			//
	//			//			discountTooltip = discountTooltip + "<br/><?php ////print $langs->transnoentities('ClientCategory'); ?>//// : " +   data.match_on.category_company
	//			//				+ "<br/><?php ////print $langs->transnoentities('Customer'); ?>//// : " +   data.match_on.company;
	//			//
	//			//
	//			//			if(idprod > 0 && data.standard_product_price > 0){
	//			//				discountTooltip = discountTooltip + "<br/><br/><strong><?php ////print $langs->transnoentities('InfosProduct'); ?>////</strong><br/><?php ////print $langs->transnoentities('ProductPrice'); ?>//// : " +  data.standard_product_price;
	//			//			}
	//			//		}
	//			//		else if(data.result
	//			//			&& (data.element === "facture" || data.element === "commande" || data.element === "propal"  )
	//			//		)
	//			//		{
	//			//			$inputRemisePercent.val(data.reduction);
	//			//			$inputRemisePercent.addClass("discount-rule-change --info");
	//			//			$inputPriceHt.val(data.subprice);
	//			//			$inputPriceHt.addClass("discount-rule-change --info");
	//			//			discountTooltip = discountTooltip + data.label
	//			//				+ "<br/><?php ////print $langs->transnoentities('Price'); ?>//// : " +  data.subprice
	//			//				+ "<br/><?php ////print $langs->transnoentities('Discount'); ?>//// : " +  data.reduction + "%"
	//			//				+ "<br/><?php ////print $langs->transnoentities('Date'); ?>//// : " +   data.date_object_human
	//			//				+ "<br/><?php ////print $langs->transnoentities('Qty'); ?>//// : " +   data.qty
	//			//			;
	//			//		}
	//			//		else
	//			//		{
	//			//			if(defaultCustomerReduction>0)
	//			//			{
	//			//				$inputPriceHt.removeClass("discount-rule-change --info");
	//			//				$inputRemisePercent.val(defaultCustomerReduction); // apply default customer reduction from customer card
	//			//				$inputRemisePercent.addClass("discount-rule-change --info");
	//			//				discountTooltip = discountTooltip
	//			//					+ "<?php ////print $langs->transnoentities('percentage'); ?>//// : " +  defaultCustomerReduction + "%"
	//			//					+ "<br/>"  +  "<?php ////print $langs->transnoentities('DiscountruleNotFoundUseCustomerReductionInstead'); ?>////"
	//			//				;
	//			//			}
	//			//			else
	//			//			{
	//			//				$inputRemisePercent.val('');
	//			//				$inputPriceHt.removeClass("discount-rule-change --info");
	//			//				$inputRemisePercent.removeClass("discount-rule-change --info");
	//			//				discountTooltip = discountTooltip +  "<?php ////print $langs->transnoentities('DiscountruleNotFound'); ?>////";
	//			//			}
	//			//		}
	//			//
	//			//		// add tooltip message
	//			//		$inputRemisePercent.attr("title", discountTooltip);
	//			//		$inputPriceHt.attr("title", discountTooltip);
	//			//
	//			//		// add tooltip
	//			//		if(!$inputRemisePercent.data("tooltipset")){
	//			//			$inputRemisePercent.data("tooltipset", true);
	//			//			$inputRemisePercent.tooltip({
	//			//				show: { collision: "flipfit", effect:"toggle", delay:50 },
	//			//				hide: { delay: 50 },
	//			//				tooltipClass: "mytooltip",
	//			//				content: function () {
	//			//					return $(this).prop("title");		/* To force to get title as is */
	//			//				}
	//			//			});
	//			//		}
	//			//
	//			//		if(!$inputPriceHt.data("tooltipset")){
	//			//			$inputPriceHt.data("tooltipset", true);
	//			//			$inputPriceHt.tooltip({
	//			//				show: { collision: "flipfit", effect:"toggle", delay:50 },
	//			//				hide: { delay: 50 },
	//			//				tooltipClass: "mytooltip",
	//			//				content: function () {
	//			//					return $(this).prop("title");		/* To force to get title as is */
	//			//				}
	//			//			});
	//			//		}
	//			//
	//			//		// Show tootip
	//			//		if(data.result){
	//			//			$inputRemisePercent.tooltip().tooltip( "open" ); //  to explicitly show it here
	//			//			setTimeout(function() {
	//			//				$inputRemisePercent.tooltip().tooltip("close" );
	//			//			}, 2000);
	//			//		}
	//			//	});
	//
	//
	//
	//
	//
	//		}
	//
	//	}
	//}
	//
	//});



    $("[name='massaction']").change(function() {

    	if($(this).val() == 'addtocategory' || $(this).val() == 'removefromcategory' )
    	{
    		var catinput = $('#select_categ_search_categ');
    		if(catinput != undefined)
    		{
    			// set error
    			catinput.get(0).setCustomValidity('<?php print $langs->transnoentitiesnoconv('CategoryNotSelected'); ?>');
    			discountRulesCheckSelectCat = false;
    		}
    	}
    	else
    	{
			// reset error
			$(this).get(0).setCustomValidity('');
			discountRulesCheckSelectCat = true;
    	}
    });
	$('#select_categ_search_categ').change(function() {
		if(!discountRulesCheckSelectCat && $(this).val() > 0)
		{
			// reset error
			$(this).get(0).setCustomValidity('');
			discountRulesCheckSelectCat = true;
		}
	});
});
