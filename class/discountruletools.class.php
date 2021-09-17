<?php

/**
 * Collection of tools not directly related to DiscountRule
 *
 * Class DiscountRuleTools
 */
class DiscountRuleTools
{
	/**
	 * Helper method: calls $object->updateline() with the right parameters
	 * depending on the object's type (proposal, order or invoice).
	 * TODO: check compatibility with multicurrency
	 *
	 * @param CommonObject $object
	 * @param CommonObjectLine $line
	 * @param int $notrigger
	 * @return int > 0 = success; < 0 = failure
	 */
	static public function updateLineBySelf($object, $line, $notrigger=0)
	{
		if(empty($line->array_options)){
			$line->fetch_optionals();
		}

		$line->subprice_ht_devise = 0;

		if($object->element === 'propal'){
			/** @var Propal $object */
			return $object->updateline($line->id, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->product_label, $line->product_type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->subprice_ht_devise, $notrigger);
		}
		elseif($object->element === 'facture' ){
			/** @var Facture $object */
			return $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit, $line->subprice_ht_devise, $notrigger);
		}
		elseif($object->element === 'commande' ){
			/** @var Commande $object */
			return $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx,$line->localtax2_tx, 'HT', $line->info_bits, $line->date_start, $line->date_end, $line->product_type, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->fk_unit, $line->subprice_ht_devise, $notrigger);
		}

		return -1;

	}
}
