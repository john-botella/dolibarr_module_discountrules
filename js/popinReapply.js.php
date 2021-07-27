<?php

//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');


/**
 * \file    js/advancedproductsearch.js.php
 * \ingroup advancedproductsearch
 * \brief   JavaScript file for module advancedproductsearch.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include(substr($tmp, 0, ($i + 1)) . "/main.inc.php");
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include(substr($tmp, 0, ($i + 1)) . "/../main.inc.php");
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (!$res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules@discountrules", "other"));

$translateList = array('Saved', 'errorAjaxCall');

$translate = array();
foreach ($translateList as $key) {
	$translate[$key] = $langs->transnoentities($key);
}

if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal") $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
if ($thousand == 'None') $thousand = '';
elseif ($thousand == 'Space') $thousand = ' ';

$confToJs = array(
	'MAIN_MAX_DECIMALS_TOT' => $conf->global->MAIN_MAX_DECIMALS_TOT,
	'MAIN_MAX_DECIMALS_UNIT' => $conf->global->MAIN_MAX_DECIMALS_UNIT,
	'dec' => $dec,
	'thousand' => $thousand,
);

?>
/* <script > */
// LANGS

// DIALOG BOX

$(document).on("click", '#dr-reapply', function (event) {
	event.preventDefault();

	var element = $(this).attr('data-target-element');
	var fk_element = $(this).attr('data-target-id');
	var documentUrl = $(this).attr('data-document-url');

	var productLoadDialogBox = "product-load-dialog-box";
	// Create layer to convert popup
	$('body').append('<div id="' + productLoadDialogBox + '" title="<?php print $langs->transnoentities('UpdateProduct'); ?>"></div>');

	// Layer to popup
	var popup = $('#' + productLoadDialogBox).dialog({
		autoOpen: true,
		modal: true,
		width: Math.min($(window).width() - 50, 1700),
		dialogClass: 'discountrule-product-search-box',
		buttons: [
			{
				text: "<?php print $langs->transnoentities('Apply'); ?>",
				"class": 'ui-state-information',
				"type": 'submit',
				"id": 'apply-button',
				click: function () {
					$("#reapply-form").submit();
				}
			},
			{
				text: "<?php print $langs->transnoentities('Cancel'); ?>",
				"class": 'ui-state-information',
				click: function () {
					$(this).dialog("close");
					$('#' + productLoadDialogBox).remove();
				}
			}
		],
		close: function (event, ui) {
			$('#' + productLoadDialogBox).remove();
			if (reapplyDiscount.dialogCountAddedProduct > 0) {
				// si une ligne a été ajoutée, recharge la page actuelle
				document.location.reload();
			}
		},
		open: function (event, ui) {
			reapplyDiscount.discountLoadProductDialogForm(documentUrl, element, fk_element);
			$('#' + productLoadDialogBox).parent().css('z-index', 1002);
			$('.ui-widget-overlay').css('z-index', 1001);
			//Enabled/disabled Apply button
			$("#apply-button").attr("class", "ui-state-information ui-button ui-corner-all ui-widget ui-button-disabled ui-state-disabled");
		}
	});
});

