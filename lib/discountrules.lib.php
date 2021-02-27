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
 */

/**
 * \file    lib/discountrules.lib.php
 * \ingroup discountrules
 * \brief   Example module library.
 *
 * Put detailed description here.
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function discountrulesAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("discountrules@discountrules");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/discountrules/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("ModulediscountrulesSettings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/discountrules/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, false, $head, $h, 'discountrules');

	return $head;
}

function discountrulesPrepareHead($object)
{
    global $langs, $conf;

    $langs->load("discountrules@discountrules");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/discountrules/discountrule_card.php", 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
        //); // to remove a tab
        complete_head_from_modules($conf, $langs, $object, $head, $h, 'discountrules');

        return $head;
}



function discountRulesBannerTab(DiscountRule $object, $showNav = 1){
	global $langs, $form, $conf, $db;

	$onlybanner = 0;

	$linkbackUrl = dol_buildpath('/discountrules/discountrule_list.php',1) . '?t=t' . (! empty($socid) ? '&socid=' . $socid : '');
	if(!empty($object->fk_product)){ $linkbackUrl.= '&fk_product=' . intval($object->fk_product) ; }
	$linkback = '<a href="' . $linkbackUrl . '">' . $langs->trans("BackToList") . '</a>';


	$morehtmlref = '';


	$morehtmlref.='<div class="refidno">';

	if(!empty($object->fk_product))
	{

		if($object->product && (!is_object($object->product) || $object->id < 1) ){
			$object->product = new Product($db);
			if($object->product->fetch($object->fk_product) < 1)
			{
				$object->product = false;
			}
		}

		if($object->product){

			// Product / Service
			$morehtmlref.= $object->product->getNomUrl(2) . ' : ' . $object->product->label;
		}
	}

	if(!empty($object->fk_soc))
	{
		$soc = new Societe($object->db);
		if($soc->fetch($object->fk_soc)>0)
		{
			// Thirdparty
			$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(2);
		}
	}

	$morehtmlref.='</div>';

	$morehtmlstatus = $morehtmlright = $morehtmlleft = '';


	dol_banner_tab($object, 'id', $linkback, $showNav , 'rowid', 'label', $morehtmlref, '', 0, $morehtmlleft, $morehtmlstatus, 0, $morehtmlright);
}

/**
 * TODO : cette maniere contre intuitive de récupérer le libellé est tiré de la card des tiers, j'ai préféré factoriser pour pouvoir facilement le modifier plus tard vu que j'aime pas le style...
 * @param $fk_c_typent
 * @return bool|mixed
 */
