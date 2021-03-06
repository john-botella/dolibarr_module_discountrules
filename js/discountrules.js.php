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

$translateList = array('Saved', 'errorAjaxCall');

$translate = array();
foreach ($translateList as $key){
	$translate[$key] = $langs->transnoentities($key);
}

if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
if ($thousand == 'None') $thousand = '';
elseif ($thousand == 'Space') $thousand = ' ';

$confToJs = array(
	'MAIN_MAX_DECIMALS_TOT' => $conf->global->MAIN_MAX_DECIMALS_TOT,
	'MAIN_MAX_DECIMALS_UNIT' => $conf->global->MAIN_MAX_DECIMALS_UNIT,
	'dec' => $dec,
	'thousand' => $thousand,
);


// BE CAREFULL : According to Dolibarr version there is 2 kind of category imput : single select or multiselect
if(intval(DOL_VERSION) > 10){
	// Use an multiselect field
	$catImput = "search_category_product_list";
}
else{
	// Use an single select field
	$catImput = "select_categ_search_categ";
}

?>
// LANGS
var discountlang = <?php print json_encode($translate) ?>;
var discountConfig = <?php print json_encode($confToJs) ?>;
var discountDialogCountAddedProduct = 0;

/* Javascript library of module discountrules */
$( document ).ready(function() {



	/***************************************************************/
	/* Lors de la modification de ligne ajout du bouton de remise  */
	/***************************************************************/
	$('#remise_percent').parent().append('<span class="suggest-discount --disable" id="suggest-discount"></span>');


	/*****************************************************/
	/* Pour les actions en masse sur liste des produits  */
	/*****************************************************/
	var discountRulesCheckSelectCat = true;

    $("[name='massaction']").change(function() {

    	if($(this).val() == 'addtocategory' || $(this).val() == 'removefromcategory' )
    	{
    		var catinput = $('#<?php echo $catImput; ?>');
    		if(catinput != undefined)
    		{
    			if(catinput.val() == ""){
					// set error
					catinput.get(0).setCustomValidity('<?php print $langs->transnoentitiesnoconv('CategoryNotSelected'); ?>');
					discountRulesCheckSelectCat = false;
				}
    		}
    	}
    	else
    	{
			// reset error
			$(this).get(0).setCustomValidity('');
			discountRulesCheckSelectCat = true;
    	}
    });

	$('#<?php echo $catImput; ?>').change(function() {
		if(!discountRulesCheckSelectCat && $(this).val() != "")
		{
			// reset error
			$(this).get(0).setCustomValidity('');
			discountRulesCheckSelectCat = true;
		}
	});

	/****************************************************************/
	/* recherche de produit rapide sur formulaire d'ajout de ligne  */
	/****************************************************************/
	$(document).on("submit", "#product-search-dialog-form" , function(event) {
		event.preventDefault();
		discountLoadSearchProductDialogForm("&"+$( this ).serialize());
	});

	//_________________________________________________
	// RECHERCHE GLOBALE AUTOMATIQUE SUR FIN DE SAISIE
	// (Uniquement sur la recherche globale)

	//setup before functions
	var typingProductSearchTimer;                //timer identifier
	var doneTypingProductSearchInterval = 2000;  //time in ms (2 seconds)

	$(document).on("keyup", "#search-all-form-input" , function(event) {
		clearTimeout(typingProductSearchTimer);
		if ($('#search-all-form-input').val()) {
			typingProductSearchTimer = setTimeout(function(){
				discountLoadSearchProductDialogForm("&"+$( "#product-search-dialog-form" ).serialize());
			}, doneTypingProductSearchInterval);
		}
	});

	// Update prices display
	$(document).on("change", ".on-update-calc-prices" , function(event) {
		let fk_product = $(this).attr("data-product");
		updateLinePricesCalcs(fk_product)
	});

	$(document).on("keyup", ".on-update-calc-prices" , function(event) {
		let fk_product = $(this).attr("data-product");
		updateLinePricesCalcs(fk_product)
	});

	//_______________
	// LA DIALOG BOX

	// Ajout de produits sur click du bouton
	$(document).on("click", ".discount-prod-list-action-btn" , function(event) {
		event.preventDefault();
		var fk_product = $(this).attr("data-product");
		addProductToCurentDocument(fk_product);
	});

	// Ajout de produits sur click du bouton
	$(document).on("keydown", ".discount-prod-list-input-qty" , function(event) {
		if(event.keyCode == 13) {
			event.preventDefault();
			var fk_product = $(this).closest('tr').attr("data-product");
			addProductToCurentDocument(fk_product);
			return false;
		}
	});



	$(document).on("click", '#product-search-dialog-button', function(event) {
		event.preventDefault();

		var element = $(this).attr('data-target-element');
		var fk_element = $(this).attr('data-target-id');

		var productSearchDialogBox = "product-search-dialog-box";
		// crée le calque qui sera convertie en popup
		$('body').append('<div id="'+productSearchDialogBox+'" title="<?php print $langs->transnoentities('SearchProduct'); ?>"></div>');

		// transforme le calque en popup
		var popup = $('#'+productSearchDialogBox).dialog({
			autoOpen: true,
			modal: true,
			width: Math.min($( window ).width() - 50, 1700),
			dialogClass: 'discountrule-product-search-box',
			buttons: [
				{
					text: "<?php print $langs->transnoentities('CloseDialog'); ?>",
					"class": 'ui-state-information',
					click: function () {
						$(this).dialog("close");
						$('#'+productSearchDialogBox).remove();
					}
				}
			],
			close: function( event, ui ) {
				if(discountDialogCountAddedProduct>0){
					// si une ligne a été ajoutée, recharge la page actuelle
					document.location.reload();
				}
			},
			open: function( event, ui ) {
				discountLoadSearchProductDialogForm("&element="+element+"&fk_element="+fk_element);
			}
		});
	});
});


