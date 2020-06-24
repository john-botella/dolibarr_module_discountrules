-- Copyright (C) 2018 John BOTELLA
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


ALTER TABLE llx_discountrule ADD product_price decimal(20,6) NULL;
ALTER TABLE llx_discountrule ADD product_reduction_amount decimal(20,6) NULL;
ALTER TABLE llx_discountrule CHANGE reduction reduction DECIMAL(20,6) NULL;
ALTER TABLE llx_discountrule CHANGE `status` fk_status integer;
ALTER TABLE llx_discountrule DROP `reduction_type`;
