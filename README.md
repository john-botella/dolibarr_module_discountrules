
# DOLIBARR MODULE DISCOUNT RULES
Last published version : 

![Last realease](https://img.shields.io/github/v/release/ATM-Consulting/dolibarr_module_discountrules)

Discount Rules is a Dolibarr module useful to discounts and prices...

## LICENSE
Copyright (C) 2019 ATM Consulting <contact@atm-consulting.fr>
Discount Rules is released under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version (GPL-3+).

See the [COPYING](https://github.com/Dolibarr/dolibarr/blob/develop/COPYING) file for a full copy of the license.

## INSTALL

### Dependency 
DOLIBARR minimum version 11.0

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go to
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.


Note: If this screen tells you there is no custom directory, check your setup is correct: 

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
        
### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/discountrules```

```sh
cd ....../custom
git clone git@github.com:gitlogin/discountrules.git discountrules
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module
