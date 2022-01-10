<?php

/**
 *		Class toolbox to validate values
*/
include_once 'backupValidate.class.php';

class ImportRule{

	public $db;
	public $filepath;
	public $validate;

	public function __construct($db){

		$this->db = $db;
		$this->validate = new Validate($db);

	}
	/**
	 * @param DoliDB $db
	 * @param string $filePath  Typically path to uploaded CSV file
	 * @param string $srcEncoding
	 * @param string $importKey
	 * @return array|null  Array with
	 */
	function idrGetBatchSerialFromCSV($filePath, $srcEncoding = 'latin1', $importKey='ecImportDiscountRule',$startLine,$endLine = 0 ) {

		/*
			Import / DiscountRules (création et mise à jour des règles de prix).
		*/

		global $conf, $user, $langs;
		// Initialize technical objects
		//$object = new DiscountRule($db);

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
				$objProduct = $this->idrValidateCSVLine($i-1, $TcsvLine);
			} catch (ErrorException $e) {
				$TImportLog[] = $this->newImportLogLine('error', $e->getMessage());
				$errors++;
				continue;
			}
			// si pas d'erreur sur cette ligne on l'ajoute dans le tableau object a mettre en base plus tard.
			if (count($TImportLog) == 0){
				// $TLineValidated[] = $objProduct;
			}
		}

		if ($errors == 0){

			/*try {
				validateDuplicateSerial($TLineValidated);
			} catch (ErrorException $e) {
				$TImportLog[] = newImportLogLine('error', $e->getMessage());
				$TLineValidated = array(); // on reset le tableau
			}*/


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
	 * @param string $type  'error', 'warning', 'info'
	 * @param string $msg   Message
	 * @return array
	 */
	function newImportLogLine($type, $msg) {
		return array('type' => $type, 'msg' => $msg);
	}

	/**
	 * @param int $lineNumber
	 * @param array $lineArray
	 * @return object  Object representing the parsed CSV line
	 * @throws ErrorException
	 */
	function idrValidateCSVLine($lineNumber, $lineArray) {
		global $db, $langs;

		$TFieldName = array('ref_product', 'ref_warehouse','qty', 'batch');
		$arrayProduct = array();

		//@todo reprendre ici


		$ref_product = trim($lineArray[0]);
		$arrayProduct['ref_product'] = trim($lineArray[0]);
		$ref_entrepot = trim($lineArray[1]);
		$arrayProduct['ref_warehouse'] = trim($lineArray[1]);
		$qty = $lineArray[2];
		$batch = trim($lineArray[3]);

		//nb Columns
		try {
			$this->nbColumnsValidation($lineArray, $TFieldName, $langs);
		}catch( ErrorException $e){
			throw $e;
		}
		//Product
		try {
			$p = validateProduct($db, $ref_product, $langs, $lineNumber);
			$arrayProduct['id_product'] = $p->id;
		}catch( ErrorException $e){
			throw $e;
		}
		//warehouse
		try {
			list($arrayProduct['id_warehouse'], $arrayProduct['ref_warehouse']) = validateWareHouse($db, $ref_entrepot, $p, $langs, $lineNumber);
//		$arrayProduct['ref_warehouse'] = getRefWarehouse($db, $arrayProduct['id_warehouse']);

		}catch( ErrorException $e){
			throw $e;
		}
		//Qty
		try {
			$arrayProduct['qty'] =  validateQty($qty, $langs,$p, $lineNumber);
		}catch( ErrorException $e){
			throw $e;
		}
		//Lot/serie
		try {
			$arrayProduct = validateLotSerie($batch, $langs, $lineNumber, $arrayProduct, $p, $db);
		}catch( ErrorException $e){
			throw $e;
		}

		return (object)$arrayProduct;
	}

	/**
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

}
