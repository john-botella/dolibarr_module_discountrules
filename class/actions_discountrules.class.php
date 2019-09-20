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
							    
							    if(data.result && data.reduction_type == "percentage" && data.element == "discountrule")
							    {
								    input.val(data.reduction);
							    	discountTooltip = discountTooltip + data.label 
							    						+ "<br/><?php print $langs->transnoentities('Discount'); ?> : " +  data.reduction + "%"
							    						+ "<br/><?php print $langs->transnoentities('ProductCategory'); ?> : " +   data.match_on.category_product
														+ "<br/><?php print $langs->transnoentities('ClientCategory'); ?> : " +   data.match_on.category_company
														+ "<br/><?php print $langs->transnoentities('Customer'); ?> : " +   data.match_on.company
									;
							    }
							    else if(data.result && data.reduction_type == "percentage")
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
}
