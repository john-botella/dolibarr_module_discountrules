<?php

//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');


/**
 * \file    js/advancedproductsearch.js.php
 * \ingroup advancedproductsearch
 * \brief   JavaScript file for module advancedproductsearch.
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
$langs->loadLangs(array("advancedproductsearch@advancedproductsearch","other"));

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

?>
/* <script > */
// LANGS

// LA DIALOG BOX

$(document).on("click", '#drReapply', function(event) {
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
			if(AdvancedProductSearch.dialogCountAddedProduct>0){
				// si une ligne a été ajoutée, recharge la page actuelle
				document.location.reload();
			}
		},
		open: function( event, ui ) {
			//$(this).dialog('option', 'maxHeight', $(window).height()-30);
			AdvancedProductSearch.discountLoadSearchProductDialogForm("&element="+element+"&fk_element="+fk_element);
			$('#'+productSearchDialogBox).parent().css('z-index', 1002);
			$('.ui-widget-overlay').css('z-index', 1001);
		}
	});
});
