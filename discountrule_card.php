<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       discountrule_card.php
 *		\ingroup    discountrules
 *		\brief      Page to create/edit/view discountrule
 */

//if (! defined('NOREQUIREUSER'))          define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))            define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))           define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))          define('NOREQUIRETRAN','1');
//if (! defined('NOSCANGETFORINJECTION'))  define('NOSCANGETFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOSCANPOSTFORINJECTION')) define('NOSCANPOSTFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOCSRFCHECK'))            define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))           define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))         define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))          define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))          define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))          define('NOREQUIREAJAX','1');         // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
dol_include_once('/discountrules/class/discountrule.class.php');
dol_include_once('/discountrules/lib/discountrules.lib.php');

// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules","other"));

// Get parameters
$id			= GETPOST('id', 'int');
$action		= GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$TCategoryProduct = GETPOST('TCategoryProduct','array');
$TCategoryCompany = GETPOST('TCategoryCompany','array');


// Initialize technical objects
$object = new discountrule($db);

if($id>0)
{
    $object->fetch($id);
}

$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->discountrules->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('discountrulecard'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('discountrule');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all",'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}
//$result = restrictedArea($user, 'discountrules', $id);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals



/*
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error=0;

	if ($cancel)
	{
		if ($action != 'addlink')
		{
			$urltogo=$backtopage?$backtopage:dol_buildpath('/discountrules/discountrule_list.php',1);
			header("Location: ".$urltogo);
			exit;
		}
		if ($id > 0 || ! empty($ref)) $ret = $object->fetch($id,$ref);
		$action='';
	}

	// Action to add record
	if ($action == 'add' && ! empty($user->rights->discountrules->create))
	{
        foreach ($object->fields as $key => $val)
        {
            if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'import_key'))) continue;	// Ignore special fields

            $object->$key=GETPOST($key,'alpha');
            
            if($object->fk_category_product < 0 ){
                $object->fk_category_product = 0;
            }
            
            if($object->fk_category_supplier < 0 ){
                $object->fk_category_supplier = 0;
            }
            
            if($object->fk_category_company < 0 ){
                $object->fk_category_company = 0;
            }
            
            if($object->fk_country < 0 ){
                $object->fk_country = 0;
            }
            
            
            $object->TCategoryProduct =  $TCategoryProduct;
            $object->TCategoryCompany =  $TCategoryCompany;
            
            if ($val['notnull'] && $object->$key == '')
            {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv($val['label'])), null, 'errors');
            }
        }

		if (! $error)
		{
			$result=$object->createCommon($user);
			if ($result > 0)
			{
				// Creation OK
				$urltogo=$backtopage?$backtopage:dol_buildpath('/discountrules/discountrule_list.php',1);
				header("Location: ".$urltogo);
				exit;
			}
			else
			{
				// Creation KO
				if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
				$action='create';
			}
		}
		else
		{
			$action='create';
		}
	}

	// Action to update record
	if ($action == 'update' && ! empty($user->rights->discountrules->create))
	{
	    foreach ($object->fields as $key => $val)
        {
            $object->$key=GETPOST($key,'alpha');
            
            if($object->fk_category_product < 0 ){
                $object->fk_category_product = 0;
            }
            
            if($object->fk_category_supplier < 0 ){
                $object->fk_category_supplier = 0;
            }
            
            if($object->fk_category_company < 0 ){
                $object->fk_category_company = 0;
            }
            
            if($object->fk_country < 0 ){
                $object->fk_country = 0;
            }
            
            
            $object->TCategoryProduct =  $TCategoryProduct;
            $object->TCategoryCompany =  $TCategoryCompany;
            
            
            if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'import_key'))) continue;
            if ($val['notnull'] && $object->$key == '')
            {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv($val['label'])), null, 'errors');
            }
        }

		if (! $error)
		{
			$result=$object->updateCommon($user);
			if ($result > 0)
			{
			    $action='edit';
			    setEventMessage($langs->trans('Saved'));
			}
			else
			{
				// Creation KO
				if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
				$action='edit';
			}
		}
		else
		{
			$action='edit';
		}
	}

	// Action to delete
	if ($action == 'confirm_delete' && ! empty($user->rights->discountrules->delete))
	{
		$result=$object->deleteCommon($user);
		if ($result > 0)
		{
			// Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');
			header("Location: ".dol_buildpath('/discountrules/discountrule_list.php',1));
			exit;
		}
		else
		{
			if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}
}




/*
 * VIEW
 *
 * Put here all code to build page
 */

$form=new Form($db);

llxHeader('','discountrule','');

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';


// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->transnoentitiesnoconv("NewDiscountRule"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	dol_fiche_head(array(), '');

	print '<table class="border centpercent">'."\n";
	print _GenerateFormFields($object);
	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="butAction" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'"> &nbsp; <input type="submit" class="butAction" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"></div>';

	print '</form>';
}