function getTypeEntLabel($fk_c_typent){
	global $db, $langs;

	if(empty($fk_c_typent)){
		return $langs->trans("AllTypeEnt");
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

	$fk_c_typent = intval($fk_c_typent);

	$formcompany = new FormCompany($db);
	$arr = $formcompany->typent_array();
	if(isset($arr[$fk_c_typent])){
		return $arr[$fk_c_typent];
	}

	return false;
}

/**
 * Return a compiled message of json product-discount info
 * @param Translate $langs
 * @param $jsonResponse
 * @param $action
 *
 * @return string
 */
function getDiscountRulesInterfaceMessageTpl(Translate $langs, $jsonResponse, $action){

	global $hookmanager, $db;

	$return = '';

	$TprepareTpMsg = array();

	if($jsonResponse->result && $jsonResponse->element === "discountrule") {

		$discount = new DiscountRule($db);

		// Title
		$TprepareTpMsg['title'] = $langs->transnoentities('Discountrule') . " : ";
		$TprepareTpMsg['label'] = "<strong>" . $jsonResponse->label . "</strong>";

		if(isset($discount->fields['priority_rank']['arrayofkeyval'][$jsonResponse->priority_rank])) {
			$TprepareTpMsg['priority'] = $langs->transnoentities('PriorityRuleRank') . " : ";
			if($jsonResponse->priority_rank>0){
				$TprepareTpMsg['priority'].= "<strong>" . $langs->transnoentities($discount->fields['priority_rank']['arrayofkeyval'][$jsonResponse->priority_rank]) . "</strong>";
			}else{
				$TprepareTpMsg['priority'].= $langs->transnoentities($discount->fields['priority_rank']['arrayofkeyval'][$jsonResponse->priority_rank]);
			}
		}

		if ($jsonResponse->fk_project > 0) {
			$TprepareTpMsg['InfoProject'] =$langs->transnoentities('InfosProject');
		}

		if ($jsonResponse->product_price > 0) {
			$TprepareTpMsg['product_price'] =  $langs->transnoentities('Price') . " : " . $jsonResponse->product_price;
		}

		if ($jsonResponse->product_reduction_amount > 0) {
			$TprepareTpMsg['product_reduction_amount'] = $langs->transnoentities('ReductionAmount') . ": -" . $jsonResponse->product_reduction_amount;
		}

		$TprepareTpMsg['discount'] = $langs->transnoentities('Discount') . " : " . $jsonResponse->reduction . "%" ;
		$TprepareTpMsg['FromQty']  =  $langs->transnoentities('FromQty') . " : " . $jsonResponse->from_quantity ;
		$TprepareTpMsg['ThirdPartyType'] = 	 $langs->transnoentities('ThirdPartyType') . " : " . $jsonResponse->typentlabel;

		if ($jsonResponse->fk_product > 0) {
			$TprepareTpMsg['fk_product'] = $langs->transnoentities('Product') . " : " . $jsonResponse->match_on->product_info;
		}

		$TprepareTpMsg['ProductCategory'] = $langs->transnoentities('ProductCategory') . " : " . $jsonResponse->match_on->category_product ;
		$TprepareTpMsg['ClientCategory'] = $langs->transnoentities('ClientCategory') . " : " . $jsonResponse->match_on->category_company ;
		$TprepareTpMsg['Customer']  = $langs->transnoentities('Customer') . " : " . $jsonResponse->match_on->company;

	}
	else if($jsonResponse->result && ($jsonResponse->element === "facture" || $jsonResponse->element === "commande" || $jsonResponse->element === "propal"  ))
	{
		$TprepareTpMsg['label'] = "<strong>" . $jsonResponse->label . "</strong>";
		$TprepareTpMsg['discount'] 	= $langs->transnoentities('Discount') . " : " . $jsonResponse->reduction . "%" ;
		$TprepareTpMsg['subprice'] 	= $langs->transnoentities('PriceUHT') . " : " . price($jsonResponse->subprice);
		$TprepareTpMsg['Date'] 		= $langs->transnoentities('Date') . " : " . $jsonResponse->date_object_human;
		$TprepareTpMsg['Qty'] 		= $langs->transnoentities('Qty') . " : " . $jsonResponse->qty;
	}
	else
	{
		if ($jsonResponse->defaultCustomerReduction > 0){
			$TprepareTpMsg['CustomerReduction'] = $langs->transnoentities('percentage')." : " .  $jsonResponse->defaultCustomerReduction + "%"
				. "<br/>"  . $langs->transnoentities('DiscountruleNotFoundUseCustomerReductionInstead');
		}else{
			$TprepareTpMsg['CustomerReduction'] = $langs->transnoentities('DiscountruleNotFound');
		}
	}

	if ($jsonResponse->fk_product > 0 && doubleval($jsonResponse->standard_product_price > 0)) {
		$TprepareTpMsg['InfosProduct'] = "<strong>" . $langs->transnoentities('InfosProduct') . "</strong>";
		$TprepareTpMsg['productPrice'] = $langs->transnoentities('ProductPrice') . " : " . $jsonResponse->standard_product_price;
	}

	if(!empty($TprepareTpMsg)){
		foreach($TprepareTpMsg as $key => $msg ){

			if(!empty($return)){
				$return .= '<br/>';
			}

			if($key == 'InfosProduct'){
				$return .= '<br/>';
			}

			$return .= $msg;
		}
	}


	// Note that $action and $object may be modified by hook
	// Utilisation initiale : interception pour remplissage customisé de $jsonResponse->tpMsg

	$parameters = array(
		'TprepareTpMsg' => $TprepareTpMsg,
	);

	$reshook = $hookmanager->executeHooks('discountRulesInterfaceMessage', $parameters, $jsonResponse, $action);
	if($reshook>0){
		$return = $hookmanager->resPrint;
	}
	else if ($reshook < 0)
	{
		// TODO : manage errors
		// $hookmanager->error;
		// $hookmanager->errors;
	}
	else{
		$return.= $hookmanager->resPrint;
	}


	return $return;
}

/**
 * print an ajax ready search table for product
 */
function discountProductSearchForm(){
global $langs, $conf, $db;

	if(!class_exists('Product')){
		include_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
	}

	$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : 10;
	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
	$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
	if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	if (!$sortfield) $sortfield = "p.ref";
	if (!$sortorder) $sortorder = "ASC";


	// LES FILTRES
	$search_type = '';
	$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
	$search_ref = GETPOST("search_ref", 'alpha');
	$search_barcode = GETPOST("search_barcode", 'alpha');
	$search_label = GETPOST("search_label", 'alpha');

	$search_type = -1; // TODO $search_type = GETPOST("search_type", 'int');
//	$search_vatrate = GETPOST("search_vatrate", 'alpha');
	$searchCategoryProductOperator = (GETPOST('search_category_product_operator', 'int') ? GETPOST('search_category_product_operator', 'int') : 0);
	$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
	$search_tosell = 1; // GETPOST("search_tosell", 'int'); // TODO
//	$search_tobuy = GETPOST("search_tobuy", 'int'); // TODO
	$fourn_id = GETPOST("fourn_id", 'int');
	$catid = GETPOST('catid', 'int');
//	$search_tobatch = GETPOST("search_tobatch", 'int');
//	$optioncss = GETPOST('optioncss', 'alpha');
	$type = GETPOST("type", "int");


	$fk_company = GETPOST("fk_company", "int");
	$fk_project = GETPOST("fk_project", "int");

	// REQUETTE SQL

	// List of fields to search into when doing a "search in all"
	$fieldstosearchall = array('p.ref','pfp.ref_fourn','p.label','p.description',"p.note");

	// multilang
	if (!empty($conf->global->MAIN_MULTILANGS)){
		$fieldstosearchall+= array('pl.label','pl.description','pl.note');
	}

	if (!empty($conf->barcode->enabled)) {
		$fieldstosearchall+=  array('p.barcode','pfp.barcode');
	}

	// SELECT PART
	$sqlSelect = ' DISTINCT p.rowid ';
	if (!empty($conf->global->PRODUCT_USE_UNITS))   $sqlSelect .= ' ,cu.label as cu_label';

	// SELECT COUNT PART
	$sqlSelectCount = ' COUNT(DISTINCT p.rowid) as nb_results ';

	$sql = ' FROM '.MAIN_DB_PREFIX.'product as p ';
	if (!empty($searchCategoryProductList) || !empty($catid)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON (p.rowid = cp.fk_product) "; // We'll need this table joined to the select in order to filter by categ
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (pfp.fk_product = p.rowid) ";
	// multilang
	if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON (pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang()."' )";
	if (!empty($conf->global->PRODUCT_USE_UNITS))   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_units cu ON (cu.rowid = p.fk_unit)";

	$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
	if (isset($search_tosell) && dol_strlen($search_tosell) > 0 && $search_tosell != -1) $sql .= " AND p.tosell = ".((int) $search_tosell);
	if (isset($search_tobuy) && dol_strlen($search_tobuy) > 0 && $search_tobuy != -1)   $sql .= " AND p.tobuy = ".((int) $search_tobuy);

	if ($sall) $sql .= natural_search($fieldstosearchall, $sall);
	// if the type is not 1, we show all products (type = 0,2,3)
//	if (dol_strlen($search_type) && $search_type != '-1'){
//		if ($search_type == 1) $sql .= " AND p.fk_product_type = 1";
//		else $sql .= " AND p.fk_product_type <> 1";
//	}

	if ($search_ref)     $sql .= natural_search('p.ref', $search_ref);
	if ($search_label)   $sql .= natural_search('p.label', $search_label);
	if ($search_barcode) $sql .= natural_search('p.barcode', $search_barcode);
	if ($catid > 0)     $sql .= " AND cp.fk_categorie = ".$catid;
	if ($catid == -2)   $sql .= " AND cp.fk_categorie IS NULL";

	$searchCategoryProductSqlList = array();
	if ($searchCategoryProductOperator == 1) {
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			if (intval($searchCategoryProduct) == -2) {
				$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
			} elseif (intval($searchCategoryProduct) > 0) {
				$searchCategoryProductSqlList[] = "cp.fk_categorie = ".$db->escape($searchCategoryProduct);
			}
		}
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
		}
	} else {
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			if (intval($searchCategoryProduct) == -2) {
				$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
			} elseif (intval($searchCategoryProduct) > 0) {
				$searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".$searchCategoryProduct.")";
			}
		}
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
		}
	}
	if ($fourn_id > 0)  $sql .= " AND pfp.fk_soc = ".((int) $fourn_id);

	print '<form id="product-search-dialog-form">';

	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="product-search-form">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	$res = $db->query('SELECT '.$sqlSelectCount.' '.$sql);
	$countResult = 0;
	if ($res) {
		$obj = $db->fetch_object($res);
		$countResult = $obj->nb_results;
	}

	print '<div class="discountrules-global-search-container" >';
	print '<input name="sall" value="'.dol_htmlentities($sall).'" id="search-all-form-input" class="discountrules-global-search-input" placeholder="'.$langs->trans('Search').'" autocomplete="off">';
	print '</div>';

	if($countResult > 0){
		print '<div class="discountrules-productsearch__results-count">';
		if($countResult>1){
			print $langs->trans('resultsDisplayForNbResultsFounds', min($limit,$countResult), $countResult );
		}
		else{
			print $langs->trans('OneResultDisplayForOneResultFounds', min($limit,$countResult), $countResult );
		}
		print '</div>';
	}

	?>
		<table class="noborder centpercent" >
			<thead>
			<tr class="liste_titre">
				<th><?php print $langs->trans('Ref'); ?></th>
				<th><?php print $langs->trans('Label'); ?></th>
				<th><?php print $langs->trans('RealStock'); ?></th>
				<th><?php print $langs->trans('VirtualStock'); ?></th>
				<th><?php print $langs->trans('Price'); ?></th>
				<th><?php print $langs->trans('Discount'); ?></th>
				<th><?php print $langs->trans('FinalDiscountPrice'); ?></th>
			</tr>
			</thead>
			<tbody>
