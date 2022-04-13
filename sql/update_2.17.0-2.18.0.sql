-- Copyright (C) 2018 John BOTELLA
-- Copyright (C) 2022 Gauthier VERDOL
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

UPDATE llx_discountrule LEFT JOIN llx_discountrule_category_project ON (llx_discountrule_category_project.fk_discountrule = llx_discountrule.rowid) SET llx_discountrule.all_category_project = 1 WHERE llx_discountrule_category_project.rowid IS NULL AND llx_discountrule.all_category_project = 0;