// Part to edit record
if ($id && $action == 'edit')
{
	print load_fiche_titre($langs->trans("discountrules"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	dol_fiche_head();

	print '<table class="border centpercent">'."\n";
	
	// LIST_OF_TD_LABEL_FIELDS_EDIT
	print _GenerateFormFields($object);
	
	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="butAction" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="butAction" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}



// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
    $res = $object->fetch_optionals($object->id, $extralabels);

    $head = discountrulesPrepareHead($object);
	dol_fiche_head($head, 'discount', $langs->trans("CustomerOrder"), -1, 'order');

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
	}

	if (! $formconfirm) {
	    $parameters = array('lineid' => $lineid);
	    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	    if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
	    elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;



	// Object card
	// ------------------------------------------------------------

	$linkback = '<a href="' . DOL_URL_ROOT . '/discountrules/discountrule_list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';



	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';
	// LIST_OF_TD_LABEL_FIELDS_VIEW


	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div><br>';

	dol_fiche_end();

}


// End of page
llxFooter();
$db->close();



// YEAH, THIS IS A TEST : I dont like...
function _GenerateFormFields($object)
{
    
    global $langs,$db,$conf;
    $form=new Form($db);
    $return ='';
    
    foreach($object->fields as $key => $val)
    {
        if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'import_key')) || $val['input']['type'] == 'none' ) continue;
        $return .= '<tr><td';
        $return .= ' class="titlefieldcreate';
        $required = '';
        if ($val['notnull']){
            $return .= ' fieldrequired';
            $required = ' required ';
        }
        $return .= '" >';
        $return .= $langs->trans($val['label']).'</td><td>';
        
        $default_value= isset($val['default_value'])?$val['default_value']:'';
        $value = (GETPOST($key)?GETPOST($key): ($object->id>0?$object->{$key} : $default_value) );
        
        if($val['type'] == 'integer'){
            $value = intval($value);
        }
        elseif($val['type'] == 'date'){
            $value = date('Y-m-d',intval($value));
        }
        
        
        if(!empty($val['input']))
        {
            $input = $val['input'];
            
            $placeholder= !empty($input['placeholder'])?' placeholder="'.$input['placeholder'].'" ':'';
            
            $formField = '<input class="flat" type="'.$input['type'].'" name="'.$key.'" value="'.$value.'" '.$placeholder.$required.' >';
            
            if($input['type'] == 'select')
            {
                foreach ($input['options'] as &$valueLabel)
                {
                    $valueLabel = $langs->trans($valueLabel);
                }
                
                $formField = $form->selectarray($key, $input['options'],$value,!$val['notnull']);
            }
            elseif($input['type'] == 'callback'){

                if($input['callback'][0] == 'Form'){
                    $input['callback'][0] = $form;
                }
                if(is_callable($input['callback'])){
                    
                    $params = !empty($input['param'])?$input['param']:array() ;
                    foreach ($params as $ckey => &$cval)
                    {
                        if($ckey === 'object'){
                            $cval = isset($object->{$cval})?$object->{$cval}:'';
                        }
                        elseif($ckey === 'field'){
                            $cval = $value;
                        }
                    }
                    
                    
                    $formField = call_user_func_array ( $input['callback'] , $params );
                }
            }
            
            // override
            /*if($key == 'fk_category_product' )
            {
                $formField = _generateFormCategorie('product',$key,$value);
            }
            
            if($key == 'fk_category_company')
            {
                $formField = _generateFormCategorie('customer',$key,$value);
            }*/
            
        }
        else
        {
            $formField = '<input class="flat" type="text" name="'.$key.'" value="'.$value.'" '.$required.'>';
        }
        
        $return .= $formField;
        $return .= '</td></tr>';
    }
    if ($conf->categorie->enabled) {
        
        include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
        
        $return .= '<tr><td class="titlefieldcreate" >'.$langs->trans('ProductCategory').'</td><td>';
        $value = GETPOST('TCategoryProduct','array')?GETPOST('TCategoryProduct','array') : $object->TCategoryProduct;
        $return .= _generateFormCategorie(Categorie::TYPE_PRODUCT,'TCategoryProduct',$value);
        $return .= '</td></tr>';
        
        $return .= '<tr><td class="titlefieldcreate" >'.$langs->trans('ClientCategory').'</td><td>';
        $value = GETPOST('TCategoryCompany','array')?GETPOST('TCategoryCompany','array') : $object->TCategoryCompany;
        $return .= _generateFormCategorie(Categorie::TYPE_CUSTOMER,'TCategoryCompany',$value);
        $return .= '</td></tr>';
        
    }
        
        
    return $return;
}


function _generateFormCategorie($type,$name,$selected=array())
{
    global $form;
    $TOptions = $form->select_all_categories($type, $selected, $name, 0, 0, 1);
    return  $form->multiselectarray($name, $TOptions, $selected, $key_in_label=0, $value_as_key=0, $morecss='', $translate=0, $width='100%', $moreattrib='', $elemtype='', $placeholder='', $addjscombo=1);
}

