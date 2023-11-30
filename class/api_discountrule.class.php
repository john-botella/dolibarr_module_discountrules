<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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

use Luracast\Restler\RestException;

/**
 * \file    class/api_discountrule.class.php
 * \ingroup discountrules
 * \brief   File for API management of discountrule.
 */

/**
 * API class for discountrules discountrule
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class discountruleApi extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'name'
    );

    /**
     * @var DiscountRule $discountrule {@type DiscountRule}
     */
    public $discountrule;

    /**
     * Constructor
     *
     * @url     GET discountrule/
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->discountrule = new DiscountRule($this->db);
    }

    /**
     * Get properties of a discountrule object
     *
     * Return an array with discountrule informations
     *
     * @param 	int 	$id ID of discountrule
     * @return 	array|mixed data without useless information
	 *
     * @url	GET discountrule/{id}
     * @throws 	RestException
     */
    function get($id)
    {
		if(! DolibarrApiAccess::$user->hasRight('discountrules','read')) {
			throw new RestException(401);
		}

        $result = $this->discountrule->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'discountrule not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('discountrule',$this->discountrule->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->discountrule);
    }

    /**
     * List discountrules
     *
     * Get a list of discountrules
     *
     * @param int		$mode		Use this param to filter list
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101') or (t.import_key:=:'20160101')"
     * @return array Array of discountrule objects
     *
     * @url	GET /discountrules/
     */
    function index($mode, $sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->hasRight('societe','client','voir') && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT s.rowid";
        if ((!DolibarrApiAccess::$user->hasRight('societe','client','voir') && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."discountrule as s";

        if ((!DolibarrApiAccess::$user->hasRight('societe','client','voir') && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
        $sql.= " WHERE s.fk_stcomm = st.id";

		// Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $sql.= ' AND s.entity IN ('.getEntity('discountrule').')';
        if ((!DolibarrApiAccess::$user->hasRight('societe','client','voir') && !$socid) || $search_sale > 0) $sql.= " AND s.fk_soc = sc.fk_soc";
        if ($socid) $sql.= " AND s.fk_soc = ".$socid;
        if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $discountrule_static = new DiscountRule($db);
                if($discountrule_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($discountrule_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve discountrule list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No discountrule found');
        }
		return $obj_ret;
    }

    /**
     * Create discountrule object
     *
     * @param array $request_data   Request datas
     * @return int  ID of discountrule
     *
     * @url	POST discountrule/
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->hasRight('discountrules','create')) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->discountrule->$field = $value;
        }
        if( ! $this->discountrule->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->discountrule->id;
    }

    /**
     * Update discountrule
     *
     * @param int   $id             Id of discountrule to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT discountrule/{id}
     */
    function put($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->hasRight('discountrules','create')) {
			throw new RestException(401);
		}

        $result = $this->discountrule->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'discountrule not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('discountrule',$this->discountrule->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->discountrule->$field = $value;
        }

        if($this->discountrule->update($id, DolibarrApiAccess::$user))
            return $this->get ($id);

        return false;
    }

    /**
     * Delete discountrule
     *
     * @param   int     $id   discountrule ID
     * @return  array
     *
     * @url	DELETE discountrule/{id}
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->hasRight('discountrules','delete')) {
			throw new RestException(401);
		}
        $result = $this->discountrule->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'discountrule not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('discountrule',$this->discountrule->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( !$this->discountrule->delete($id))
        {
            throw new RestException(500);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'discountrule deleted'
            )
        );

    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $discountrule = array();
        foreach (discountruleApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $discountrule[$field] = $data[$field];
        }
        return $discountrule;
    }
}