var reapplyDiscount = {};
(function (o) {

	o.lastidprod = 0;
	o.lastqty = 0;

	o.discountlang = <?php print json_encode($translate) ?>;
	o.advancedProductSearchConfig = <?php print json_encode($confToJs) ?>;
	o.dialogCountAddedProduct = 0;

	/**
	 * Load reapply discount dialog form
	 */
	o.discountLoadProductDialogForm = function (documentUrl, element = '', fk_element = '') {
		var productLoadDialogBox = "product-load-dialog-box";
		var formReapply = $('<form action="" id="reapply-form" method="post"></form>');
		var divReapply = $('<div id="divReapply"></div>');

		$('#' + productLoadDialogBox).addClass('--ajax-loading');

		$('#' + productLoadDialogBox).append(formReapply);

		//$('#' + productLoadDialogBox).prepend($('<div class="inner-dialog-overlay"><div class="dialog-loading__loading"><div class="dialog-loading__spinner-wrapper"><span class="dialog-loading__spinner-text">LOADING</span><span class="dialog-loading__spinner"></span></div></div></div>'));

		formReapply.append($('<div class="checkbox-reapply"><?php print $langs->transnoentities('priceReapply')?><input name="price-reapply" id="price-reapply" type="checkbox" value="1"> <?php print $langs->transnoentities('productReapply')?><input name="product-reapply" id="product-reapply" type="checkbox" value="1"><input name="action" type="hidden" value="doUpdateDiscounts"/></div>'));

		formReapply.append(divReapply);

		// Display all invoice products lines
		divReapply.load(documentUrl + "&action=selectlines #tablelines", function () {

			// Check all checkboxes at once
			$(".linecolcheckall > input").first().on('change', function () {
				if ($(".linecolcheckall > input").is(':checked')) {
					$(".linecheckbox").prop('checked', true).trigger( "change" );
				} else {
					$(".linecheckbox").prop('checked', false).trigger( "change" );
				}
			});
			//Enabled/disabled Apply button
			$("#price-reapply, #product-reapply").on('change', function () {
				if (($(".checkbox-reapply > input").is(':checked')) && ($(".linecheckbox").is(':checked'))) {
					$("#apply-button").removeAttr("class", "ui-state-information ui-button ui-corner-all ui-widget ui-button-disabled ui-state-disabled");
					$("#apply-button").attr("class", "ui-state-information ui-button ui-corner-all ui-widget");
				} else {
					$("#apply-button").attr("class", "ui-state-information ui-button ui-corner-all ui-widget ui-button-disabled ui-state-disabled");
				}
			});

			$(".linecolcheck > input").on('change', function () {
				if (($(".checkbox-reapply > input").is(':checked')) && ($(".linecheckbox").is(':checked'))) {
					$("#apply-button").removeAttr("class", "ui-state-information ui-button ui-corner-all ui-widget ui-button-disabled ui-state-disabled");
					$("#apply-button").attr("class", "ui-state-information ui-button ui-corner-all ui-widget");
				} else {
					$("#apply-button").attr("class", "ui-state-information ui-button ui-corner-all ui-widget ui-button-disabled ui-state-disabled");
				}
			});
		});
	}

	/**
	 * Submit reapply discount
	 */
	o.discountSubmitDialogForm = function (documentUrl, element = '', fk_element = '') {
		/*o.isCheckboxReapplyChecked = Boolean(false);

		if (document.getElementById("price-reapply").checked) {
			console.log("Price checked !");
			o.isCheckboxReapplyChecked = true;
		}
		if (document.getElementById("product-reapply").checked) {
			console.log("Product checked !");
			o.isCheckboxReapplyChecked = true;
		}
		if (!o.isCheckboxReapplyChecked) {
			console.log("No action checked !");
		}

		if (document.getElementById("linecheckboxtoggle").checked) {

		}*/
	}

	/**
	 * affectation du contenu dans l'attribut title
	 *
	 * @param $element
	 * @param text
	 */
	o.setToolTip = function ($element, text) {
		$element.attr("title", text);
		o.initToolTip($element);
	}


	/**
	 * initialisation de la tootip
	 * @param element
	 */
	o.initToolTip = function (element) {

		if (!element.data("tooltipset")) {
			element.data("tooltipset", true);
			element.tooltip({
				show: {collision: "flipfit", effect: "toggle", delay: 50},
				hide: {delay: 50},
				tooltipClass: "mytooltip",
				content: function () {
					return $(this).prop("title");		/* To force to get title as is */
				}
			});
		}
	}


	o.setEventMessage = function (msg, status = true) {

		if (msg.length > 0) {
			if (status) {
				$.jnotify(msg, 'notice', {timeout: 5}, {
					remove: function () {
					}
				});
			} else {
				$.jnotify(msg, 'error', {timeout: 0, type: 'error'}, {
					remove: function () {
					}
				});
			}
		} else {
			$.jnotify('ErrorMessageEmpty', 'error', {timeout: 0, type: 'error'}, {
				remove: function () {
				}
			});
		}
	}

})(reapplyDiscount);
