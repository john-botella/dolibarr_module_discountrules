<?php
/* Copyright (C) 2021 John BOTELLA <john.botella@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/core/class/validate.class.php
 *      \ingroup    core
 *		\brief      File for Utils class
 */


/**
 *		Class toolbox to validate values
 */
class Validate
{

	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var Translate $outputLang
	 */
	public $outputLang;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;



	/**
	 *    Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param Translate   $outputLang output lang for error
	 * @return null
	 */
	public function __construct($db, $outputLang = false)
	{
		global $langs;

		if ($outputLang) {
			$this->outputLang = $langs;
		} else {
			$this->outputLang = $outputLang;
		}

		$outputLang->load('validate');

		$this->db = $db;
	}

	/**
	 * Use to clear errors msg or other ghost vars
	 * @return null
	 */
	protected function clear()
	{
		$this->error = '';
	}

	/**
	 * Use to clear errors msg or other ghost vars
	 *
	 * @param string $errMsg your error message
	 * @return null
	 */
	protected function setError($errMsg)
	{
		$this->error = $errMsg;
	}


	/**
	 * Check for string not empty
	 *
	 * @param string $string to validate
	 * @return boolean Validity is ok or not
	 */
	public function isNotEmptyString($string)
	{
		if (!strlen($string)) {
			$this->error = $this->outputLang->trans('RequireANotEmptyValue');
			return false;
		}
		return true;
	}

	/**
	 * Check for all values in db
	 *
	 * @param array  $values Boolean to validate
	 * @param string $table  the db table name without MAIN_DB_PREFIX
	 * @param string $col    the target col
	 * @return boolean Validity is ok or not
	 * @throws Exception
	 */
	public function isInDb($values, $table, $col)
	{
		if (!is_array($values)) {
			$value_arr = array($values);
		} else {
			$value_arr = $values;
		}

		if (!count($value_arr)) {
			$this->error = $this->outputLang->trans('RequireValue');
			return false;
		}

		foreach ($value_arr as $val) {
			$sql = 'SELECT ' . $col . ' FROM ' . MAIN_DB_PREFIX . $table . " WHERE ";
			$sql .=  $col ." = '" . $this->db->escape($val) . "'"; // nore quick than count(*) to check existing of a row
			$resql = $this->db->getRow($sql);
			if ($resql) {
				continue;
			} else {
				$this->error = $this->outputLang->trans('RequireValidExistingElement');
				return false;
			}
		}

		return true;
	}


}