/*******************/
/* LES LIBRAIRIES  */
/*******************/

function discountLoadSearchProductDialogForm(morefilters = ''){
	var productSearchDialogBox = "product-search-dialog-box";
	$('#'+productSearchDialogBox).load( "<?php print dol_buildpath('discountrules/scripts/interface.php',1)."?action=product-search-form"; ?>" + morefilters, function() {
		discountDialogCountAddedProduct = 0; // init count of product added for reload action
		focusAtEndSearchInput($("#search-all-form-input"));
		$('#'+productSearchDialogBox).dialog( "option", "position", { my: "center", at: "center", of: window } ); // Hack to center vertical the dialog box after ajax load
		initToolTip($('#'+productSearchDialogBox+' .classfortooltip')); // restore tooltip after ajax call
	});
}


function discountFetchOnEditLine(element, idLine, idProd,fkCompany,fkProject,fkCountry) {

	if (idProd == undefined || $('#qty') == undefined) return 0;


	var lastidprod = 0;
	var lastqty = 0;
	var qty = $('#qty').val();

	if (idProd != lastidprod || qty != lastqty) {

		lastidprod = idProd;
		lastqty = qty;

		var urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php', 2); ?>";
		var sendData = {
			'action': "product-discount",
			'qty': qty,
			'id_line': idLine,
			'fk_product': idProd,
			'fk_company': fkCompany,
			'fk_project' : fkProject,
			'fk_country' : fkCountry
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
					$("#suggest-discount").attr('data-discount', data.reduction);
					$("#suggest-discount").attr('data-subprice', data.subprice);

					$("#suggest-discount").removeClass("--disable");
					$("#suggest-discount").addClassReload("--dr-rotate-icon");
				}
				else if(data.result && (data.element === "facture" || data.element === "commande" || data.element === "propal"  )) {
					$("#suggest-discount").attr('data-discount', data.reduction);
					$("#suggest-discount").attr('data-subprice', data.subprice);

					$("#suggest-discount").removeClass("--disable");
					$("#suggest-discount").addClassReload("--dr-rotate-icon");
				}
				else
				{ // pas de discounRule

					$("#suggest-discount").attr('data-discount', $inputRemisePercent.val());
					$("#suggest-discount").attr('data-subprice', $inputPriceHt.val());

					if(defaultCustomerReduction > 0) {
						$("#suggest-discount").attr('data-discount', defaultCustomerReduction).removeClass("--disable --dr-rotate-icon");
					} else {
						$("#suggest-discount").addClass("--disable").removeClass("--dr-rotate-icon");
					}
				}

				// add tooltip message
				setToolTip($('#suggest-discount'),"<?php print dol_escape_js($langs->transnoentities('actionClickMeDiscountrule', '<span class="suggest-discount"></span>')); ?><br/><br/>"+ discountTooltip);

				// Show tootip
				if(data.result){
					$("#suggest-discount").tooltip("open");//  to explicitly show it here
					setTimeout(function() {
						$("#suggest-discount").tooltip("close" );
					}, 2000);
				}
			},
			error: function (err) {

			}
		});
	}

} // FormmUpdateLine
/**
 * initialisation de la tootip
 * @param element
 */
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

/**
 * permet de faire un addClass qui reload les animations si la class etait deja la
 */
(function ( $ ) {
	$.fn.addClassReload = function(className) {
		return this.each(function() {
			var $element = $(this);
			// Do something to each element here.
			$element.removeClass(className).width;
			setTimeout(function(){ $element.addClass(className); }, 0);
		});
	};
}( jQuery ));


/**
 * affectation du contenu dans l'attribut title
 *
 * @param $element
 * @param text
 */
function setToolTip($element, text){
	$element.attr("title",text);
	initToolTip($element);
}


function discountRule_setEventMessage(msg, status = true){

	if(msg.length > 0){
		if(status){
			$.jnotify(msg, 'notice', {timeout: 5},{ remove: function (){} } );
		}
		else{
			$.jnotify(msg, 'error', {timeout: 0, type: 'error'},{ remove: function (){} } );
		}
	}
	else{
		$.jnotify('ErrorMessageEmpty', 'error', {timeout: 0, type: 'error'},{ remove: function (){} } );
	}
}

