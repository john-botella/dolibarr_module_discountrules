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



ALTER TABLE llx_discountrule
	DROP INDEX rule,
	DROP INDEX category
;

ALTER TABLE llx_discountrule
  ADD KEY rule (entity,fk_category_product,fk_category_supplier,fk_category_compagny,fk_country,fk_compagny,from_quantity),
  ADD KEY category (fk_category_product,fk_category_supplier,fk_category_compagny)
;

--ALTER TABLE llx_discountrule ADD CONSTRAINT llx_discountrule_field_id FOREIGN KEY (fk_field) REFERENCES llx_myotherobject(rowid);

