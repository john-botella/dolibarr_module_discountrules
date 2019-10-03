<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file        class/discountrule.class.php
 * \ingroup     discountrules
 * \brief       This file is a CRUD class file for discountrule (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for discountrule
 */
class discountrule extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'discountrule';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'discountrule';
	const table_element_category_product = 'discountrule_category_product';
	const table_element_category_company = 'discountrule_category_company';

	/**
	 * @var array  Does this field is linked to a thirdparty ?
	 */
	protected $isnolinkedbythird = 1;
	/**
	 * @var array  Does discountrule support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	protected $ismultientitymanaged = 1;
	/**
	 * @var string String with name of icon for discountrule
	 */
	public $picto = 'discountrules@discountrules';


    /**
     * Activate status
     */
    const STATUS_ACTIVE = 1;
    /**
     * Disabled status
     */
    const STATUS_DISABLED = 0;


    public $rowid;
    public $entity;
    public $status;
    public $date_creation;
    public $tms;
    public $import_key;

    //public $fk_category_product;
    public $fk_category_supplier;
    //public $fk_category_company ;
    public $fk_country;
    public $fk_company;

    public $from_quantity;
    public $reduction;
    public $fk_reduction_tax;
    public $reduction_type;
    public $date_from;
    public $date_to;


    public $TCategoryProduct = array();
    public $TCategoryCompany = array();

	/**
	 *             'type' if the field format, 'label' the translation key, 'enabled' is a condition when the filed must be managed,
	 *             'visible' says if field is visible in list (-1 means not shown by default but can be aded into list to be viewed)
	 *             'notnull' if not null in database
	 *             'index' if we want an index in database
	 *             'position' is the sort order of field
	 *             'searchall' is 1 if we want to search in this field when making a search from the quick search button
	 *             'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *             'comment' is not used. You can store here any text of your choice.
	 *             'input' is used for card form
	 */

	/**
	 * @var array  Array with all fields and their property
	 */
	public $fields=array(
		'rowid' => array(
		      'type'=>'integer', 
		    'label'=>'TechnicalID', 
		    'visible'=>-1, 
		    'enabled'=>1,
		    'position'=>1,
		    'notnull'=>1, 
		    'index'=>1,
		    'comment'=>'Id',
		    'search'=>1,
		),
	    'label' => array(
	        'type'=>'varchar(255)',
	        'label'=>'Label',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>1,
	        'default_value'=>'',
	        'search'=>1,
	        'input' => array(
	            'input' => array(
	                'type' => 'text', //{'text', 'select', 'textarea', 'radio', 'checkbox', 'file', 'shop', 'asso_shop', 'free', 'color'},
	            ),
	        ),
	    ),
		'entity' => array(
		    'type'=>'integer',
		    'label'=>'Entity',
		    'visible'=>0,
		    'enabled'=>1,
		    'position'=>20,
		    'notnull'=>1,
		    'index'=>1,
		    'input' => array(
		        'type' => 'none', //{'text', 'select', 'textarea', 'radio', 'checkbox', 'file', 'shop', 'asso_shop', 'free', 'color'},
		    ),
		),
	    'from_quantity' => array(
	        'type'=>'integer',
	        'label'=>'FromQty',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>40,
	        'notnull'=>1,
	        'default_value' => 1,
	        'search'=>1,
	    ),
		'date_creation' => array(
		    'type'=>'datetime', 
		    'label'=>'DateCreation', 
		    'visible'=>-1, 
		    'enabled'=>1, 
		    'position'=>500, 
		    'notnull'=>1,
		),
		'tms' => array(
		    'type'=>'timestamp', 
		    'label'=>'DateModification', 
		    'visible'=>-1, 'enabled'=>1, 
		    'position'=>500, 
		    'notnull'=>1,
		),
	    'import_key' => array(
	        'type'=>'varchar(14)',
	        'label'=>'ImportKey',
	        'visible'=>-1,
	        'enabled'=>1,
	        'position'=>1000,
	        'index'=>1,
	        'search'=>1,
	    ),
	    /*'fk_category_product' =>array(
	        'type'=>'integer',
	        'label'=>'ProductCategory',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>1,
	        'comment'=>'Product Category ID',
	        'default_value'=>0,
	        'input' => array(
	            'type' => 'callback',
	            'callback' => array('Form', 'select_all_categories'),
	            'callbackParam' => array('product', 'field' => 0, 'fk_category_product', 64, 0, 0),
	        ),
	    ),*/
	   /* 'fk_category_company' =>array(
	        'type'=>'integer',
	        'label'=>'ClientCategory',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>1,
	        'comment'=>'Product company ID',
	        'default_value'=>0,
	        'input' => array(
	            'type' => 'callback',
	            'callback' => array('Form', 'select_all_categories'),
	            'callbackParam' => array('customer', 'field' => 0, 'fk_category_company', 64, 0, 0),
	        ),
	    ),*/
	    'fk_country' =>array(
	        'type'=>'integer',
	        'label'=>'Country',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>1,
	        'comment'=>'Country ID',
	        'default_value'=>1,
	        'input' => array(
	            'type' => 'callback',
	            'callback' => array('Form', 'select_country'),
	            'callbackParam' => array('field' => 0, 'fk_country'),
	        ),
	        
	        'search'=>1,
	    ),
	    'fk_company' =>array(
	        'type'=>'integer',
	        'label'=>'Customer',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>1,
	        'comment'=>'Customer ID',
	        'default_value'=>0,
	        'input' => array(
	            'type' => 'callback',
	            'callback' => array('Form', 'select_thirdparty_list'),
	            'callbackParam' => array( 'field' => 0, 'fk_company', '', 1, 'customer'),
				'help' => 'DiscountRuleFieldHelp_fk_company'
	        ),
	        'search'=>1,
	    ),
	    /*'fk_company' =>array(
	        'type'=>'integer',
	        'label'=>'Customer',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>1,
	    ),*/
	    'reduction' =>array(
	        'type'=>'double(24,8)',
	        'label'=>'Discount',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>0,
	        'comment'=>'',
	        'input' => array(
	            'type' => 'text', //{'text','date', 'select', 'textarea', 'radio', 'checkbox', 'file', 'shop', 'asso_shop', 'free', 'color'},
	            'placeholder'=> 'xx,xx',
	        ),
	        'search'=>1,
	    ),
	    'reduction_type' =>array(
	        'type'=>'enum(\'amount\',\'percentage\')',
	        'label'=>'DiscountType',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>0,
	        'comment'=>'Reduction type',
	        'default_value'=>'percentage',
	        'input' => array(
	            'type' => 'select', //{'text','date', 'select', 'textarea', 'radio', 'checkbox', 'file', 'shop', 'asso_shop', 'free', 'color'},
	            'options' => array(    // This is only useful if type == select
	                //'amount' => 'amount',
	                'percentage' => 'percentage',
	            ),
	        ),
	    ),
	    'date_from' =>array(
	        'type'=>'date',
	        'label'=>'DateFrom',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>0,
	        'index'=>0,
	        'comment'=>'date from',
	        'input' => array(
	            'type' => 'date',
	        ),
	    ),
	    'date_to' =>array(
	        'type'=>'date',
	        'label'=>'DateEnd',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>0,
	        'index'=>0,
	        'comment'=>'',
	        'input' => array(
	            'type' => 'date',
	        ),
	    ),
	    'status' => array(
	        'type'=>'integer',
	        'label'=>'Status',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1000,
	        'index'=>1,
	        'notnull'=>1,
	        'default_value'=>1,
	        'input' => array(
	            'type' => 'select', //{'text', 'select', 'textarea', 'radio', 'checkbox', 'file', 'shop', 'asso_shop', 'free', 'color'},
	            'options' => array(    // This is only useful if type == select
	                '0' => 'Disable',
	                '1' => 'Enable',
	            ),
	        ),
	        'search'=>1,
	    ),
	);
	





	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
	    $return = parent::fetchCommon($id,$ref);
	    
	    if($return > 0){
	        $this->fetch_categoryCompany();
	        $this->fetch_categoryProduct();
	    }
	    
	    return $return;
	}

	
	/**
	 *	Delete
	 *
	 *	@param	User	$user        	Object user that delete
	 *	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return	int						1 if ok, otherwise if error
	 */
	function delete($user, $notrigger=0)
	{
	    global $conf;
	    
	    $error=0;
	    
	    $this->db->begin();
	    
	    if (! $notrigger)
	    {
	        // Call trigger
	        $result=$this->call_trigger('DISCOUNTRULE_DELETE',$user);
	        if ($result < 0) { $error++; }
	        // End call triggers
	    }
	    
	    $this->TCategoryProduct = array();
	    if ($this->update_categoryProduct(1) < 0){
	        $error++;
	    }

	    $this->TCategoryCompany = array();
	    if ($this->update_categoryCompany(1) < 0){
	        $error++;
	    }
	    
	    if (! $error)
	    {
	        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".$this->id;
	        if ($this->db->query($sql))
	        {
	            $this->db->commit();
	            return 1;
	        }
	        else
	        {
	            $this->error=$this->db->lasterror();
	            $this->db->rollback();
	            return -2;
	        }
	    }
	    else
	    {
	        $this->db->rollback();
	        return -1;
	    }
	}


    /**
     * @param User  $user   User object
     * @return int
     */
    public function setDisabled($user)
    {
        $this->status = self::STATUS_DISABLED;
        $ret = $this->updateCommon($user);
        return $ret;
    }

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setActive($user)
    {
        $this->status = self::STATUS_ACTIVE;
        $ret = $this->updateCommon($user);
        return $ret;
    }

	/**
	 * Function to prepare the values to insert.
	 * Note $this->${field} are set by the page that make the createCommon or the updateCommon.
	 *
	 * @return array
	 */
	private function set_save_query()
	{
	    global $conf;
	    
	    $queryarray=array();
	    foreach ($this->fields as $field=>$info)	// Loop on definition of fields
	    {
	        // Depending on field type ('datetime', ...)
	        if($this->isDate($info))
	        {
	            if(empty($this->{$field}))
	            {
	                $queryarray[$field] = NULL;
	            }
	            else
	            {
	                $queryarray[$field] = $this->db->idate($this->{$field});
	                
	                if($field == 'date_to'){
	                    $queryarray[$field] = empty($this->{$field})?'':dol_print_date($this->{$field},"%Y-%m-%d 23:59:59");
	                }
	                
	                if($field == 'date_from'){
	                    $queryarray[$field] = empty($this->{$field})?'':dol_print_date($this->{$field},"%Y-%m-%d 00:00:00");
	                }
	            }
	        }
	        else if($this->isArray($info))
	        {
	            $queryarray[$field] = serialize($this->{$field});
	        }
	        else if($this->isInt($info))
	        {
	            if ($field == 'entity' && is_null($this->{$field})) $queryarray[$field]=$conf->entity;
	            else
	            {
	                $queryarray[$field] = (int) price2num($this->{$field});
	                if (empty($queryarray[$field])) $queryarray[$field]=0;		// May be rest to null later if property 'nullifempty' is on for this field.
	            }
	        }
	        else if($this->isFloat($info))
	        {
	            $queryarray[$field] = (double) price2num($this->{$field});
	            if (empty($queryarray[$field])) $queryarray[$field]=0;
	        }
	        else
	        {
	            $queryarray[$field] = $this->{$field};
	        }
	        
	        if ($info['type'] == 'timestamp' && empty($queryarray[$field])) unset($queryarray[$field]);
	        if (! empty($info['nullifempty']) && empty($queryarray[$field])) $queryarray[$field] = null;
	    }
	    
	    return $queryarray;
	}
	

	
	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function createCommon(User $user, $notrigger = false)
	{
	    $res = parent::createCommon($user, $notrigger);
        $error= 0;
	    if($res)
	    {
	        if ($this->update_categoryProduct(1) < 0){
	            $error++;
	        }
	        
	        if ($this->update_categoryCompany(1) < 0){
	            $error++;
	        }
	    }
	    else{
	        $error++;
	    }
	    
	    if ($error) {
	        return -1 * $error;
	    } else {
	        return 1;
	    }
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function updateCommon(User $user, $notrigger = false)
	{
	    $error = 0;
	    
	    $fieldvalues = $this->set_save_query();
	    unset($fieldvalues['rowid']);	// We don't update this field, it is the key to define which record to update.
	    unset($fieldvalues['date_creation']);
	    unset($fieldvalues['entity']);
	    
	    foreach ($fieldvalues as $k => $v) {
	        if (is_array($key)){ // $key is not used... I have probabely wanted to to something but what ?
	            $i=array_search($k, $key);
	            if ( $i !== false) {
	                $where[] = $key[$i].'=' . $this->quote($v, $this->fields[$k]);
	                continue;
	            }
	        } else {
	            if ( $k == $key) {
	                $where[] = $k.'=' .$this->quote($v, $this->fields[$k]);
	                continue;
	            }
	        }
	        $tmp[] = $k.'='.$this->quote($v, $this->fields[$k]);
	    }
	    $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET '.implode( ',', $tmp ).' WHERE rowid='.$this->id ;

	    $this->db->begin();
	    if (! $error)
	    {
	        $res = $this->db->query($sql);
	        
	        if ($res===false)
	        {
	            $error++;
	        }

	        if ($this->update_categoryProduct(1) < 0)
	        {
	            $error++;
	        }

	        if ($this->update_categoryCompany(1) < 0)
	        {
	            $error++;
	        }
	    }

	    if (! $error && ! $notrigger) {
	        // Call triggers
	        $result=$this->call_trigger(strtoupper(get_class($this)).'_MODIFY',$user);
	        if ($result < 0) { $error++; } //Do also here what you must do to rollback action if trigger fail
	        // End call triggers
	    }

	    // Commit or rollback
	    if ($error) {
	        $this->db->rollback();
	        return -1 * $error;
	    } else {
	        $this->db->commit();
	        return $this->id;
	    }
	}
	
	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	int  	$notooltip			1=Disable tooltip
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $morecss='', $urlOnly = 0)
	{
		global $db, $conf, $langs;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';

        $label = '<u>' . $langs->trans("discountrules") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->label;

        $url = $url = dol_buildpath('/discountrules/discountrule_card.php',1).'?id='.$this->id;

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("Showdiscountrule");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

        if($urlOnly) return $url;
        
		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

        if ($withpicto)
        {
            $result.=($linkstart.img_object(($notooltip?'':$label), 'discountrules@discountrules', ($notooltip?'':'class="classfortooltip"')).$linkend);
            if ($withpicto != 2) $result.=' ';
		}
		$result.= $linkstart . $this->label . $linkend;
		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs;

        $statusLabel = $statusType = "";

        if ($status == 1){
            $statusLabel = $langs->trans('Enabled');
            $statusType = 'status4';
        }
        if ($status == 0){
            $statusLabel = $langs->trans('Disabled');
            $statusType = 'status5';
        }


		if(function_exists('dolGetStatus'))
        {
            return dolGetStatus($statusLabel, '', '', $statusType, $mode);
        }
		else
        {
            // FOR DOLIBARR < 10

            if ($status == 1){
                $statusType = 'statut4';
            }
            if ($status == 0){
                $statusType = 'statut5';
            }

            if ($mode == 0)
            {
                return $statusLabel;
            }
            if ($mode == 1)
            {
                return $statusLabel;
            }
            if ($mode == 2)
            {
                return img_picto($statusLabel, $statusType ).' '.$statusLabel;
            }
            if ($mode == 3)
            {
                return img_picto($statusLabel, $statusType );
            }
            if ($mode == 4)
            {
                return img_picto($statusLabel, $statusType ).' '.$statusLabel;
            }
            if ($mode == 5)
            {
                return $statusLabel.' '.img_picto($statusLabel, $statusType );
            }
            if ($mode == 6)
            {
                return $statusLabel.' '.img_picto($statusLabel, $statusType );
            }
        }
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	
	
	/**
	 * @param unknown $cat
	 * @param number $deep
	 * @return array|NULL[]
	 */
	static function getCategoryChild($cat,$deep=0)
	{
	    global $db;
	    
	    dol_include_once('categories/class/categorie.class.php');
	    
	    $Tlist = array();
	    
	    $category = new Categorie($db);
	    $res = $category->fetch($cat);
	    
	    $Tfilles = $category->get_filles();
	    if(!empty($Tfilles) && $Tfilles>0)
	    {
	        foreach ($Tfilles as &$fille)
	        {
	            $Tlist[] = $fille->id;
	            
	            $Tchild = getCategoryChild($fille->id,$deep++);
	            if(!empty($Tchild)){
	                $Tlist = array_merge($Tlist,$Tchild);
	            }
	        }
	    }
	    
	    return $Tlist;
	    
	}
	
	/**
	 * @param int $cat
	 * @param number $isParent
	 * @param number $reverse
	 * @return array
	 */
	static function getCategoryParent($cat,$isParent = 0, $reverse = 0)
	{
	    global $db;
	    
	    dol_include_once('categories/class/categorie.class.php');
	    
	    $Tlist = array();
	    
	    if($isParent){
	        $Tlist[] = $cat;
	    }
	    
	    $category = new Categorie($db);
	    $res = $category->fetch($cat);
	   
	    if($res > 0 && !empty( $category->fk_parent ) )
	    {
	        $TParent = self::getCategoryParent($category->fk_parent, 1, 0);
	        if(!empty($TParent)){
                $Tlist = array_merge($Tlist,$TParent);
            }
	    }
	    
	    if($reverse){ 
	        $Tlist = array_reverse ($Tlist); 
	    }
	    
	    return $Tlist;
	    
	}
	
	/**
	 * @param string $col
	 * @param string $val
	 * @return string
	 */
	static function prepareSearch($col, $val, $ignoreEmpty = 0)
	{
	    $sql = '';
	    
	    if($ignoreEmpty && empty($val) ) return '';
	    
	    $in = '0';
	    if(!empty($val)){
	        
	        if(is_array($val)){
	            $val = array_map('intval', $val);
	            $in.= ','.implode(',', $val);
	        }
	        else {
	            $in.= ','.intval($val);
	        }
	        
	    }
	    $sql.= ' AND '.$col.' IN ('.$in.') '; 
	    
	    return $sql;
	}
	
	/**
	 * @param string $col
	 * @param string $val
	 * @return string
	 */
	static function prepareOrderByCase($col, $val)
	{
	    $sql = $col.' DESC ';
	    
	    if(!empty($val) && is_array($val) && count($val)>1)
	    {
	        
	        $sql = ' CASE ';
	        
	        $i = 0;
	        foreach($val as $id)
	        {
	            $i++;
	            if (empty($id)){
	                continue;
	            }
	            
	            $sql.= ' WHEN '.$col.' = '.intval($id).' THEN '.$i.' ';

	        }
	        
	        $sql.= ' ELSE '.PHP_INT_SIZE.' END DESC';
	        
	    }
	    
	    return $sql;
	}
	
	/**
	 * @param number $from_quantity
	 * @param number $fk_category_product
	 * @param number $fk_category_company
	 * @param number $fk_company
	 * @param number $reduction_type
	 * @param number $date
	 * @return int <0 if KO, 0 if not found, > 0 if OK
	 */
	public function fetchByCrit($from_quantity = 1, $fk_category_product = 0, $fk_category_company = 0, $fk_company = 0, $reduction_type = 0, $date = 0, $fk_country = 0, $fk_c_typent = 0)
	{

	    $sql = 'SELECT d.*, cc.fk_category_company, cp.fk_category_product';

		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' d ';
	    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.self::table_element_category_company.' cc ON ( cc.fk_discountrule = d.rowid' ;
        $sql.= self::prepareSearch('cc.fk_category_company', $fk_category_company).' ) ';

	    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.self::table_element_category_product.' cp ON ( cp.fk_discountrule = d.rowid' ;
        $sql.= self::prepareSearch('fk_category_product', $fk_category_product, 1) .') ';

	    $sql.= ' WHERE from_quantity <= '.floatval($from_quantity).' AND `status` = 1 ' ;

	    $sql.= self::prepareSearch('fk_country', $fk_country);
	    $sql.= self::prepareSearch('fk_c_typent', $fk_c_typent);
		$sql.= self::prepareSearch('fk_company', $fk_company);
	    
	    $this->lastFetchByCritResult = false;
	    
	    if(!empty($reduction_type) && in_array($reduction_type, array('amount', 'percentage'))){
	        $sql.= ' AND reduction_type = \''.$this->db->escape($reduction_type).'\'';
	    }
	    
	    
	    if(!empty($date)){
	        $date = $this->db->idate($date);
	    }
	    else {
	        $date = $this->db->idate(time()); 
	    }
	    
	    $sql.= ' AND ( date_from <= \''.$date.'\'  OR date_from IS NULL  OR date_from = \'\' )';
	    $sql.= ' AND ( date_to >= \''.$date.'\' OR date_to IS NULL OR date_to = \'\' )';

//		// test for "FOR ALL CAT"
        $sql.= ' AND ( d.all_category_product > 0 OR cp.fk_discountrule > 0 ) ';
		$sql.= ' AND ( d.all_category_company > 0 OR cc.fk_discountrule > 0 ) ';


	    $sql.= ' ORDER BY reduction DESC, from_quantity DESC, fk_company DESC, '.self::prepareOrderByCase('fk_category_company', $fk_category_company).', '.self::prepareOrderByCase('fk_category_product', $fk_category_product);

	    $sql.= ' LIMIT 1';

	    $res = $this->db->query($sql);
		$this->lastquery = $this->db->lastquery;
	    if($res)
	    {
	        if ($obj = $this->db->fetch_object($res))
	        {
	            $this->lastFetchByCritResult = $obj; // return search result object to know exactly matching parameters
	            return $this->fetch($obj->rowid);
	        }
	    }
	    else
	    {
	        $this->reserror = $this->db->error;
	    }
	    //print '<p>'.$sql.'</p>';
	    return 0;
	}
	
	
	/**
	 * 	Get children of line
	 *
	 * 	@param	int		$id		Id of parent line
	 * 	@return	array			Array with list of children lines id
	 */
	function fetch_categoryCompany()
	{
	    $this->TCategoryCompany=array();
	    
	    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.self::table_element_category_company;
	    $sql.= ' WHERE fk_discountrule = '.$this->id;
	    
	    $resql = $this->db->query($sql);
	    if ($resql)
	    {
	        while ($row = $this->db->fetch_object($resql) )
	        {
	            $this->TCategoryCompany[] = $row->fk_category_company;
	        }
	    }
	    
	    return $this->TCategoryCompany;
	}
	
	
	
	/**
	 * @param boolean $replace  if false do not remove cat not in TCategoryCompany
	 * @return array
	 */
	function update_categoryCompany($replace = false)
	{
	    $TcatList = $this->TCategoryCompany; // store actual
	    $this->fetch_categoryCompany();

	    if(!is_array($this->TCategoryCompany) || !is_array($TcatList) || empty($this->id)){
	        return -1;
	    }

	    // Ok let's show what we got !
	    $TToAdd = array_diff ( $TcatList, $this->TCategoryCompany );
	    $TToDel = array_diff ( $this->TCategoryCompany, $TcatList );
	    
	    if(!empty($TToAdd)){
	        
	        // Prepare insert query
	        $TInsertSql = array();
	        foreach($TToAdd as $fk_category_company){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_company).')';
	        }
	        
	        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.self::table_element_category_company;
	        $sql.= ' (fk_discountrule,fk_category_company) VALUES '.implode(',', $TInsertSql );

	        $resql = $this->db->query($sql);
	        if (!$resql){
	            dol_print_error($this->db);
	            return -2;
	        }
	        else{
	            $this->TCategoryCompany = array_merge($TToDel,$TToAdd);
	        }
	    }
	    
	    if(!empty($TToDel) && $replace){
	        $TToDel = array_map('intval', $TToDel);
	        
	        foreach($TToDel as $fk_category_company){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_company).')';
	        }
	        
	        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.self::table_element_category_company.' WHERE fk_category_company IN ('.implode(',', $TToDel).')  AND fk_discountrule = '.intval($this->id).';';

	        $resql = $this->db->query($sql);
	        if (!$resql){
	            dol_print_error($this->db);
	            return -2;
	        }
	        else{
	            $this->TCategoryCompany = $TToAdd; // erase all to Del
	        }
	    }


        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET all_category_company = '.intval(empty($this->TCategoryCompany)).' WHERE rowid='.$this->id ;
        $resql = $this->db->query($sql);
        if (!$resql){
            dol_print_error($this->db);
            return -3;
        }
	    
	    return 1;
	}
	
	/**
	 * 	Get children of line
	 *
	 * 	@param	int		$id		Id of parent line
	 * 	@return	array			Array with list of children lines id
	 */
	function fetch_categoryProduct()
	{
	    $this->TCategoryProduct=array();
	    
	    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.self::table_element_category_product;
	    $sql.= ' WHERE fk_discountrule = '.$this->id;
	    
	    $resql = $this->db->query($sql);
	    if ($resql)
	    {
	        while ($row = $this->db->fetch_object($resql) )
	        {
	            $this->TCategoryProduct[] = $row->fk_category_product;
	        }
	    }
	    
	    return $this->TCategoryProduct;
	}
	
	

	/**
	 * @param boolean $replace  if false do not remove cat not in TCategoryProduct
	 * @return array
	 */
	function update_categoryProduct($replace = false)
	{
	    $TcatList = $this->TCategoryProduct; // store actual 
	    $this->fetch_categoryProduct();

	    if(!is_array($this->TCategoryProduct) || !is_array($TcatList) || empty($this->id)){
	        return -1;
	    }

	    // Ok let's show what we got !
	    $TToAdd = array_diff ( $TcatList, $this->TCategoryProduct );
	    $TToDel = array_diff ( $this->TCategoryProduct, $TcatList );
	    
	    if(!empty($TToAdd)){
	        
	        // Prepare insert query
	        $TInsertSql = array();
	        foreach($TToAdd as $fk_category_product){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_product).')';
	        }
	        
	        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.self::table_element_category_product;
	        $sql.= ' (fk_discountrule,fk_category_product) VALUES '.implode(',', $TInsertSql );
	        
	        $resql = $this->db->query($sql);
	        if (!$resql){
	            return -2;
	        }
	        else{
	            $this->TCategoryProduct = array_merge($TToDel,$TToAdd); // erase all to Del
	        }
	    }
	    
	    if(!empty($TToDel) && $replace){
	        $TToDel = array_map('intval', $TToDel);

	        foreach($TToDel as $fk_category_product){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_product).')';
	        }
	        
	        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.self::table_element_category_product.' WHERE fk_category_product IN ('.implode(',', $TToDel).')  AND fk_discountrule = '.intval($this->id).';';
	        
	        $resql = $this->db->query($sql);
	        if (!$resql){
	            return -2;
	        }
	        else{
	            $this->TCategoryProduct = $TToAdd; // erase all to Del
	        }
	    }


        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET all_category_product = '.intval(empty($this->TCategoryProduct)).' WHERE rowid='.$this->id ;
        $resql = $this->db->query($sql);
        if (!$resql){
            dol_print_error($this->db);
            return -3;
        }
	    
	    return 1;
	}

	static public function searchDiscountInDocuments($element, $fk_product, $fk_company, $from_quantity = 1)
    {
        global $conf, $db;

        $table = $tableDet = $fkObjectCol = false;

        $refCol = 'ref';
        $fk_product = intval($fk_product);
        $fk_company = intval($fk_company);

        if($element === 'facture'){
            $table          = 'facture';
            $tableDet       = 'facturedet';
            $fkObjectCol    = 'fk_facture';
            if(intval(DOL_VERSION) < 10) $refCol = 'facnumber';
        }
        elseif($element === 'commande'){
            $table          = 'commande';
            $tableDet       = 'commandedet';
            $fkObjectCol    = 'fk_commande';
        }
        elseif($element === 'propal'){
            $table          = 'propal';
            $tableDet       = 'propaldet';
            $fkObjectCol    = 'fk_propal';
        }

        if(empty($table)){
            return false;
        }

        $sql = 'SELECT line.remise_percent, object.rowid, object.'.$refCol.' as ref, object.date_valid, object.entity, line.qty ' ;

        $sql.= ' FROM '.MAIN_DB_PREFIX.$tableDet.' line ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.$table.' object ON ( line.'.$fkObjectCol.' = object.rowid ) ';

        $sql.= ' WHERE object.fk_statut > 0 ';
        $sql.= ' AND object.fk_soc = '. $fk_company;
        $sql.= ' AND line.fk_product = '. $fk_product;
        $sql.= ' AND object.entity = '. $conf->entity;

        if(!empty($from_quantity)){
            $sql.= ' AND line.qty = '.$from_quantity;
        }


        if(!empty($conf->global->DISCOUNTRULES_SEARCH_DAYS)){
            $sql.= ' AND object.date_valid >= CURDATE() - INTERVAL '.abs(intval($conf->global->DISCOUNTRULES_SEARCH_DAYS)).' DAY ';
        }

        $sql.= ' ORDER BY line.remise_percent DESC ';

        $sql.= ' LIMIT 1';

        $res = $db->query($sql);

        if($res)
        {
            if ($obj = $db->fetch_object($res))
            {
                $obj->date_valid = $db->jdate($obj->date_valid);
                $obj->element = $element;
                return $obj;
            }
        }
        else
        {
            $db->reserror = $db->error;
        }
        //print '<p>'.$sql.'</p>';
        return $sql;
    }
	
}
