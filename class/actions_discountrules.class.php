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
 * \file    class/actions_discountrules.class.php
 * \ingroup discountrules
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class Actionsdiscountrules
 */
class Actionsdiscountrules
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	/**
	 * @var string Error
	 */
	public $error = '';
	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * @param array $parameters
	 * @param CommonObject $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 */
	public function formEditProductOptions ($parameters, &$object, &$action, $hookmanager){
		global $langs;
		$langs->loadLangs(array('discountrules'));
		$context = explode(':', $parameters['context']);
		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context) && $action != "edit")
		{
			?>
			<!-- handler event jquery on 'qty' udpating values for product  -->
			<link rel="stylesheet" type="text/css" href="<?php print dol_buildpath('discountrules/css/discountrules.css.php',1); ?>">
			<script type="text/javascript">
			$( document ).ready(function() {
				var idProd = "<?php print $parameters['line']->fk_product; ?>";
				var idLine =  "<?php print $parameters['line']->id; ?>";

				// change Qty
				$("[name='qty']").change(function() {
					let FormmUpdateLine = 	!document.getElementById("addline");
					// si nous sommes dans le formulaire Modification
					if (FormmUpdateLine) {
						discountFetchOnEditLine('<?php print $object->element; ?>',idLine,idProd,<?php print intval($object->socid); ?>,<?php print intval($object->fk_project); ?>,<?php print intval($object->country_id); ?>);
					}
				});

				$(document).on("mouseover", ".suggest-discount-icon",function(){
					if ($('#suggest-discount').css('opacity') != 0){
						$(this).css("cursor","pointer");
					}else{
						$(this).css("cursor","default");
						$('#suggest-discount').attr("title","");
					}
				});

				$(document).on("click", ".suggest-discount-icon",function(){
					$('#remise_percent').val($(this).attr("data-discount"));
					$('#remise_percent').addClass("discount-rule-change --info");
				});
			});
			</script>
			<?php

		}
	}

	/**
	 * @param array $parameters
	 * @param CommonObject $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$context = explode(':', $parameters['context']);
		$langs->loadLangs(array('discountrules'));
		if (in_array('propalcard', $context))
		{
			if ($action == 'statut' && $object->statut == Propal::STATUS_VALIDATED) {
				// on override l'action pour ne pas passer dans le code standard de 'statut'.
				// le code standard est remplacé par un code modifié (avec un champ de formulaire en plus) dans
				// printCommonFooter(), puis la valeur de ce champ est réinterceptée ici.
				$action = 'statut_override';
			} elseif ($action == 'setstatut'
					  && GETPOST('statut', 'int') == Propal::STATUS_SIGNED
					  && GETPOSTISSET('updateDiscountRule')
			) {
				dol_include_once('/discountrules/class/discountrule.class.php');
				$linesToUpdate = GETPOST('updateDiscountRule', 'array');
				$linesToUpdate = (is_array($linesToUpdate)) ? array_keys($linesToUpdate) : array();
				// pour créer ou mettre à jour des règles de remise, on a besoin
				// - du client
				// - du projet (si existant)
				// - du produit
				// - du pourcentage de remise
				// - de la quantité
				$customerId = intval($object->socid);
				$projectId = intval($object->fk_project);
				$line = new PropaleLigne($this->db);
				$error = 0;
				foreach ($linesToUpdate as $lineId) {
					if ($line->fetch($lineId) <= 0) {
						$error++;
						continue;
					}
					$discountPercent = $line->remise_percent;
					$productId = $line->fk_product;
					// TODO: optimiser en passant l'ID de la règle à remplacer dans l'URL; 0 si règle à créer
					$discountrule = $this->_findDiscountRuleMatchingLine($object, $line);
					if ($discountrule === null) {
						dol_print_error($this->db);
						continue;
					}
					elseif (empty($discountrule->id)) {
						$discountrule = new discountrule($this->db);
						$discountrule->fk_product = $productId;
						$discountrule->from_quantity = 0; // vu avec Arnaud : pas de qté min pour les règles créées
						$discountrule->fk_status = discountrule::STATUS_ACTIVE;
						$discountrule->fk_company = $customerId;
						$discountrule->fk_project = $projectId;
						$product = new Product($this->db);
						if ($product->fetch($line->fk_product) <= 0) {
							$error++;
							continue;
						}
						$discountrule->label = $product->ref . '_' . ('cust_' . $customerId) . ($projectId ? ('_proj_' . $projectId) : '');
					}
					$discountrule->reduction = $discountPercent;

					if ($discountrule->id) {
						$res = $discountrule->updateCommon($user);
					} else {
						$res = $discountrule->createCommon($user);
					}
					if ($res < 0) {
						$this->errors += $discountrule->errors;
						$this->error = $discountrule->error;
						return -1;
					}
				}
				if (empty($this->errors)) {
					setEventMessages($langs->trans('RulesUpdated'), array(), 'mesgs');
				}
			}
		}

		if (array_intersect(array('propalcard', 'ordercard', 'invoicecard'), $context)) {
			$confirm = GETPOST('confirm', 'alpha');
			dol_include_once('/discountrules/class/discountrule.class.php');
			include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			if ($action === 'askUpdateDiscounts') {
				global $delayedhtmlcontent;

				$form = new Form($this->db);
				$formconfirm = $form->formconfirm(
					$_REQUEST['PHP_SELF'] . '?id=' . $object->id . '&token=' . $_SESSION['newtoken'],
					$langs->trans('confirmUpdateDiscountsTitle'),
					$langs->trans('confirmUpdateDiscounts'),
					'doUpdateDiscounts',
					array(), // inputs supplémentaires
					'no', // choix présélectionné
					2 // ajax ou non
				);
				$delayedhtmlcontent .= $formconfirm;
			} elseif ($action === 'doUpdateDiscounts' && $confirm === 'yes') {
				$discountrule = new DiscountRule($this->db);
				$c = new Categorie($this->db);
				$client = new Societe($this->db);
				$client->fetch($object->socid);
				$TCompanyCat = $c->containing($object->socid, Categorie::TYPE_CUSTOMER, 'id');
				$TCompanyCat = DiscountRule::getAllConnectedCats($TCompanyCat);
				foreach ($object->lines as $line) {
					/** @var PropaleLigne|OrderLine|FactureLigne $line */

					$TProductCat = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
					$TProductCat = DiscountRule::getAllConnectedCats($TProductCat);

					// fetchByCrit = cherche la meilleure remise qui corresponde aux contraintes spécifiées
					$res = $discountrule->fetchByCrit(
						$line->qty,
						$line->fk_product,
						$TProductCat,
						$TCompanyCat,
						$object->socid,
						time(),
						$client->country_id,
						$client->typent_id,
						$object->fk_project
					);
					if ($res > 0) {
						$oldsubprice = $line->subprice;
						$oldremise = $line->remise_percent;
						$line->subprice = $discountrule->getProductSellPrice($line->fk_product, $object->socid) - $discountrule->product_reduction_amount;
						// ne pas appliquer les prix à 0 (par contre, les remises de 100% sont possibles)
						if ($line->subprice <= 0 && $oldsubprice > 0) {
							$line->subprice = $oldsubprice;
						}
						$line->remise_percent = $discountrule->reduction;
//						print '<script>console.log(' . json_encode([$oldsubprice, $line->subprice]) . ');</script>';
//						print '<script>console.log(' . json_encode([$oldremise, $line->remise_percent]) . ');</script>';
						// cette méthode appelle $object->updateline avec les bons paramètres
						// selon chaque type d’objet (proposition, commande, facture)
						discountruletools::updateLineBySelf($object, $line);
					} else {
						continue;
					}
				}
			}
		}
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$context = explode(':', $parameters['context']);

		$langs->loadLangs(array('discountrules'));
		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context) )
		{
			/** @var CommonObject $object */
			if(!empty($object->statut)){
				return 0;
			}

			// bouton permettant de rechercher et d'appliquer les règles de remise
			// applicables aux lignes existantes
			print dolGetButtonAction(
				$langs->trans("UpdateDiscountsFromRules"),
				'',
				'default',
				$_REQUEST['PHP_SELF'] . '?id=' . $object->id . '&action=askUpdateDiscounts&token=' . $_SESSION['newtoken'],
				'',
				$user->rights->discountrules->read
			);

			?>
			<!-- MODULE discountrules -->
			<link rel="stylesheet" type="text/css" href="<?php print dol_buildpath('discountrules/css/discountrules.css.php',1); ?>">
			<script type="text/javascript">
				$(document).ready(function(){
					$( "#idprod, #qty" ).change(function() {
						discountUpdate();
					});
					var defaultCustomerReduction = <?php print floatval($object->thirdparty->remise_percent); ?>;
					var lastidprod = 0;
					var lastqty = 0;

					function discountUpdate(){

						if($('#idprod') == undefined || $('#qty') == undefined ){  return 0; }

						var idprod = $('#idprod').val();
						var qty = $('#qty').val();
						if(idprod != lastidprod || qty != lastqty)
						{

							lastidprod = idprod;
							lastqty = qty;

							var urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php',2); ?>";

							$.ajax({
								  method: "POST",
								  url: urlInterface,
								  dataType: 'json',
								  data: {
										'fk_product': idprod, 
										'action': "product-discount",
										'qty': qty,
										'fk_company': '<?php print intval($object->socid); ?>',
										'fk_project' : '<?php print intval($object->fk_project); ?>',
									}
							})
							.done(function( data ) {
								var $inputPriceHt = $('#price_ht');
								var $inputRemisePercent = $('#remise_percent');
								var discountTooltip = data.tpMsg;


								if(data.result && data.element === "discountrule")
								{
									$inputRemisePercent.val(data.reduction);
									$inputRemisePercent.addClass("discount-rule-change --info");

									if(data.subprice > 0){
										// application du prix de base
										$inputPriceHt.val(data.subprice);

										if(data.fk_product > 0) {
											$inputPriceHt.addClass("discount-rule-change --info");
										}
									}
								}
								else if(data.result
									&& (data.element === "facture" || data.element === "commande" || data.element === "propal"  )
								)
								{
									$inputRemisePercent.val(data.reduction);
									$inputRemisePercent.addClass("discount-rule-change --info");
									$inputPriceHt.val(data.subprice);
									$inputPriceHt.addClass("discount-rule-change --info");
								}
								else
								{
									if(defaultCustomerReduction>0)
									{
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
								$inputRemisePercent.attr("title", discountTooltip);
								$inputPriceHt.attr("title", discountTooltip);

								// add tooltip
								if(!$inputRemisePercent.data("tooltipset")){
									$inputRemisePercent.data("tooltipset", true);
									$inputRemisePercent.tooltip({
										show: { collision: "flipfit", effect:"toggle", delay:50 },
										hide: { delay: 50 },
										tooltipClass: "mytooltip",
										content: function () {
											return $(this).prop("title");		/* To force to get title as is */
										}
									});
								}

								if(!$inputPriceHt.data("tooltipset")){
									$inputPriceHt.data("tooltipset", true);
									$inputPriceHt.tooltip({
										show: { collision: "flipfit", effect:"toggle", delay:50 },
										hide: { delay: 50 },
										tooltipClass: "mytooltip",
										content: function () {
											return $(this).prop("title");		/* To force to get title as is */
										}
									});
								}

								// Show tootip
								if(data.result){
									 $inputRemisePercent.tooltip().tooltip( "open" ); //  to explicitly show it here
									 setTimeout(function() {
										 $inputRemisePercent.tooltip().tooltip("close" );
									 }, 2000);
								}
							});
						}
					}
				});
			</script>
			<!-- END MODULE discountrules -->
			<?php
		}
	}



	/*
	 * Overloading the printPDFline function
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$model, &$action, $hookmanager)
	{
		global $langs, $conf;
		// PRODUCTS MASSS ACTION
		if (in_array($parameters['currentcontext'], array('productservicelist','servicelist','productlist')) && !empty($conf->category->enabled))
		{
			$ret='<option value="addtocategory">'.$langs->trans('massaction_add_to_category').'</option>';
			$ret.='<option value="removefromcategory">'.$langs->trans('massaction_remove_from_category').'</option>';
			
			$this->resprints = $ret;
		}

		return 0;
	}

	/*
	 * Overloading the doMassActions function
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db,$action,$langs;

		$massaction = GETPOST('massaction');

		// PRODUCTS MASSS ACTION
		if (in_array($parameters['currentcontext'], array('productservicelist','servicelist','productlist')))
		{
			$TProductsId = $parameters['toselect'];

			// Clean
			if(!empty($TProductsId)){
				$TProductsId=array_map('intval', $TProductsId);
			}else{
				return 0;
			}

			// Mass action
			if($massaction === 'addtocategory' || $massaction === 'removefromcategory'){

				$search_categ = GETPOST('search_categ');

				// Get current categories
				require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
				$c = new Categorie($db);

				$processed = 0;

				// Process
				if ($c->fetch($search_categ) > 0)
				{
					foreach($TProductsId as $id){
						$product = new Product($db);
						$product->fetch($id);
						$existing = $c->containing($product->id, Categorie::TYPE_PRODUCT, 'id');

						$catExist = false;

						// Diff
						if (is_array($existing))
						{
							if(in_array($search_categ, $existing)){
								$catExist = true;
							}
							else {
								$catExist = false;
							}
						}

						// Process
						if($massaction === 'removefromcategory' && $catExist){
							// REMOVE FROM CATEGORY
							$c->del_type($product, 'product');
							$processed++;
						}
						elseif($massaction === 'addtocategory' && !$catExist) {
							// ADD IN CATEGORY
							$c->add_type($product, 'product');
							$processed++;
						}
					}

					setEventMessage($langs->trans('NumberOfProcessed',$processed));
				}
				else
				{
					setEventMessage($langs->trans('CategoryNotSelectedOrUnknow'), 'errors');
				}
			}

		}

		return 0;
	}

	/**
	 * Overloading the completeTabsHead function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;
		if(!empty($parameters['object']) && $parameters['mode'] === 'add')
		{
			$pObject = $parameters['object'];
			if ( in_array($pObject->element, array( 'product', 'societe')))
			{
				if ( $pObject->element == 'product' ){
					$column = 'fk_product';
				}
				elseif ( $pObject->element == 'societe' ){
					$column = 'fk_company';
				}

				if(!empty($parameters['head']))
				{
					foreach ($parameters['head'] as $h => $headV)
					{
						if($headV[2] == 'discountrules')
						{
							$nbRules = 0;
							$resql= $pObject->db->query('SELECT COUNT(*) as nbRules FROM '.MAIN_DB_PREFIX.'discountrule drule WHERE '.$column.' = '.intval($pObject->id).';');
							if($resql>0){
								$obj = $pObject->db->fetch_object($resql);
								$nbRules = $obj->nbRules;
							}

							if ($nbRules > 0)  $parameters['head'][$h][1] = $langs->trans('TabTitleDiscountRule').' <span class="badge">'.($nbRules).'</span>';

							$this->results = $parameters['head'];

							return 1;
						}
					}
				}
			}
		}

		return 0;
	}

	/**
	 * @param array $parameters
	 * @param CommonObject $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 */
	public function printCommonFooter($parameters, &$object, &$action, $hookmanager) {
		global $conf, $user, $langs, $db;
		global $action, $object; // obligatoire car executeHooks est appelé avec des chaînes vides pour ces variables
		$langs->loadLangs(array('discountrules'));
		$context = explode(':', $parameters['context']);
		if (in_array('propalcard', $context) && $action === 'statut_override') {
			// On réaffiche le formconfirm standard, mais on y ajoute la question sur les règles de prix.
			// Inconvénient : nécessite de maintenir ce code en parallèle du code standard (pour rester à jour de
			// l'action 'statut' de propal/card.php), mais il n'existe pas de hook pour éviter ça.
			$form = new Form($db);

			// Form to close proposal (signed or not)
			$formquestion = array(
					array('type' => 'select','name' => 'statut','label' => $langs->trans("CloseAs"),'values' => array(2=>$object->LibStatut(Propal::STATUS_SIGNED), 3=>$object->LibStatut(Propal::STATUS_NOTSIGNED))),
					array('type' => 'text', 'name' => 'note_private', 'label' => $langs->trans("Note"),'value' => '')				// Field to complete private note (not replace)
			);

			if (! empty($conf->notification->enabled)) {
				require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
				$error = 0;
				$notify = new Notify($db);
				$formquestion = array_merge($formquestion, array(
						array('type' => 'onecolumn', 'value' => $notify->confirmMessage('PROPAL_CLOSE_SIGNED', $object->socid, $object)),
				));
			}

			/* Code spécifique DiscountRules */
			// séparateur horizontal
			$subTableLines = array();
			$inputok = array( // noms des inputs et des checkboxes qui seront ajoutés à l’URL par le bouton OK
					'statut',
					'note_private',
			);
			$product = new Product($db);
			$willBeCreatedMsg = '<span title="'
								. dol_escape_htmltag($langs->trans('RuleWillBeCreatedTooltip')) . '">'
								. $langs->trans('RuleWillBeCreated')
								. '</span>';
			/** @var CommonObjectLine $line */
			foreach ($object->lines as $line) {
				$matchingRule = $this->_findDiscountRuleMatchingLine($object, $line);
				if ($matchingRule === null) {
					$error++;
				}
				if (empty($line->remise_percent)) continue;
				if (empty($line->fk_product)) continue;
				if ($product->fetch($line->fk_product) <= 0) continue;
				$checkboxName = 'updateDiscountRule[' . intval($line->id) . ']';
				$checkboxId = 'updateDiscountRule_' . intval($line->id);
				$inputok[] = $checkboxId;
				$remise_percent = price($line->remise_percent) . ' %';
				if ($line->remise_percent == 100)  $remise_percent = $langs->trans('Offered');
				$subTableLines[] = '<tr>'
								   . '<td>' . ($matchingRule->id ? $matchingRule->getNomUrl() : $willBeCreatedMsg) . '</td>'
								   . '<td>' . $product->getNomUrl() . ' – ' . $product->label . '</td>'
//									   . '<td>' . $line->qty . '</td>'
								   . '<td class="right" style="padding-right: 2em">'
								   . '<b>' . $remise_percent . '</b>'
								   . '</td>'
								   . '<td>'
								   . '<input type="checkbox" id="' . $checkboxId . '" name="' . $checkboxName . '" />'
								   . '</td>'
								   . '</tr>';
			}
			$subTable = '<hr/><table id="selectDiscounts" class="discount-rule-selection-table" style="display: none; max-width: 1200px"><thead>'
					. '<tr>'
						. '<th style="max-width: 10em;">' . $langs->trans('CreateOrUpdateRule') . '</th>'
						. '<th>' . $form->textwithtooltip($langs->trans('SelectDiscountsToTurnIntoRules'), $langs->trans('SelectDiscountsToTurnIntoRulesTooltip'), 2,1,img_help(1,'')) . '</th>'
//							. '<th>' . $langs->trans('Qty') . '</th>'
						. '<th>' . $langs->trans('Discount') . '</th>'
						. '<th>' . '<input type="checkbox" id="selectall" name="selectall" title="' . $langs->trans('ToggleSelectAll') . '" />' . '</th>'
						. '</tr>'
						. '</thead><tbody>'
						. implode('', $subTableLines)
						. '</tbody></table>';
			$formquestion[] = array('type' => 'onecolumn', 'value' => $subTable);

			$formconfirmURL = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
			$formconfirmURL_yes = $formconfirmURL . '&action=setstatut&confirm=yes';
			$formconfirm = $form->formconfirm($formconfirmURL, $langs->trans('SetAcceptedRefused'), '', 'setstatut', $formquestion, '', 2, 'auto', 'auto');
			print $formconfirm;
			$jsTrans = array('Yes', );
			$jsVars = array(
					'trans' => array_combine($jsTrans, array_map(function ($t) use ($langs) { return $langs->trans($t); }, $jsTrans)),
					'session' => array('newtoken' => $_SESSION['newtoken']),
					'inputok' => $inputok, // noms des éléments de formulaire à envoyer; modifié pour DiscountRule
					'pageyes' => $formconfirmURL_yes,
					'' => '',
			);
			echo '';

			?>
				<script type="application/javascript">
					$(function () {
						let jsVars = <?php echo json_encode($jsVars); ?>;
						let $dialog = $('#dialog-confirm');
						let overrideDialogActions = function() {
							let dialogButtons = $dialog.dialog('option', 'buttons');
							dialogButtons[jsVars.trans['Yes']] = function () {
								/*
								Ceci est une copie modifiée du callback standard qui est dans html.form > formconfirm;
								seule l'action "Yes" du dialogue est surchargée pour que soient pris en compte les
								checkboxes supplémentaires qui n'ont pas été passées dans le tableau au formconfirm.
								L'action "No" par défaut n'a pas besoin d'être surchargée
								*/
								var options = '&token=' + jsVars.session['newtoken'];
								$.each(jsVars.inputok, function(i, inputId) {
									var more = "";0
									let inputSelector = '#' + inputId;
									let inputType = $(inputSelector).attr('type');
									if (inputType === 'checkbox' || inputType === 'radio') {
										inputSelector += ":checked";
									}
									var inputValue = $(inputSelector).val();
									var inputName = $(inputSelector).attr('name');
									if (typeof inputValue === 'undefined') { return; }
									options += "&" + inputName + "=" + encodeURIComponent(inputValue);
								});
								var urljump = jsVars.pageyes + (jsVars.pageyes.indexOf("?") < 0 ? "?" : "") + options;
								if (jsVars.pageyes.length > 0) {
									// alert(urljump);
									window.location.href = urljump;
								}
								$dialog.dialog("close");
							}
							$dialog.dialog('option', 'buttons', dialogButtons);
						};
						window.setTimeout(function () {
							if ($dialog.hasClass('ui-dialog-content')) {
								overrideDialogActions();
							} else {
								console.error('Dialog is uninitialized; cannot override its "Yes" action');
							}
						}, 0);
						// setTimeout(overrideDialogActions, 500); // TODO C'EST ULTRA MOCHE
						$('#selectall').change(function () {
							$('input[id^="updateDiscountRule"]').prop('checked', $('#selectall').prop('checked'));
						});
						$('#statut').change(function () {
							parseInt($('#statut').val()) !== <?php echo Propal::STATUS_SIGNED ?>
							&& $('table#selectDiscounts').hide()
							|| $('table#selectDiscounts').show();
							$('#dialog-confirm').dialog({
								width: 'auto',
								height: 'auto',
								position: {my: 'center', at: 'center', of: window}
							});
						})
					});
				</script>
			<?php
		}
	}

	/**
	 * Retourne la règle DiscountRule applicable à la ligne de document (proposition, commande, facture) ou null si non
	 * trouvée.
	 *
	 * @param CommonObject $object  Propal
	 * @param CommonObjectLine $line
	 *
	 * @return DiscountRule|null  null = database error;
	 *                            Note that the DiscountRule returned may be uninitialized (= no rule found)
	 */
	private function _findDiscountRuleMatchingLine($object, $line)
	{
		include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$discountrule = new DiscountRule($this->db);
//		$c = new Categorie($this->db);
//		$client = new Societe($this->db);
//		$client->fetch($object->socid);
//
//		$TCompanyCat = $c->containing($object->socid, Categorie::TYPE_CUSTOMER, 'id');
//		$TCompanyCat = DiscountRule::getAllConnectedCats($TCompanyCat);
//
//		$TProductCat = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
//		$TProductCat = DiscountRule::getAllConnectedCats($TProductCat);
//		$res = $discountrule->fetchByCrit(
//				$line->qty,
//				$line->fk_product,
//				$TProductCat,
//				$TCompanyCat,
//				$object->socid,
//				time(),
//				$client->country_id,
//				$client->typent_id,
//				$object->fk_project
//		);
//		if ($res < 0) return null;
//		return $discountrule;
		
		// note: $object->socid and $line->fk_product are mandatory
		$criteria = array(
				'rule.fk_status = ' . intval(discountrule::STATUS_ACTIVE),
				'rule.reduction IS NOT NULL',
				// match only rules without a from_quantity criterion
				'rule.from_quantity = 0',
				// third party is mandatory (rules without a third party won't be updated)
				'rule.fk_company = ' . intval($object->socid),
				// product is mandatory (rules without a product won't be updated)
				'rule.fk_product = ' . doubleval($line->fk_product),
		);

		if (!empty($object->fk_project)) {
			$criteria[] = 'rule.fk_project = ' . intval($object->fk_project);
		} else {
			$criteria[] = '(rule.fk_project = 0)';
		}

		$sql =
			/** @lang SQL */
			'SELECT rule.rowid AS id FROM ' . MAIN_DB_PREFIX . 'discountrule AS rule'
				. ' WHERE ' . implode(' AND ', $criteria)
				. ' ORDER BY rule.reduction DESC, rule.from_quantity DESC LIMIT 1';

		$resql = $this->db->query($sql);

		if (!$resql) {
			return null;
		}
		$obj = $this->db->fetch_object($resql);
		if (!empty($obj->id)) {
			$resfetch = $discountrule->fetch($obj->id);
			if ($resfetch < 0) {
				return null;
			}
		}
		return $discountrule;
	}
}
