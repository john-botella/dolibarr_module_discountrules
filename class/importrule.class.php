<?php

/**
 *		Class toolbox to validate values
*/
include_once 'backupValidate.class.php';
include_once 'discountrule.class.php';
include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
class ImportRule{

	public $db;
	public $filepath;
	public $validate;

	public function __construct($db){
		global $langs;
		$this->db = $db;
		$this->validate = new Validate($db,$langs);

	}
	/**
	 * @param DoliDB $db
	 * @param string $filePath  Typically path to uploaded CSV file
	 * @param string $srcEncoding
	 * @param string $importKey
	 * @return array|null  Array with
	 */
	function idrGetDiscountFromCSV($filePath, $srcEncoding = 'latin1', $importKey='ecImportDiscountRule',$startLine,$endLine = 0 ) {

		/*
			Import / DiscountRules (création et mise à jour des règles de prix).
		*/

		global $conf, $user, $langs;

		$TLineValidated =array();
		$errors = 0;

		if (!is_file($filePath)) {
			return array($this->newImportLogLine('error', 'CSVFileNotFound'));
		}

		$TImportLog = array();
		$csvFile = fopen($filePath, 'r');

		$this->db->begin();
		for ($i = 1; $csvValues = fgetcsv($csvFile, '64000', ";", '"'); $i++) {
			$csvValues = array_map(
				function ($val) use ($srcEncoding) {
					if ($srcEncoding === 'UTF-8') return trim($val);
					return iconv($srcEncoding, 'UTF-8', trim($val));
				},
				$csvValues
			);
			$TcsvLine = $csvValues;

			if (empty(implode('', $csvValues))) continue;

			if ($i < $startLine) continue; // skip headers rows
			if ($endLine > 0 && $i > $endLine ) continue; // skip footers rows

			try {
				$objDiscount = $this->idrValidateCSVLine($i-1, $TcsvLine);
			} catch (ErrorException $e) {
				$TImportLog[] = $this->newImportLogLine('error', $e->getMessage());
				$errors++;
				continue;
			}
			// si pas d'erreur sur cette ligne on l'ajoute dans le tableau object a mettre en base plus tard.
			if (count($TImportLog) == 0){
				$TLineValidated[] = $objDiscount;
			}
		}

		if ($errors == 0){



			$date_import = dol_print_date(dol_now(), '%Y%m%d%H%M%S');
			// on injecte les données en bases
			foreach ($TLineValidated as $k => $line){
				try {
					//  create mouvement
					//$successMessage = ibRegisterLotBatch($line, $k+1, $date_import);
					//$TImportLog[] = newImportLogLine('info', $successMessage);
				} catch (ErrorException $e) {
					/*$TImportLog[] = newImportLogLine('error', $e->getMessage());
					$this->db->rollback();
					$TImportLog[] = newImportLogLine('error rollback db');*/
				}
			}
		}

		$this->db->commit();
		return $TImportLog;
	}

