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
 * @param CommonObject $object
 * @return string
 */
function discountRuleDocumentsLines($object){
	global $db, $langs;
	$langs->load("discountrules@discountrules");
	$out = '';

    $havePricesChange = false;
    $haveDescriptionsChange = false;


	if(!class_exists('DiscountSearch')) {
		require_once __DIR__ . '/discountSearch.class.php';
	}

	if(!empty($object->lines)){


		$out.= '<table class="noborder noshadow" >';
		$out.= '<tr class="liste_titre nodrag nodrop">';
		$out.= '	<td>';
		$out.= $langs->transnoentities("Description");
		$out.= '	</td>';
		$out.= '	<td>';
		$out.= $langs->transnoentities("VAT");
		$out.= '	</td>';
		$out.= '	<td>';
		$out.= $langs->transnoentities("UnitPriceET");
		$out.= '	</td>';
		$out.= '	<td>';
		$out.= $langs->transnoentities("Qty");
		$out.= '	</td>';
		$out.= '	<td>';
		$out.= $langs->transnoentities("Reduction");
		$out.= '	</td>';
		$out.= '	<td>';
		$out.= $langs->transnoentities("TotalHT");
		$out.= '	</td>';
		$out.= '<td class="linecolcheckall center">';
		$out.= '<input type="checkbox" class="linecheckboxtoggle" />';
		$out.= '</td>';
		$out.= '</tr>';
		$out.= '<tbody>';

		foreach ($object->lines as $i => $line){

			$product = false;
			$haveUnitPriceChange = false;
			$haveVatChange = false;
			$haveReductionChange = false;
			$haveDescriptionChange = false;

			//Get the product from the database
			if(!empty($line->fk_product)){

				// RE-Appliquer la description si besoin

				$product = new Product($object->db);
				$resFetchProd = $product->fetch($line->fk_product);
				if($resFetchProd>0){
					if($line->desc != $product->description){
						$haveDescriptionChange = true;
                        $haveDescriptionsChange = true;
					}
				}
				else{
					// Erreur si le produit n'a pas d'ID
					setEventMessage($langs->transnoentities('ErrorProduct'));
				}

				// Search discount
				$discountSearch = new DiscountSearch($object->db);

				$discountSearchResult = $discountSearch->search($line->qty, $line->fk_product, $object->socid, $object->fk_project);

				DiscountRule::clearProductCache();


				// ne pas appliquer les prix à 0 (par contre, les remises de 100% sont possibles)
				if (doubleval($line->subprice) != $discountSearchResult->subprice) {
					$haveUnitPriceChange = true;
                    $havePricesChange = true;
				}

				if(doubleval($line->remise_percent) != $discountSearchResult->reduction){
					$haveReductionChange = true;
                    $havePricesChange = true;
				}

				if ($line->tva_tx != $product->tva_tx) {
					$haveVatChange = true;
                    $havePricesChange = true;
				}

			}

			$out.= '<tr class="drag drop oddeven" id="line-'.$line->id.'">';

			//  Description
			$out.= '	<td class="linecoldescription minwidth300imp">';
			if ($product != null) {
				$out.= $product->getNomUrl(2);
			} else {
                $out.= $product->name;
            }

            // Si les descriptions ne sont pas similaire
			if ($haveDescriptionChange) {

				$out.= ' <i class="fas fa-exclamation-triangle" ></i>';                                                                                  // Ajout du picto

				$out.= '<div class="dr-accordion-container --closed">';                                                                                  // Ajout d'une div qui englobe le title et la description
                $out.= '    <div class="dr-accordion-title" data-accordion-target="accordion-toggle-current'. $line->id .'" >';                          // début Title qui gère le toggle
                $out.= '        <span class="description-available new-description">'. ' ' . $langs->trans('CurrentDescription') . ' </span>';      // Contenu du Title
                $out.= '    </div>';                                                                                                                     // fin Title qui gere le toggle
                $out.= '    <div id="accordion-toggle-current'. $line->id .'" class="dr-accordion-body compare-current-description">';                   //début description activé/désactivé par le toggle
                $out.= $line->desc;                                                                                                                      // Description propal
                $out.= '    </div>';                                                                                                                     //fin description activé/désactivé par le toggle
                $out.= '</div> <!-- end .dr-accordion-container -->';

                $out.= '<div class="dr-accordion-container --closed">';
                $out.= '    <div class="dr-accordion-title"  data-accordion-target="accordion-toggle-new'. $line->id .'" >';
                $out.= '        <span class="description-available new-description">'. ' ' . $langs->trans('NewDescription') . ' </span>';
                $out.= '    </div>';
                $out.= '    <div id="accordion-toggle-new'. $line->id .'" class="dr-accordion-body compare-new-description">';
                $out.= $product->description;
                $out.= '    </div>';
                $out.= '</div><!-- end .dr-accordion-container -->';
			}
			else{
				$out.= '<div class="--no-change" style="opacity: 0.7" >'.$line->desc.'</div>';
			}

			$out.= '	</td>';

			// TVA
			$out.= '	<td>';
			if ($haveVatChange) {
				$out.= '<em style="text-decoration: line-through">' . price(doubleval($line->tva_tx)) . '%' . '</em><br/>';
				$out.= '<strong>' . price(doubleval($product->tva_tx)) . '% </strong>';
			} else {
				$out.= price(doubleval($line->tva_tx)) . '%';
			}
			$out.= '	</td>';

			// Prix unitaire
			$out.= '	<td>';
			if ($haveUnitPriceChange) {
				$out.= '<em style="text-decoration: line-through">' . price(round($line->subprice, 2)) . '</em><br/>';
				$out.= '<strong>' . price(round($discountSearchResult->subprice, 2)) . '</strong>';
			} else {
				$out.= price(doubleval($line->subprice));
			}
			$out.= '	</td>';

			$out.= '	<td>';
			$out.= $line->qty;
			$out.= '	</td>';

			// REMISE
			$out.= '	<td>';
			if ($haveReductionChange) {
				$out.= '<em style="text-decoration: line-through">' . price($line->remise_percent) . '</em><br/>';
				$out.= '<strong>' . price($discountSearchResult->reduction) . '</strong>';
			} else {
				$out.= price($line->remise_percent) ;
			}
			$out.= '	</td>';

			// Total HT
			$out.= '	<td>';
			if ($haveUnitPriceChange || $haveReductionChange) {
				$out.= '<em style="text-decoration: line-through">' . price(doubleval($line->total_ht)) . '</em><br/>';
				$out.= '<strong>' . price($discountSearchResult->subprice * $line->qty) . '</strong>';
			} else {
				$out.= price(doubleval($line->total_ht));
			}

			$out.= '<td class="linecolcheck center">';
			if(!empty($line->fk_product)) {
                $checked = "";
                if ($haveUnitPriceChange || $haveReductionChange || $haveDescriptionChange || $haveVatChange) {
                    $checked = "checked";
                }
				$out .= '<input type="checkbox" class="linecheckbox" name="line_checkbox[' . ($i + 1) . ']" value="' . $line->id . '" '.$checked.' >';
			}
			$out.= '</td>';

			$out.= '</tr>';
		}

		$out.= '</tbody>';
		$out.= "</table>";
	}

    if ($havePricesChange) {
        //TODO touloulou
        $out = '<div class="reapply-discount-form-label checkbox-reapply" ><input name="price-reapply" id="price-reapply" type="checkbox" value="1" checked> </div>' . ' ' . $langs->trans('priceReapply') . $out;
    }
    if ($haveDescriptionsChange) {
        $out = '<div class="reapply-discount-form-label checkbox-reapply" ><input name="product-reapply" id="product-reapply" type="checkbox" value="1" checked> </div> ' . ' ' . $langs->trans('productDescriptionReapply')  . $out;
    }


	return $out;
}
