# CHANGELOG FOR DISCOUNTRULES MODULE

## Not Released

- NEW : un onglet “Règles de prix catalogue” sera ajouté sur les fiches tiers. Cet onglet proposera un tableau des règles de remises applicables à ce client selon qu’elles s’appliquent directement à lui ou à un attribut qu’il possède (catégorie, type de tiers, pays ou projet). *23/08/2021* - 2.11.0
- NEW : Search result return now also current product price and default customer reduction *29/07/2021* - 2.10.0

## 2.9

- FIX : type ent save error *28/07/2021* - 2.9.2
- FIX : Minor v14 compatibility fixes *12/07/2021* - 2.9.1
- NEW : New Dolibarr V14 check module update compatibility and add dynamic about page loader  *02/07/2021* - 2.9.0
- NEW : Add product filter to rules list *15/06/2021* - 2.8.0

## 2.7 - 01/06/2021

- FIX : Priority rank between document and discount rules  *18/07/2021* - 2.7.3
- FIX : Price list default selection for fields *15/06/2021* - 2.7.2
- NEW : Grosse Factorisation du code js - 2.7.0
- FIX : Compatibility V13 - Add token renewal - *18/05/2021* - 2.6.3
- NEW : Display/Export customer prices  - 2.6.0

  This new feature add a new page and entry menu in discounts rules menu call "price list".
  
  This page can simulate prices for different kinds of filters and allow you to export results in csv
  
    **Possible improvement :**
  
    - Add conf on setup page to configure default CSV export options
    - Add conf on setup page to configure default behavior on export null prices (currently not exported)
    - Add dialog box on click export button to choose export options

## 2.5.0 - 21/01/2021
- NEW : Add priority rank to rules *12/01/2021* - 2.4.0
- NEW : Discount rules search class *13/01/2021* - 2.5.0
- FIX : Table creation *05/03/2021* - 2.5.1
- FIX : use relave url for interface.php instead of conf.php url *23/03/2021* - 2.5.2

## 2.3.0 - 11/01/2021

- NEW : Add Button to update rules from quotations lines (MAIN_FEATURE_LEVEL 2)
  To finish this feature, on button click, need to show all modifications applied before validation
- FIX : Quantity limitation
- FIX : Security access of card and list

## 2.2.0 - 13/10/2020

- NEW : Règle de prix étendue à la modification de lignes.
   Lors de la modification d'une ligne de devis | propal | facture si une réduction existe celle-ci peut être ajoutée avec un clic sur l'icône de réduction.

## 2.1.0 - 06/08/2020
on  Project reduce rule:

- NEW : Ajout d'un champs de selection de projet dans la création d'une règle de prix.
- NEW : AutoLoading de la réduction liée à un projet si existant. 
- NEW : Affichage de la ref Projet et de son titre dans la tooltips du projet associé à la règle si selectionnée.(mouse over sur  le champs réduction ).  

## 2.0
- NEW : Product price rules feature


## 1.0
Initial version
