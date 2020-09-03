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

	$('#remise_percent').parent().append("<span class='addProposal'id='addProposal' name='addProposal'> <img id='proposalIcon' class='proposalIcon' data-discount='0' src='<?php print dol_buildpath("discountrules/img/object_discountrule.png",1) ?>'  alt='message'> </span>");
	var discountRulesCheckSelectCat = true;
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

function discountFetchOnEditLine(element, idLine, idProd,fkCompany,fkProject,fkCountry,defaultCustomerReduction) {

	if (idProd == undefined || $('#qty') == undefined) return 0;


	var lastidprod = 0;
	var lastqty = 0;
	var qty = $('#qty').val();

	if (idProd != lastidprod || qty != lastqty) {

		lastidprod = idProd;
		lastqty = qty;

		var urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php', 2); ?>";
		var sendData = {
			'get': "product-discount",
			'qty': qty,
			'id_line': idLine,
			'fk_product': idProd,
			'fk_company': fkCompany,
			'fk_project' : fkProject,
			'fk_country' : fkCountry,
			'defaultCustomerReduction' : defaultCustomerReduction,
		};


		$.ajax({
			method: "POST",
			url: urlInterface,
			dataType: 'json',
			data: sendData,
			success: function (data) {


				var $inputPriceHt = $('#price_ht');
				var $inputRemisePercent = $('#remise_percent');
				var discountTooltip = data.tpMsg;

				if(data.result && data.element === "discountrule") {

					if(data.subprice > 0){

						// application du prix de base
						$inputPriceHt.val(data.subprice);

						if(data.fk_product > 0) {
							$inputPriceHt.addClass("discount-rule-change --info");
						}
					}
				}
				else if(data.result && (data.element === "facture" || data.element === "commande" || data.element === "propal"  )) {
					$inputRemisePercent.val(data.tpMsg['reduction']);
					$inputRemisePercent.addClass("discount-rule-change --info");
					$inputPriceHt.val(data.subprice);
					$inputPriceHt.addClass("discount-rule-change --info");
				}
				else
				{
					if(defaultCustomerReduction > 0) {
						$inputPriceHt.removeClass("discount-rule-change --info");
						$inputRemisePercent.val(defaultCustomerReduction); // apply default customer reduction from customer card
						$inputRemisePercent.addClass("discount-rule-change --info");
					}
					else
					{
						$inputRemisePercent.val('');
						$inputPriceHt.removeClass("discount-rule-change --info");
						$inputRemisePercent.removeClass("discount-rule-change --info");
					}
				}

				// add tooltip message
				$("#proposalIcon").attr('data-discount', data.reduction);
				$("#addProposal").css("opacity",1) ;

				// add tooltip
				setToolTip($inputRemisePercent, discountTooltip);
				setToolTip($inputPriceHt, discountTooltip);
				setToolTip($('#addProposal'), discountTooltip);
				setToolTip($('#qty'), discountTooltip);

				// Show tootip
				if(data.result){
					$("#addProposal").tooltip("open");//  to explicitly show it here
					setTimeout(function() {
						$("#addProposal").tooltip("close" );
					}, 2000);
				}
			},
			error: function (err) {

			}
		});
	}

} // FormmUpdateLine

function initToolTip(element){

	if(!element.data("tooltipset")){
		element.data("tooltipset", true);
		element.tooltip({
			show: { collision: "flipfit", effect:"toggle", delay:50 },
			hide: { delay: 50 },
			tooltipClass: "mytooltip",
			content: function () {
				return $(this).prop("title");		/* To force to get title as is */
			}
		});
	}
}


function setToolTip($element, text){
	$element.attr("title",text);
	initToolTip($element);
}
