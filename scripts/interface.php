<?php

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Include and load Dolibarr environment variables
$res = 0;
if (!$res && file_exists($path . "master.inc.php")) $res = @include($path . "master.inc.php");
if (!$res && file_exists($path . "../master.inc.php")) $res = @include($path . "../master.inc.php");
if (!$res && file_exists($path . "../../master.inc.php")) $res = @include($path . "../../master.inc.php");
if (!$res && file_exists($path . "../../../master.inc.php")) $res = @include($path . "../../../master.inc.php");
if (!$res) die("Include of master fails");
dol_include_once('discountrules/class/discountrule.class.php');
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once __DIR__ . '/../lib/discountrules.lib.php';

// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules@discountrules", "other"));


$get = GETPOST('get');
$put = GETPOST('put');

$activateDebugLog = GETPOST('activatedebug','int');

if ($get === 'product-discount') {
	$productId = GETPOST('fk_product', 'int');
	$qty = GETPOST('qty', 'int');
	$fk_company = GETPOST('fk_company', 'int');
	$fk_country = GETPOST('fk_country', 'int');
	$fk_c_typent = GETPOST('fk_c_typent', 'int');

	// GET SOCIETE CAT
	$TCompanyCat = array();
	if (!empty($fk_company)) {
		$c = new Categorie($db);
		$TCompanyCat = $c->containing($fk_company, Categorie::TYPE_CUSTOMER, 'id');

		if (empty($fk_country)) {
			$societe = new Societe($db);
			if ($societe->fetch($fk_company) > 0) {
				$fk_country = $societe->country_id;
				$fk_c_typent = $societe->typent_id;
			}
		}
	}

	_debugLog($TCompanyCat); // pass get var activatedebug or set $activatedebug to show log

	if (empty($qty)) $qty = 1;

	$jsonResponse = new stdClass();
	$jsonResponse->result = false;
	$jsonResponse->log = array();

	// GET product infos and categories
	$product = false;
	if (!empty($productId)) {
		dol_include_once('product/class/product.class.php');
		$product = new Product($db);

		if ($product->fetch($productId) > 0) {
			// Get current categories
			$c = new Categorie($db);
			$TProductCat = $c->containing($product->id, Categorie::TYPE_PRODUCT, 'id');
		}else {
			$product = false;
			$productId = 0;
		}
	}

	$discount = false;

	if (empty($TProductCat)) {
		$TProductCat = array(0); // force searching in all cat
	} else {
		$TProductCat[] = 0; // search in all cat too
	}

	_debugLog($TProductCat); // pass get var activatedebug or set $activatedebug to show log
	_debugLog($TCompanyCat); // pass get var activatedebug or set $activatedebug to show log

	$TAllProductCat = DiscountRule::getAllConnectedCats($TProductCat);
	$TAllCompanyCat = DiscountRule::getAllConnectedCats($TCompanyCat);

	_debugLog($TAllProductCat); // pass get var activatedebug or set $activatedebug to show log
	_debugLog($TAllCompanyCat); // pass get var activatedebug or set $activatedebug to show log

	$discountRes = new DiscountRule($db);
	$res = $discountRes->fetchByCrit($qty, $productId, $TAllProductCat, $TCompanyCat, $fk_company,  time(), $fk_country, $fk_c_typent);
	_debugLog($discountRes->error);
	if ($res > 0) {
		$discount = $discountRes;
	}
	else{
		$jsonResponse->log[] = $discountRes->error;
	}

	// SEARCH ALLREADY APPLIED DISCOUNT IN DOCUMENTS (need setup option activated)
	if($product) {
		$documentDiscount = false;
		$from_quantity = empty($conf->global->DISCOUNTRULES_SEARCH_QTY_EQUIV) ? 0 : $qty;

		if (!empty($conf->global->DISCOUNTRULES_SEARCH_IN_ORDERS)) {
			$commande = DiscountRule::searchDiscountInDocuments('commande', $product->id, $fk_company, $from_quantity);
			$documentDiscount = $commande;
		}
		if (!empty($conf->global->DISCOUNTRULES_SEARCH_IN_PROPALS)) {
			$propal = DiscountRule::searchDiscountInDocuments('propal', $product->id, $fk_company, $from_quantity);
			if (!empty($propal)
				&& (empty($documentDiscount) || DiscountRule::calcNetPrice($documentDiscount->subprice, $documentDiscount->remise_percent) > DiscountRule::calcNetPrice($propal->subprice, $propal->remise_percent) ))
			{
					$documentDiscount = $propal;
			}
		}
		if (!empty($conf->global->DISCOUNTRULES_SEARCH_IN_INVOICES)) {
			$facture = DiscountRule::searchDiscountInDocuments('facture', $product->id, $fk_company, $from_quantity);
			if (!empty($facture)
				&& (empty($documentDiscount)|| DiscountRule::calcNetPrice($documentDiscount->subprice, $documentDiscount->remise_percent) > DiscountRule::calcNetPrice($facture->subprice, $facture->remise_percent) ) )
			{
				$documentDiscount = $facture;
			}
		}

		if (!empty($documentDiscount) && $documentDiscount->remise_percent > $discount->reduction) {

			$useDocumentReduction = true;
			if (!empty($discount)) {
				$discountNetPrice = $discount->getNetPrice($product->id, $fk_company);
				if(!empty($discountNetPrice) && DiscountRule::calcNetPrice($documentDiscount->subprice, $documentDiscount->remise_percent) > $discountNetPrice) {
					$useDocumentReduction = false;
				}
			}

			if($useDocumentReduction) {

				$discount = false;

				$jsonResponse->result = true;
				$jsonResponse->element = $documentDiscount->element;
				$jsonResponse->id = $documentDiscount->rowid;
				$jsonResponse->label = $documentDiscount->ref;
				$jsonResponse->qty = $documentDiscount->qty;
				$jsonResponse->subprice = $jsonResponse->product_price =  $documentDiscount->subprice;
				$jsonResponse->product_reduction_amount = 0;
				$jsonResponse->reduction = $documentDiscount->remise_percent;
				$jsonResponse->entity = $documentDiscount->entity;
				$jsonResponse->fk_status = $documentDiscount->fk_status;
				$jsonResponse->date_valid = $documentDiscount->date_valid;
				$jsonResponse->date_valid_human = dol_print_date($documentDiscount->date_valid, '%d %b %Y');
			}
		}
	}


	// PREPARE JSON RETURN
	if (!empty($discount)) {

		$jsonResponse->result = true;
		$jsonResponse->element = 'discountrule';
		$jsonResponse->id = $discount->id;
		$jsonResponse->label = $discount->label;
		$jsonResponse->subprice = $discount->getDiscountSellPrice($productId, $fk_company) - $discount->product_reduction_amount;
		$jsonResponse->product_price = $discount->product_price;
		$jsonResponse->standard_product_price = $discount::getProductSellPrice($productId, $fk_company);
		$jsonResponse->product_reduction_amount = $discount->product_reduction_amount;
		$jsonResponse->reduction = $discount->reduction;
		$jsonResponse->entity = $discount->entity;
		$jsonResponse->from_quantity = $discount->from_quantity;
		$jsonResponse->fk_c_typent = $discount->fk_c_typent;

		$jsonResponse->typentlabel  = getTypeEntLabel($discount->fk_c_typent);
		if(!$jsonResponse->typentlabel ){ $jsonResponse->typentlabel = ''; }

		$jsonResponse->fk_status = $discount->fk_status;
		$jsonResponse->fk_product = $discount->fk_product;
		$jsonResponse->date_creation = $discount->date_creation;
		$jsonResponse->match_on = $discount->lastFetchByCritResult;
		if (!empty($discount->lastFetchByCritResult)) {
			// ADD humain readable informations from search result

			$jsonResponse->match_on->product_info = '';
			if($product && !empty($discount->fk_product) && $product->id == $discount->fk_product ){
				$jsonResponse->match_on->product_info = $product->ref . ' - '.$product->label;
			}

			$jsonResponse->match_on->category_product = $langs->transnoentities('AllProductCategories');
			if (!empty($discount->lastFetchByCritResult->fk_category_product)) {
				$c = new Categorie($db);
				$c->fetch($discount->lastFetchByCritResult->fk_category_product);
				$jsonResponse->match_on->category_product = $c->label;
			}

			$jsonResponse->match_on->category_company = $langs->transnoentities('AllCustomersCategories');
			if (!empty($discount->lastFetchByCritResult->fk_category_company)) {
				$c = new Categorie($db);
				$c->fetch($discount->lastFetchByCritResult->fk_category_company);
				$jsonResponse->match_on->category_company = $c->label;
			}

			$jsonResponse->match_on->company = $langs->transnoentities('AllCustomers');
			if (!empty($discount->lastFetchByCritResult->fk_company)) {
				$s = new Societe($db);
				$s->fetch($discount->lastFetchByCritResult->fk_company);

				$jsonResponse->match_on->company = $s->name ? $s->name : $s->nom;
				$jsonResponse->match_on->company .= !empty($s->name_alias) ? ' (' . $s->name_alias . ')' : '';
			}
		}
	}

	print json_encode($jsonResponse, JSON_PRETTY_PRINT);

}


$db->close();    // Close $db database opened handler


function _debugLog($log = null){
	global $activateDebugLog;

	if($activateDebugLog){
		var_dump($log);
	}
}