/**
 * Positionne le focus et le curseur à la fin de l'input
 * @param {jQuery} $searchAllInput
 */
function focusAtEndSearchInput($searchAllInput){
	let searchAllInputVal = $searchAllInput.val();
	$searchAllInput.blur().focus().val('').val(searchAllInputVal);
}

/**
 *
 * @param fk_product
 */
function addProductToCurentDocument(fk_product){
	var urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php', 2); ?>";

	// disable action button during add
	disableAddProductFields(fk_product, true);

	var sendData = {
		'action': "add-product",
		'fk_product': fk_product,
		'qty': $("#discount-prod-list-input-qty-"+fk_product).val(),
		'subprice': $("#discount-prod-list-input-subprice-"+fk_product).val(),
		'reduction': $("#discount-prod-list-input-reduction-"+fk_product).val(),
		'fk_element': $("#discountrules-form-fk-element").val(),
		'element': $("#discountrules-form-element").val()
	};

	// check if supplier price exist because it could be not activated
	let fk_fournprice = $("#prodfourprice-" + fk_product);
	if(fk_fournprice.length > 0){
		sendData.fk_fournprice = fk_fournprice.val();
	}
//console.log(sendData);
	$.ajax({
		method: "POST",
		url: urlInterface,
		dataType: 'json',
		data: sendData,
		success: function (data) {
			if(data.result) {
				//$("#discount-prod-list-input-qty-"+fk_product).val(1);
			}
			else
			{

			}
			discountDialogCountAddedProduct++;
			focusAtEndSearchInput($("#search-all-form-input"));

			discountRule_setEventMessage(data.msg, data.result);

			// re-enable action button
			disableAddProductFields(fk_product, false);
		},
		error: function (err) {
			discountRule_setEventMessage(discountlang.errorAjaxCall, false);

			// re-enable action button
			disableAddProductFields(fk_product, false);
		}
	});
}

/**
 * Permet de désactiver/activer les inputs/bouttons de formulaire d'une ligne de produit et ajoute quelques animations
 * @param fk_product
 * @param disable
 */
function disableAddProductFields(fk_product, disable = true){

	var timer = 0
	if(!disable){
		timer = 1000; // Add timer on reactivate to avoid doubleclick
	}

	var buttonAddProduct = $(".discount-prod-list-action-btn[data-product="+fk_product+"]");

	setTimeout(function() {
		if(!disable){
			timer = 500; // Add timer on reactivate to avoid doubleclick
			buttonAddProduct.find('.add-btn-icon').removeClass('fa-spinner fa-pulse').addClass('fa-plus');
		}else{
			buttonAddProduct.find('.add-btn-icon').removeClass('fa-plus').addClass('fa-spinner fa-pulse');
		}

		$("#discount-prod-list-input-qty-"+fk_product).prop("disabled",disable);
		buttonAddProduct.prop("disabled",disable);
		$("#discount-prod-list-input-subprice-"+fk_product).prop("disabled",disable);
		$("#discount-prod-list-input-reduction-"+fk_product).prop("disabled",disable);

		// check if fournprice exist becaus it could be not activated
		let fk_fournprice = $("#prodfourprice-" + fk_product);
		if(fk_fournprice.length > 0){
			fk_fournprice.prop("disabled",disable);
		}

	}, timer);
}

/**
 * Met a jour les calcules de prix basé sur les données des input
 * @param fk_product
 */
function updateLinePricesCalcs(fk_product){
	 //discountConfig.MAIN_MAX_DECIMALS_TOT
	 //discountConfig.MAIN_MAX_DECIMALS_UNIT
	 //discountConfig.dec
	 //discountConfig.thousand

	inputQty = $("#discount-prod-list-input-qty-"+fk_product);
	inputSubPrice = $("#discount-prod-list-input-subprice-"+fk_product);
	inputReduction = $("#discount-prod-list-input-reduction-"+fk_product);


	let qty = Number(inputQty.val());
	let subPrice = Number(inputSubPrice.val());
	let reduction = Number(inputReduction.val());
	if(reduction>100){
		reduction = 100;
		inputReduction.val(reduction);
	}

	let finalUnitPrice = subPrice - (subPrice * reduction / 100);
	finalUnitPrice = Number(finalUnitPrice.toFixed(discountConfig.MAIN_MAX_DECIMALS_UNIT));

	let finalPrice = finalUnitPrice*qty;
	finalPrice = Number(finalPrice.toFixed(discountConfig.MAIN_MAX_DECIMALS_TOT));

	$("#discount-prod-list-final-subprice-"+fk_product).html(finalUnitPrice.toLocaleString(undefined, { minimumFractionDigits: discountConfig.MAIN_MAX_DECIMALS_TOT, maximumFractionDigits: discountConfig.MAIN_MAX_DECIMALS_UNIT }));
	$("#discount-prod-list-final-price-"+fk_product).html(finalPrice.toLocaleString(undefined, { minimumFractionDigits: discountConfig.MAIN_MAX_DECIMALS_TOT }));
}
