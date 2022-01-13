<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
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
if(is_file('../main.inc.php'))$dir = '../';
else  if(is_file('../../../main.inc.php'))$dir = '../../../';
else $dir = '../../';

if(!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
	include($dir."master.inc.php");
}
elseif(!defined('INC_FROM_DOLIBARR')) {
	include($dir."main.inc.php");
} else {
	global $dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass,$dolibarr_main_db_type;
}
if(!defined('DB_HOST')) {
	define('DB_HOST',$dolibarr_main_db_host);
	define('DB_NAME',$dolibarr_main_db_name);
	define('DB_USER',$dolibarr_main_db_user);
	define('DB_PASS',$dolibarr_main_db_pass);
	define('DB_DRIVER',$dolibarr_main_db_type);
}



//require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('discountrules/lib/discountrules.lib.php');
dol_include_once('discountrules/class/importrule.class.php');



if(empty($user->rights->discountrules->create)) accessforbidden();
$langs->load('importdiscountrules@discountrules');



$action = GETPOST('action');

$startLine 		= GETPOSTISSET('startLine','int') ? GETPOST('startLine','int') : 2;
$endline		= GETPOSTISSET('endline','int') ? GETPOST('endline','int') : '';




// a cause de la redirection qui previent le ctrl+r on doit mémoriser les vars
// et les substituer le cas échéant
if (isset($_SESSION['startLine']) && isset($_SESSION['endline'])){
	$startLine = $_SESSION['startLine'];
	$endline = $_SESSION['endline'];
}

switch ($action) {
	case 'importCSV':

		$filename = GETPOST('CSVF$actionile', 'alpha');

		if (isset($_FILES['CSVFile'])) {

			$filePath = $_FILES['CSVFile']['tmp_name'];

			$importRule = new ImportRule($db);
			$_SESSION['TLog'] = $importRule->idrGetDiscountFromCSV(
					$filePath,
					GETPOST('srcEncoding', 'alpha'),
				'idr' . date('Ymd'),
				$startLine,
				$endline
			);

			if (count(array_filter($_SESSION['TLog'], function ($logLine) { return $logLine['type'] === 'error'; }))) {
				echo '<details open class="idr"><summary><h2>'. $langs->trans('Errors').'</h2></summary>';

			} else {
				echo '<details open class="idr"><summary><h2>'. $langs->trans('importDone').'</h2></summary>';
			}
			$lineNumber = 1;

			$linecount = count(file($filePath));
			$_SESSION['startLine'] = $startLine;
			$_SESSION['endline'] = $endline;

			if (( $startLine > $endline && $endline > 0 ) ){
				setEventMessage($langs->trans("startmustbeInferior","errors"));
			}

			if ( $startLine > $linecount ){
				setEventMessage($langs->trans("startafterendOffileLine","errors"));
			}

			header('Location: '.$_SERVER['PHP_SELF']);
			exit;

		}
	default:
		llxHeader('<link rel="stylesheet" href="' . dol_buildpath('/discountrules/css/idr.css', 1) . '" />');
		$form = new Form($db);
		print_barre_liste($langs->trans("idrImportDiscountRules"), 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, -1, '', 0, '', '', 0, 1, 1);
		if (isset($_SESSION['TLog'])){
			$lineNumber = 1;
			echo '<table class="idr import-log">';
			foreach ($_SESSION['TLog'] as $logLine) {
				echo '<tr class="log-' . $logLine['type'] . '"><td>' . $logLine['msg'] . '</td></tr>';
			}
			echo '</table>';
			echo '</details>';
			echo '<hr/>';
			unset($_SESSION['TLog']);
		}

		showImportForm($form,$startLine,$endline);
		showHelp();
}
// todo: mettre dans fonction show_form_create()

llxFooter();



