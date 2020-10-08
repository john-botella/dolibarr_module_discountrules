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
					  && GETPOST('statut', 'int') == 2
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
				$customerId = $object->fk_soc;
				$projectId = $object->fk_projet;
				$line = new PropaleLigne($this->db);
				$discountrule = new discountrule($this->db);
				foreach ($linesToUpdate as $lineId) {
					if ($line->fetch($lineId) <= 0) continue;
					$discountPercent = $line->remise_percent;
					$productId = $line->fk_product;
					$discountrule->fetchByCrit($line->qty, 0, 0, $customerId, 'percent', 0, 0, 0);
					if ($discountrule <= 0) {
						$discountrule->from_quantity = $line->qty;
						$discountrule->fk_company = $customerId;
						$discountrule->reduction_type = 'percent';
						$discountrule->fk_project = $projectId;
						$discountrule->reduction = $discountPercent;
						// TODO: demander à John comment on fait pour faire une règle sur un produit
						//       et lui demander aussi comment on crée une règle.
					}
					$res = $discountrule->createCommon($user);
				}
				exit;
				// Conseils de John : liste je check ou je check pas 
				// y a un moyen de faire apparaître un tableau avec des lignes et lesquelles tu veux màj
				// exemple dans doc2project
				// avenants
				// je prends la conf cachée-là
				
				// je vais sur une facture liée à une commande → petit bouton bleu → ouvre un encart avec la liste
				// pour voir comment l'overlay est créé
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
		    
		    if(!empty($object->statut)){
		        return 0;
		    }
		    
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
							
							//console.log(['discountUpdate is definied', idprod , lastidprod , qty , lastqty ]);
							var urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php',2); ?>";

							 $.ajax({
								  method: "POST",
								  url: urlInterface,
								  dataType: 'json',
								  data: { 
									    'fk_product': idprod,
								    	'get': "product-discount",
								    	'qty': qty,
								    	'fk_company': '<?php print $object->socid; ?>'
								  		}
							  })
							  .done(function( data ) {
							    console.log(data);

							    var input = $('#remise_percent');
							    var discountTooltip = "<strong><?php print $langs->transnoentities('Discountrule'); ?> :</strong><br/>";
							    
							    if(data.result && data.reduction_type === "percentage" && data.element === "discountrule")
							    {
								    input.val(data.reduction);
							    	discountTooltip = discountTooltip + data.label 
							    						+ "<br/><?php print $langs->transnoentities('Discount'); ?> : " +  data.reduction + "%"
							    						+ "<br/><?php print $langs->transnoentities('ProductCategory'); ?> : " +   data.match_on.category_product
														+ "<br/><?php print $langs->transnoentities('ClientCategory'); ?> : " +   data.match_on.category_company
														+ "<br/><?php print $langs->transnoentities('Customer'); ?> : " +   data.match_on.company
									;
							    }
							    else if(data.result && data.reduction_type === "percentage"
                                    && (data.element === "facture" || data.element === "commande" || data.element === "propal"  )
                                )
                                {
                                    input.val(data.reduction);
                                    discountTooltip = discountTooltip + data.label
                                        + "<br/><?php print $langs->transnoentities('Discount'); ?> : " +  data.reduction + "%"
                                        + "<br/><?php print $langs->transnoentities('Date'); ?> : " +   data.date_valid_human
                                        + "<br/><?php print $langs->transnoentities('Qty'); ?> : " +   data.qty
                                    ;
                                }
                                else
							    {
								    if(defaultCustomerReduction>0)
								    {
								    	input.val(defaultCustomerReduction); // appli default customer reduction from customer card
								    	discountTooltip = discountTooltip
			    											+ "<?php print $langs->transnoentities('percentage'); ?> : " +  defaultCustomerReduction + "%" 
			    											+ "<br/>"  +  "<?php print $langs->transnoentities('DiscountruleNotFoundUseCustomerReductionInstead'); ?>"
			    											;
								    }
								    else
								    {
								    	input.val(''); 
								    	discountTooltip = discountTooltip +  "<?php print $langs->transnoentities('DiscountruleNotFound'); ?>";
								    }
							    }

								// add tooltip message
						    	input.attr("title", discountTooltip);

						    	// add tooltip
						    	if(!input.data("tooltipset")){
    						    	input.tooltip({
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
    						    	 input.tooltip().tooltip( "open" ); //  to explicitly show it here
    						    	 setTimeout(function() {
    						    		 input.tooltip( "close" );
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

			if (! empty($conf->notification->enabled))
			{
				require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
				$notify = new Notify($db);
				$formquestion = array_merge($formquestion, array(
						array('type' => 'onecolumn', 'value' => $notify->confirmMessage('PROPAL_CLOSE_SIGNED', $object->socid, $object)),
				));

				/* Code spécifique DiscountRules */
				// séparateur horizontal
				$subTableLines = array();
				$inputok = array( // noms des inputs et des checkboxes qui seront ajoutés à l’URL par le bouton OK
						'statut',
						'note_private',
				);
				$product = new Product($db);
				/** @var CommonObjectLine $line */
				foreach ($object->lines as $line) {
					if (empty($line->remise_percent)) continue;
					if (empty($line->fk_product)) continue;
					if ($product->fetch($line->fk_product) <= 0) continue;
					$checkboxName = 'updateDiscountRule[' . intval($line->id) . ']';
					$inputok[] = $checkboxName;
					$subTableLines[] = '<tr>'
									   . '<td>'
									   . $product->getNomUrl() . ' '
									   . preg_replace('/(?is)<br.*/', '', $line->description)
									   . '</td>'
									   . '<td class="right" style="padding-right: 2em">'
									   . '<b>' . price($line->remise_percent) . ' %</b>'
									   . '</td>'
									   . '<td>'
									   . '<input type="checkbox" id="' . $checkboxName . '" name="' . $checkboxName . '" />'
									   . '</td>'
									   . '</tr>';
				}
				$subTable = '<hr/><table id="selectDiscounts" class="paddingtopbottomonly centpercent" style="display: none"><thead>'
						. '<tr><th colspan="2"><h3>' . $langs->trans('SelectDiscountsToTurnIntoRules') . '</h3></th>'
						. '<th>'
						. '<input type="checkbox" id="selectall" name="selectall" title="' . $langs->trans('ToggleSelectAll') . '" />' . '</th></tr>'
						. '</thead><tbody>'
						. join('', $subTableLines)
						. '</tbody></table>';
				$formquestion[] = array('type' => 'onecolumn', 'value' => $subTable);
			}

			$formconfirmURL = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
			$formconfirmURL_yes = $formconfirmURL . '&action=setstatut&confirm=yes';
			$formconfirm = $form->formconfirm($formconfirmURL, $langs->trans('SetAcceptedRefused'), '', 'setstatut', $formquestion, '', 2, 'auto', 'auto');
			print $formconfirm;
			?>
				<script type="application/javascript">
					$(() => {
						let overrideDialogActions = function() {
							let $dialog = $('#dialog-confirm');
							let dialogButtons = $dialog.dialog('option', 'buttons');
							console.log(dialogButtons);
							dialogButtons["<?php echo dol_escape_js($langs->transnoentities("Yes")); ?>"] = () => {
								/*
								Ceci est une copie modifiée du callback standard qui est dans html.form > formconfirm;
								seule l'action "Yes" du dialogue est surchargée pour que soient pris en compte les
								checkboxes supplémentaires qui n'ont pas été passées dans le tableau au formconfirm.
								L'action "No" par défaut n'a pas besoin d'être surchargée
								*/
								var options = '&token=<?php echo urlencode($_SESSION['newtoken']); ?>';
								// inputok contient les noms des éléments de formulaire à envoyer; modifié pour DiscountRule
								let inputok = <?php echo json_encode($inputok); ?>;
								var pageyes = "<?php echo dol_escape_js($formconfirmURL_yes); ?>";
								if (inputok.length>0) {
									$.each(inputok, function(i, inputname) {
										var more = "";
										if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
										if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
										var inputvalue = $("#" + inputname + more).val();
										if (typeof inputvalue == "undefined") { inputvalue=""; }
										options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
									});
								}
								var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
								//alert(urljump);
								if (pageyes.length > 0) { location.href = urljump; }
								$(this).dialog("close");
							}
							$('#dialog-confirm').dialog('option', 'buttons', dialogButtons);
						};
						setTimeout(overrideDialogActions, 500);
						$('#selectall').change(() => {
							$('input[id^="updateDiscountRule"]').prop('checked', $('#selectall').prop('checked'));
						});
						$('#statut').change(() => {
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
}
