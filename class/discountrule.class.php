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

// to include after all others
require_once __DIR__.'/../lib/retroCompatibility.lib.php';

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
    //public $fk_category_company ;
    public $fk_country;
    public $fk_company;

    public $from_quantity;
    public $reduction;
    public $fk_reduction_tax;
    public $reduction_type;
    public $date_from;
    public $date_to;

	public $all_category_product;
	public $all_category_company;

    public $TCategoryProduct = array();
    public $TCategoryCompany = array();

	/**
	 *  'type' is the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 */

	/**
	 * @var array  Array with all fields and their property
	 */
	public $fields=array(
		'rowid' => array(
		      'type'=>'integer',
		    'label'=>'TechnicalID',
		    'visible'=> 0,
		    'enabled'=>1,
		    'position'=>1,
		    'notnull'=>1,
		    'index'=>1,
		    'comment'=>'Id',
		    'search'=>1,
		),
	    'label' => array(
			'type' => 'varchar(255)',
			'label' => 'Label',
			'enabled' => 1,
			'visible' => 1,
			'position' => 1,
			'searchall' => 1,
			'css' => 'minwidth200',
			'help' => 'WebInstanceLabelHelp',
			'showoncombobox' => 1
	    ),

		'entity' => array(
			'type' => 'integer',
			'label' => 'Entity',
			'enabled' => 1,
			'visible' => 0,
			'default' => 1,
			'notnull' => 1,
			'index' => 1,
			'position' => 20
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
		    'visible'=> 0,
		    'enabled'=>1, 
		    'position'=>500, 
		    'notnull'=>1,
		),
		'tms' => array(
		    'type'=>'timestamp', 
		    'label'=>'DateModification', 
		    'visible'=> 0,
			'enabled'=> 1,
		    'position'=> 500,
		    'notnull'=> 1,
		),
	    'import_key' => array(
	        'type'=>'varchar(14)',
	        'label'=>'ImportKey',
	        'visible'=> 0,
	        'enabled'=> 1,
	        'position'=> 1000,
	        'index'=> 1,
	        'search'=> 1,
	    ),

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
	        'search'=>1,
	    ),

	    'fk_company' =>array(
			'type' => 'integer:Societe:societe/class/societe.class.php',
			'label' => 'Customer',
			'visible' => 1,
			'enabled' => 1,
			'position' => 80,
			'index' => 1,
			//'help' => 'CustomerHelp'
	    ),

	    'reduction' =>array(
	        'type'=>'double(24,8)',
	        'label'=>'Discount',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>1,
	        'notnull'=>1,
	        'index'=>0,
	        'comment'=>'',
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
	        'default'=>'percentage',
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
	    ),

		'all_category_product' =>array(
			'type' => 'integer',
			'label' => 'ProductCategory',
			'enabled' => 0, // see _construct()
			'notnull' => 0,
			'default' => -1,
			'visible' => -1,
			'position' => 115,
		),

		'all_category_company' =>array(
			'type' => 'integer',
			'label' => 'ClientCategory',
			'enabled' => 0, // see _construct()
			'notnull' => 0,
			'default' => -1,
			'visible' => -1,
			'position' => 115,
		),

	    'status' => array(
			'type' => 'integer',
			'label' => 'Status',
			'enabled' => 1,
			'visible' => 1,
			'notnull' => 1,
			'default' => 0,
			'index' => 1,
			'position' => 2000,
			'langfile' => 'discountrules@discountrules',
			'arrayofkeyval' =>  array(
				self::STATUS_DISABLED => 'Disable',
				self::STATUS_ACTIVE => 'Enable'
			)
	    ),
	);
	





	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;


		if($conf->categorie->enabled){
			$this->fields['all_category_product']['visible'] = -1;
			$this->fields['all_category_company']['visible'] = -1;
		}

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

        return dolGetStatus($statusLabel, '', '', $statusType, $mode);
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
	 * @param string $col database col
	 * @param string $val value to search
	 * @param int $ignoreEmpty si true et que la valeur cherchée est "vide" alors la recherche renvoie tout
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
	    
	    $sql.= ' AND ( date_from <= \''.$date.'\'  OR date_from IS NULL  OR YEAR(`date_from`) = 0 )'; // le YEAR(`date_from`) = 0 est une astuce MySQL pour chercher les dates vides le tout compatible avec les diférentes versions de MySQL
	    $sql.= ' AND ( date_to >= \''.$date.'\' OR date_to IS NULL OR YEAR(`date_to`) = 0 )'; // le YEAR(`date_to`) = 0 est une astuce MySQL pour chercher les dates vides le tout compatible avec les diférentes versions de MySQL

		// test for "FOR ALL CAT"
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
	    // print '<p>'.$sql.'</p>';
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


	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param  array   		$val	       Array of properties for field to show
	 * @param  string  		$key           Key of attribute
	 * @param  string  		$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  		$moreparam     To add more parameters on html input tag
	 * @param  string  		$keysuffix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$morecss       Value for css to define style/length of field. May also be a numeric.
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form;

		if ($conf->categorie->enabled) {
			include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		}

		if(empty($form)){ $form=new Form($this->db); }

		$required = '';
		if(!empty($this->fields['notnull']) && abs($this->fields['notnull']) > 0){
			$required = ' required ';
		}

		if ($key == 'fk_country'){
			$out = $form->select_country($value, $keysuffix.$key.$keyprefix);
		}
		elseif ($key == 'all_category_product'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->generateFormCategorie('product',$keysuffix.'TCategoryProduct'.$keyprefix, $value);
		}
		elseif ($key == 'all_category_company'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->generateFormCategorie('customer',$keysuffix.'TCategoryCompany'.$keyprefix, $value);
		}
		elseif ($key == 'status'){
			$options = array( self::STATUS_DISABLED => $langs->trans('Disable') ,self::STATUS_ACTIVE => $langs->trans('Enable') );
			$out = $form->selectarray($keysuffix.$key.$keyprefix, $options,$value);
		}
		elseif ($key == 'reduction_type')
		{
			$options = array( 'percentage' => $langs->trans('Percentage') );

			if(!empty($this->fk_product)){
				$options['amount'] = $langs->trans('Amount');
			}

			$out = $form->selectarray($keysuffix.$key.$keyprefix, $options,$value);
		}
		elseif ($key == 'reduction')
		{
			$out = '<input '.$required.' class="flat" type="number" name="'.$keysuffix.$key.$keyprefix.'" value="'.$value.'" placeholder="xx.xx" min="0" step="any" >';
		}
		elseif ($key == 'from_quantity')
		{
			$out = '<input '.$required.' class="flat" type="number" name="'.$keysuffix.$key.$keyprefix.'" value="'.$value.'" placeholder="xx" min="0" step="any" >';
		}
		else
		{
			$out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
		}


		return $out;
	}

	/**
	 * Return HTML string to show a field into a page
	 *
	 * @param  string  $key            Key of attribute
	 * @param  string  $moreparam      To add more parameters on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
		return $this->showOutputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
	}

	/**
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param  array   $val		       Array of properties of field to show
	 * @param  string  $key            Key of attribute
	 * @param  string  $value          Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs, $form;

		$out = '';
		if ($key == 'fk_country'){
			if(!empty($value)){
				$tmparray=getCountry($value,'all');
				$out =  $tmparray['label'];
			}
			else{
				$out =  '<span class="discountrule-all-text" >'.$langs->trans('AllCountries').'</span>';
			}
		}
		elseif ($key == 'reduction_type')
		{
			$options = array(
				'percentage' => $langs->trans('Percentage') ,
				'amount' => $langs->trans('Amount')
			);

			if(isset($options[$key])){ $out = $langs->trans($options[$key]); }
		}
		elseif ($key == 'all_category_product'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->getCategorieBadgesList($this->TCategoryProduct);
		}
		elseif ($key == 'all_category_company'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->getCategorieBadgesList($this->TCategoryCompany);
		}
		elseif ($key == 'status'){
			$out =  $this->getLibStatut(5); // to fix dolibarr using 3 instead of 2
		}
		else{
			$out = parent::showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);
		}

		return $out;
	}

	/**
	 * @param $Tcategorie array of category ID
	 * @return string
	 */
	public function getCategorieBadgesList($Tcategorie){
		$toprint = array();
		foreach($Tcategorie as $cid)
		{
			$c = new Categorie($this->db);
			if($c->fetch($cid)>0)
			{
				$ways = $c->print_all_ways();       // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
				foreach($ways as $way)
				{
					$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.($c->color?' style="background: #'.$c->color.';"':' style="background: #aaa"').'>'.img_object('','category').' '.$way.'</li>';
				}
			}
		}

		return '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
	}

	/**
	 *    @param	string|int	            $type				Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
	 *    @param    string		            $name			HTML field name
	 *    @param    array		            $selected    		Id of category preselected or 'auto' (autoselect category if there is only one element)
	 * 	  @return string
	 */
	public function generateFormCategorie($type,$name,$selected=array())
	{
		global $form;
		$TOptions = $form->select_all_categories($type, $selected, $name, 0, 0, 1);
		return  $form->multiselectarray($name, $TOptions, $selected, 0, 0, '', 0, '100%', '', '', '', 1);
	}
}