<?php
	$res = $db->query('SELECT '.$sqlSelect.' '.$sql.$db->plimit($limit + 1, $offset));
	//print dol_htmlentities('SELECT '.$sqlSelect.' '.$sql.$db->plimit($limit + 1, $offset));
	if ($res)
	{
		if($db->num_rows($res) > 0){
			while ($obj = $db->fetch_object($res)){
				$product = new Product($db);
				$resProd = $product->fetch($obj->rowid);
				if($resProd > 0){
					$product->load_stock();
					print '<tr class="discount-search-product-row">';
					print '<td class="discount-search-product-col --ref" >'. $product->getNomUrl(1).'</td>';
					print '<td class="discount-search-product-col --label" >'. $product->label.'</td>';
					print '<td class="discount-search-product-col --stock-reel" >'.$product->stock_reel.'</td>';
					print '<td class="discount-search-product-col --stock-theorique" >'.$product->stock_theorique.'</td>';

					// Search discount
					$discountSearch = new DiscountSearch($db);
					$discountSearchResult = $discountSearch->search(0, $product->id, $fk_company, $fk_project);

					print '<td class="discount-search-product-col --ref" >'.'</td>';
					print '<td class="discount-search-product-col --ref" >'.'</td>';
					print '<td class="discount-search-product-col --ref" >'.'</td>';
					print '</tr>';
				}
				else{
					print '<tr class="discount-search-product-row">';
					print '<td class="discount-search-product-col-error center" colspan="7">'. $product->errorsToString() .'</td>';
					print '</tr>';

				}

			}
		}
		else{
			print '<tr class="discount-search-product-row">';
			print '<td class="discount-search-product-col-no-result" colspan="7">'. $langs->trans("NoResults") .'</td>';
			print '</tr>';

		}
	}
	else{
		print '<tr class="discount-search-product-row">';
		print '<td class="discount-search-product-col-error" colspan="7">'. $db->error() .'</td>';
		print '</tr>';
	}

	print '</tbody>';
	print '</table>';
	print '</form>';
}
