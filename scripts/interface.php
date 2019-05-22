<?php

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Include and load Dolibarr environment variables
$res=0;
if (! $res && file_exists($path."master.inc.php")) $res=@include($path."master.inc.php");
if (! $res && file_exists($path."../master.inc.php")) $res=@include($path."../master.inc.php");
if (! $res && file_exists($path."../../master.inc.php")) $res=@include($path."../../master.inc.php");
if (! $res && file_exists($path."../../../master.inc.php")) $res=@include($path."../../../master.inc.php");
if (! $res) die("Include of master fails");
dol_include_once('discountrules/class/discountrule.class.php');
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules@discountrules","other"));


$get=GETPOST('get');
$put=GETPOST('put');


if($get === 'product-discount')
{
    $productId = GETPOST('fk_product', 'int');
    $qty = GETPOST('qty', 'int');
    $fk_company = GETPOST('fk_company', 'int');
    $fk_category_company = GETPOST('fk_category_company', 'int');
    $fk_country = GETPOST('fk_country', 'int');
    
    // GET SOCIETE CAT
    if(!empty($fk_company))
    {
        $c = new Categorie($db);
        $fk_category_company = $c->containing( $fk_company, Categorie::TYPE_CUSTOMER, 'id');
        
        if(empty($fk_country))
        {
            $societe = new Societe($db);
            if( $societe->fetch($fk_company) > 0 )
            {
                $fk_country = $societe->country_id;
            }
        }
    }
    
   // var_dump($fk_category_company);
    
    
    
    if(empty($qty)) $qty = 1;
    
    $jsonResponse = new stdClass();
    $jsonResponse->result = false;

    
    if( !empty($productId))
    {
       dol_include_once('product/class/product.class.php');
       
       $product = new Product($db);
       
       if($product->fetch($productId) > 0)
       {
           // Get current categories
           $c = new Categorie($db);
           $existing = $c->containing($product->id, Categorie::TYPE_PRODUCT, 'id');

           $catAllreadyTested = array();
           
           $discount = false;
           if(empty($existing)) {
			   $existing = array(0);
		   }

		   //var_dump($existing);
		   foreach ($existing as $cat)
		   {
			   // check if cat is allreadytested
			   if(in_array($cat, $catAllreadyTested)){
				   continue;
			   }

			   $catAllreadyTested[]=$cat;
			   $discountRes = new discountrule($db);
			   $res = $discountRes->fetchByCrit($qty, $cat, $fk_category_company, $fk_company, 'percentage', time(), $fk_country);

			   if($res>0)
			   {
				   if(empty($discount) || $discount->reduction < $discountRes->reduction)
				   {
					   $discount = $discountRes;
					   continue; // skip parent search
				   }
			   }

			   // SEARCH AT PARENT
			   $parents = discountrule::getCategoryParent($cat);
			   if(!empty($parents))
			   {
				   foreach ($parents as $parentCat)
				   {
					   //var_dump('cat '.$parentCat);
					   // check if cat is allreadytested
					   if(in_array($parentCat, $catAllreadyTested)){
						   continue;
					   }

					   $catAllreadyTested[]=$parentCat;

					   $discountRes = new discountrule($db);
					   $res = $discountRes->fetchByCrit($qty, $parentCat, $fk_category_company, $fk_company, 'percentage', time());

					   if($res>0)
					   {
						   if(empty($discount) || $discount->reduction < $discountRes->reduction)
						   {
							   $discount = $discountRes;
							   break; // skip parent search
						   }
					   }
				   }
			   }
		   }

           
           if(!empty($discount))
           {
               $jsonResponse->result = true;
               $jsonResponse->id = $discount->id;
               $jsonResponse->label = $discount->label;
               $jsonResponse->reduction = $discount->reduction;
               $jsonResponse->reduction_type = $discount->reduction_type;
               $jsonResponse->entity = $discount->entity;
               $jsonResponse->status = $discount->status;
               $jsonResponse->date_creation = $discount->date_creation;
               $jsonResponse->match_on = $discount->lastFetchByCritResult;
               if(!empty($discount->lastFetchByCritResult))
               {
                   // ADD humain readable informations from search result
                   
                   $jsonResponse->match_on->category_product = $langs->transnoentities('AllProductCategories');
                   if(!empty($discount->lastFetchByCritResult->fk_category_product)){
                       $c = new Categorie($db);
                       $c->fetch($discount->lastFetchByCritResult->fk_category_product);
                       $jsonResponse->match_on->category_product = $c->label;
                   }
                   
                   $jsonResponse->match_on->category_company =  $langs->transnoentities('AllCustomersCategories');
                   if(!empty($discount->lastFetchByCritResult->fk_category_company)){
                       $c = new Categorie($db);
                       $c->fetch($discount->lastFetchByCritResult->fk_category_company);
                       $jsonResponse->match_on->category_company = $c->label;
                   }

				   $jsonResponse->match_on->company =  $langs->transnoentities('AllCustomers');
				   if(!empty($discount->lastFetchByCritResult->fk_company)){
					   $s = new Societe($db);
					   $s->fetch($discount->lastFetchByCritResult->fk_company);

					   $jsonResponse->match_on->company = $s->name?$s->name:$s->nom;
					   $jsonResponse->match_on->company.= !empty($s->name_alias) ? ' ('.$s->name_alias.')' : '';
				   }

                   
               }
               
               
               
               
           }

       }
        
    }
    
    print json_encode($jsonResponse);
    
}




$db->close();	// Close $db database opened handler
