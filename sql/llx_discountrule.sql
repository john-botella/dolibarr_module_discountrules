-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

CREATE TABLE IF NOT EXISTS llx_discountrule (
  rowid int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label varchar(255) NOT NULL,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  entity int(11) NOT NULL DEFAULT '0',
  import_key varchar(14) DEFAULT NULL,
  status integer, 
  date_creation datetime NOT NULL, 
--  fk_category_product int(11) NOT NULL DEFAULT '0', -- deprecated
--  fk_category_supplier int(11) NOT NULL DEFAULT '0', -- deprecated
--  fk_category_company int(11) NOT NULL DEFAULT '0', -- deprecated
  fk_country int(11) NOT NULL DEFAULT '0',
  fk_company int(11) NOT NULL DEFAULT '0',
  from_quantity mediumint(8) UNSIGNED NOT NULL,
  reduction decimal(20,6) NOT NULL,
  fk_reduction_tax tinyint(1) NOT NULL DEFAULT '1',
  reduction_type enum('amount','percentage') NOT NULL,
  date_from datetime NULL,
  date_to datetime NULL
  
) ENGINE=innodb;

  