	/**
	 * @param int $lineNumber
	 * @param array $lineArray
	 * @return object  Object representing the parsed CSV line
	 * @throws ErrorException
	 */
	function idrValidateCSVLine($lineNumber, $lineArray) {
		global $db, $langs;

		//$TFieldName = array('ref_product', 'ref_warehouse','qty', 'batch');
		 $objDiscount = new DiscountRule($db);

		//$arrayDiscount = array();
		$label 				= trim($lineArray[0]);
		$ref_project 		= trim($lineArray[1]);
		$ref_product 		= trim($lineArray[2]);
		$ref_company 		= trim($lineArray[3]);
		$code_country 		= trim($lineArray[4]);
		$priorityRank 		= trim($lineArray[5]);
		$cTypeEnt 			= trim($lineArray[6]); // *
		$fkReductionTax		= trim($lineArray[7]); // *
		$cat_products 		= trim($lineArray[8]);
		$cat_companys 		= trim($lineArray[9]);
		$reduction 			= $lineArray[10]; // *
		$fromQty			= $lineArray[11];// *
		$productPrice  		= $lineArray[12];// *
		$productReducAmount= $lineArray[13];// *
		$dateFrom 			= $lineArray[14];// *
		$dateTo 			= $lineArray[15];// *

		//@TODO À SUPPRIMER nb Columns
		/*try {
			$this->nbColumnsValidation($lineArray, $TFieldName, $langs);
		}catch( ErrorException $e){
			throw $e;
		}
		*/

		// LABEL
		try {
			$this->validateLabel($label, $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// projet
		try {
			$this->validateProject($ref_project , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//Product and cat_product
		try {
			$this->validateProduct($ref_product, $cat_products, $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//company and cat_company
		try {
			$this->validateCompany($ref_company, $cat_companys, $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// country by code
		try {
			$this->validateCountry($code_country , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// Priority rank
		try {
			$this->validatePriorityRank($priorityRank , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//c_typent

		// fk_reduction



		// reduction
		try {
			$this->validateReduction($reduction , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// fromQuantity
		try {
			$this->validateFromQuantity($fromQty , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}


		/*
		// product_price
		// reduction
		try {
			$this->validateProductPrice($reduction , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}
		*/

		// productReductionAmmount
		try {
			$this->validateproductReductionAmmount($productReducAmount , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}
		// date_from
		try {
			$this->validatedateFrom($dateFrom , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}
		// date_to
		try {
			$this->validatedateTo($dateTo , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// hydrated discount object ready (all datas are cleaned up )
		// then return the object $objDiscount
		return $objDiscount;



	}

	/**
	 * @param string $type  'error', 'warning', 'info'
	 * @param string $msg   Message
	 * @return array
	 */
	function newImportLogLine($type, $msg) {
		return array('type' => $type, 'msg' => $msg);
	}


	/**
	 * todo à supprimer
	 * @param array $lineArray
	 * @param array $TFieldName
	 * @param $langs
	 * @return void
	 * @throws ErrorException
	 */
	function nbColumnsValidation(array $lineArray, array $TFieldName, $langs)
	{
// nb columns validation
		if (count($lineArray) != count($TFieldName)) {
			throw new ErrorException($langs->trans(
				'CSVLineNotEnoughColumns'));
		}
	}


	/**
	 * @param $label
	 * @param $langs
	 * @param $lineNumber
	 * @return void
	 * @throws ErrorException
	 */
	function validateLabel($label, $langs, $lineNumber, &$objDiscount){

		if (!$this->validate->isNotEmptyString($label)) {
			throw new ErrorException($langs->trans(
				'EmptyLabelError',
				$lineNumber+1));
		}else{

			$objDiscount->label = $label;
		}
	}

	/**
	 * @param $ref_product
	 * @param $catProducts
	 * @param $langs
	 * @param $lineNumber
	 * @return int
	 * @throws ErrorException
	 */
	function validateProduct($ref_product, $catProducts, $langs, $lineNumber,  &$objDiscount)
	{
		// fk_product  +  categories product error
		if ($this->validate->isNotEmptyString($ref_product) &&  $this->validate->isNotEmptyString($catProducts)){
			throw new ErrorException($langs->trans(
				'ProductRefAndCatProductError',
				$lineNumber+1,
				$ref_product));
		}

		// default value will be modified later if needed
		$objDiscount->all_category_product = 0;

		// la ref  n'est pas vide
		if ($this->validate->isNotEmptyString($ref_product)){
			//la ref  n'existe pas
			if  ( !$this->validate->isInDb($ref_product,"product","ref")) {
					throw new ErrorException($langs->trans('ProductRefError', $lineNumber + 1, $ref_product));
			}else{
				// load fk_product dans l'obj discount
				$p = new Product($this->db);
				// we are protected by the throwed exception top line
				$p->fetch(0,$ref_product);
				$objDiscount->fk_product = $p->id;
			}

		//categories produits
		}else{
			if ($this->validate->isNotEmptyString($catProducts)){
				// Test des catégories
				$TCactProducts = explode(",",$catProducts);

				foreach ($TCactProducts as $catproduct){
					// in the were param we can't pass $cat->MAP_ID[$cat::TYPE_PRODUCT] instead of 0 ...
					if  ( !$this->validate->isInDb($catproduct,"categorie","label","type = 0")) {
						throw new ErrorException($langs->trans(
							'catProductRefError',
							$lineNumber+1,
							$catproduct));
					}
				}

				// all cat ok store them in object via Tcatprod ou un truc dans le genre
				foreach ($TCactProducts as $cactproduct){
					$c = new Categorie($this->db);
					// no need to test the result here.
					// we are protected by the top for loop throwed exception
					$c->fetch(0,$cactproduct);
					$objDiscount->TCategoryProduct[] = $c->id;
				}
			}else{
				// no fk_product and no cat product we update  all_categories_product to 1
				$objDiscount->all_category_product = 1;
			}

		}
	}

	/**
	 * @param $ref_company
	 * @param $catCompanies
	 * @param $langs
	 * @param $lineNumber
	 * @return int
	 * @throws ErrorException
	 */
	function validateCompany($ref_company, $catCompanies, $langs, $lineNumber,  &$objDiscount)
	{
		// fk_company  +  categories company error
		if ($this->validate->isNotEmptyString($ref_company) &&  $this->validate->isNotEmptyString($catCompanies)){
			throw new ErrorException($langs->trans(
				'CompanyRefAndCatCompanytError', $lineNumber + 1, $ref_company));
		}

		// default value will be modified later if needed
		$objDiscount->all_category_company = 0;

		// la ref  n'est pas vide
		if ($this->validate->isNotEmptyString($ref_company)){
			//la ref  n'existe pas
			if  ( !$this->validate->isInDb($ref_company,"societe","nom")) {
				throw new ErrorException($langs->trans('CompanyRefError', $lineNumber + 1, $ref_company));
			}else{
				// load fk_product dans l'obj discount
				$s = new Societe($this->db);
				// we are protected by the throwed exception top line
				$s->fetch(0,$ref_company);
				$objDiscount->fk_company = $s->id;
			}

			//categories companies
		}else{
			if ($this->validate->isNotEmptyString($catCompanies)){
				// Test des catégories
				$TCactCompanies = explode(",",$catCompanies);

				foreach ($TCactCompanies as $catcompany){
					// in the were param we can't pass $cat->MAP_ID[$cat::TYPE_PRODUCT] instead of 2 ...
					if  ( !$this->validate->isInDb($catcompany,"categorie","label","type = 2")) {
						throw new ErrorException($langs->trans('catCompanyRefError', $lineNumber + 1, $catcompany ));
					}
				}

				// all cat ok store them in object via Tcatprod ou un truc dans le genre
				foreach ($TCactCompanies as $cactcompany){
					$c = new Categorie($this->db);
					// no need to test the result here.
					// we are protected by the top for-loop throwed exception
					$c->fetch(0,$cactcompany);
					$objDiscount->TCategoryCompany[] = $c->id;
				}
			}else{
				// no fk_product and no cat product we update  all_categories_product to 1
				$objDiscount->all_category_company = 1;
			}

		}
	}

	/**
	 * @param $ref
	 * @param $langs
	 * @param $lineNumber
	 * @return void
	 * @throws ErrorException
	 */
	function validateProject($ref, $langs, $lineNumber, &$objDiscount){
		// project
		$objDiscount->fk_project = 0;

		if ($this->validate->isNotEmptyString($ref)  &&  !$this->validate->isInDb($ref,"projet","ref")){
			throw new ErrorException($langs->trans(
				'ProjectRefError', $lineNumber + 1, $ref));
		}else{
			$p = new Project($this->db);
			$p->fetch(0,"$ref");
			// update fk_project
			$objDiscount->fk_project = $p->id;
		}

	}

	/**
	 * @param $code
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function validateCountry($code, $langs, $lineNumber, &$objDiscount){
		// project
		$objDiscount->fk_project = 0;

		if ($this->validate->isNotEmptyString($code)  &&  !$this->validate->isInDb($code,"c_country","code")){
			throw new ErrorException($langs->trans('countryCodefError', $lineNumber + 1, $code));
		}else{
			$c = new Ccountry($this->db);
			$c->fetch(0,$code);
			// update fk_project
			$objDiscount->fk_project = $c->id;
		}
	}

	/**
	 * @param $priorityRank
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function validatePriorityRank($priorityRank , $langs, $lineNumber,$objDiscount)
	{
		// vide pour pas de prio
		if (!$this->validate->isNotEmptyString($priorityRank) || $priorityRank == 0) {
			$objDiscount->priority_rank = 0;
			return 1;
		}

		if ($this->parseNumberFromCSV($priorityRank,"int") == null) {
			throw new ErrorException($langs->trans('priorityRankNumberError', $lineNumber + 1, $priorityRank));
		}

		if ( $priorityRank < 0 ) {
			throw new ErrorException($langs->trans('priorityRankNumberLevelNegativeValueError', $lineNumber + 1, $priorityRank));
		}

		if ( $priorityRank > 5 ) {
			throw new ErrorException($langs->trans('priorityRankNumberLevelPrioError', $lineNumber + 1, $priorityRank));
		}

		$objDiscount->priority_rank = $priorityRank;

	}

	/**
	 * @param $reduction
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function validateReduction($reduction , $langs, $lineNumber,$objDiscount){

		if (!$this->validate->isNotEmptyString($reduction)){
			$objDiscount->reduction = 0;
			return 1;
		}

		// reduction en %
		if ($this->parseNumberFromCSV($reduction,"double") == null){
				throw new ErrorException($langs->trans('reductionTypeNumericError', $lineNumber + 1, $reduction));
		}

		$objDiscount->reduction = $reduction;
	}

	/**
	 * @param $reduction
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function validateProductPrice($productPrice , $ref_product,  $langs, $lineNumber,$objDiscount){

		if (!$this->validate->isNotEmptyString($productPrice)){
			$objDiscount->product_price = 0;
			return 1;
		}
		// je tente de placer un prix produit alors que je n'ai pas inseré de fk_product sur cette ligne
		// du coup grosse boulette  ...
		if ($this->validate->isNotEmptyString($productPrice) && !$this->validate->isNotEmptyString($ref_product)){
			throw new ErrorException($langs->trans('productPriceAndRefProductNotPresentError', $lineNumber + 1, $productPrice));
		}

		//
		if ($this->parseNumberFromCSV($productPrice,"double") == null){
			throw new ErrorException($langs->trans('productPriceTypeNumericError', $lineNumber + 1, $productPrice));
		}

		$objDiscount->reduction = $productPrice;
	}

	/**
	 * @param $fromQty
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return int|void
	 * @throws ErrorException
	 */
	function validateFromQuantity($fromQty , $langs, $lineNumber,$objDiscount){
		if (!$this->validate->isNotEmptyString($fromQty)){
			$objDiscount->product_price = 0;
			return 1;
		}

		if ($this->parseNumberFromCSV($fromQty,"int") == null){
			throw new ErrorException($langs->trans('FromQtyTypeNumericError', $lineNumber + 1, $fromQty));
		}

	}

	/**
	 * @param $productReducAmount
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return int|void
	 * @throws ErrorException
	 */
	function validateproductReductionAmmount($productReducAmount , $langs, $lineNumber,$objDiscount){
		if (!$this->validate->isNotEmptyString($productReducAmount)){
			$objDiscount->product_price = 0;
			return 1;
		}

		if ($this->parseNumberFromCSV($productReducAmount,"double") == null ){
			throw new ErrorException($langs->trans('productReducAmountTypeNumericError', $lineNumber + 1, $productReducAmount));
		}
	}

	/**
	 * @param $dateFrom
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function validatedateFrom($dateFrom , $langs, $lineNumber,$objDiscount){

		if (!$this->validate->isNotEmptyString($dateFrom)){
			$objDiscount->product_price = 0;
			return 1;
		}
		//if ($this->validate->isTimestamp())
	}

	/**
	 * @param $dateTo
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function validatedateTo($dateTo , $langs, $lineNumber,$objDiscount){

		if (!$this->validate->isNotEmptyString($dateTo)){
			$objDiscount->product_price = 0;
			return 1;
		}

	}

	/**
	 * We don’t use price2num because price2num depends on the user configuration
	 * while the numbers from those CSV are always with a comma as a decimal separator.
	 * @param $value
	 * @return float
	 */
	function parseNumberFromCSV($value, $type) {
		global $langs;
		$value = str_replace(',', '.', $value);
		if (!is_numeric($value)) {
			return null;
		}
		if ($type === 'double') return doubleval($value);
		if ($type === 'int') return intval($value);
	}

}