function showImportForm($form,$startline,$endline) {
	global $langs;

	$acceptedEncodings = array(
		'UTF-8',
		'latin1',
		'ISO-8859-1',
		'ISO-8859-15',
		'macintosh'
	);
	?>
	<form method="POST" enctype="multipart/form-data">
		<label for="CSVFile">
			<?php echo $langs->trans('PickCSVFile'); ?> :
		</label>
		<input type="hidden" name="action" value="importCSV" />
		<input type="hidden" name="token" value="<?php echo newToken() ?>" />

		<input id="CSVFile" name="CSVFile" type="file" required />
		<br/>
		<br/>
		<label for="srcEncoding">
			<?php print $langs->trans('SelectFileEncoding'); ?>
		</label>
		<select id="srcEncoding" name="srcEncoding">
			<?php
			foreach ($acceptedEncodings as $encoding) {
				echo '<option value="' . $encoding . '">' . $encoding . '</option>';
			}
			?>
		</select>
		<br/>
		<br />
		<label for="excludefirstline">

		<?php

			print $langs->trans('startLine');
			print '<input type="number" class="maxwidth50" name="startLine" value="'.$startline.'">-' ;
			print $form->textwithpicto("", $langs->trans("SetThisValueTo2ToExcludeFirstLine"));
			print '<input type="number" class="maxwidth50" name="endline" value="'.$endline.'">';
		    print $form->textwithpicto("", $langs->trans("KeepEmptyToGoToEndOfFile"));
			unset($_SESSION['StartLine']);
			unset($_SESSION['endline']);
		?>


		<br/>
		<br/>
		<input type="submit" class="button" name="save" value="<?php echo $langs->trans("SubmitCSVForImport") ?>" />


	</form>
	<?php
}

function showHelp() {

	global $langs;

	$key="csv";
	$param="&datatoimport=discountrules_1";
	?>
	<details class="idr" id="idrImportExplanation">
		<summary><h2><?php print $langs->trans("help"); ?></h2></summary>
		<hr>
		<h3>
		<p>
			<?php print img_picto('', 'download', 'class="paddingright opacitymedium"').'<a href="'.DOL_URL_ROOT.'/imports/emptyexample.php?format='.$key.$param.'" target="_blank">'.$langs->trans("DownloadEmptyExample").'</a>'; ?>
		</p>
		</h3>
		<hr>

		<h3><?php print $langs->trans("Columns"); ?></h3>
		<table class="idr help-table">
			<tr><th><?php print $langs->trans("label"); ?></th>

				<td><?php print $langs->trans("labelDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("refProject"); ?></th>

				<td><?php print $langs->trans("refProjectDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("refProduct"); ?></th>

				<td><?php print $langs->trans("refProductDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("refCompany"); ?></th>

				<td><?php print $langs->trans("refCompanyDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("priorityRank"); ?></th>

				<td><?php print $langs->trans("priorityRankDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("cTypeEnt"); ?></th>

				<td><?php print $langs->trans("cTypeEntDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("allCategoryProduct"); ?></th>

				<td><?php print $langs->trans("allCategoryProductDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("allCategoryCompany"); ?></th>

				<td><?php print $langs->trans("allCategoryCompanyDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("reduction"); ?></th>

				<td><?php print $langs->trans("reductionDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("fromQuantity"); ?></th>

				<td><?php print $langs->trans("fromQuantityDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("productPrice"); ?></th>
				<td><?php print $langs->trans("productPriceDesc"); ?> </td>
			</tr>

			<tr>
				<th><?php print $langs->trans("productReductionAmount"); ?></th>
				<td><?php print $langs->trans("productReductionAmountDesc"); ?> </td>
			</tr>
			<tr>
				<th><?php print $langs->trans("dateFrom"); ?></th>
				<td><?php print $langs->trans("dateFromDesc"); ?> </td>
			</tr>
			<tr>
				<th><?php print $langs->trans("dateTo"); ?></th>
				<td><?php print $langs->trans("dateToDesc"); ?> </td>
			</tr>

			<tr>
				<th><?php print $langs->trans("activate"); ?></th>
				<td><?php print $langs->trans("activateDesc"); ?> </td>
			</tr>



		</table>
		<h3><?php print $langs->trans("TechDescCsvTitle"); ?></h3>
		<ul>
			<li><b><?php print $langs->trans("NumbersubTitle"); ?></b>
				<ul>
					<li><?php print $langs->trans("Numbersub-1"); ?></li>
					<li><?php print $langs->trans("Numbersub-2"); ?></li>
					<li><?php print $langs->trans("Numbersub-3"); ?></li></ul>
			<li><b><?php print $langs->trans("EncodeCharsSubTitle"); ?> </b><br><?php print $langs->trans("EncodeCharsSubTitle-2"); ?>
			</li>
			<li><?php print $langs->trans("FieldSeparatorsubTitle"); ?> </li>
			<li><?php print $langs->trans("catSeparatorsubTitle"); ?> </li>
			<li><?php print $langs->trans("StringSeparatorsubTitle"); ?></li>
		</ul>
	</details>
	<?php
}
