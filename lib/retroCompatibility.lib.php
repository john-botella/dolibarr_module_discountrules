<?php

/*
 * INCLUDE TEMPLATES FUNCTIONS CURRENTLY IN DOLIBARR 10.0
 */


if (floatval(DOL_VERSION) < 10):


    /**
     * Function dolGetBadge
     *
     * @param string $label label of badge no html : use in alt attribute for accessibility
     * @param string $html optional : label of badge with html
     * @param string $type type of badge : Primary Secondary Success Danger Warning Info Light Dark status0 status1 status2 status3 status4 status5 status6 status7 status8 status9
     * @param string $mode default '' , pill, dot
     * @param string $url the url for link
     * @param array $params various params for future : recommended rather than adding more fuction arguments
     * @return  string              Html badge
     */
    if (!function_exists('dolGetBadge')) {
        function dolGetBadge($label, $html = '', $type = 'primary', $mode = '', $url = '', $params = array())
        {

            $attr = array(
                'class' => 'badge' . (!empty($mode) ? ' badge-' . $mode : '') . (!empty($type) ? ' badge-' . $type : '')
            );

            if (empty($html)) {
                $html = $label;
            }

            if (!empty($url)) {
                $attr['href'] = $url;
            }

            if ($mode === 'dot') {
                $attr['class'] .= ' classfortooltip';
                $attr['title'] = $html;
                $attr['aria-label'] = $label;
                $html = '';
            }

            // Override attr
            if (!empty($params['attr']) && is_array($params['attr'])) {
                foreach ($params['attr'] as $key => $value) {
                    $attr[$key] = $value;
                }
            }

// TODO: add hook

            // escape all attribute
            $attr = array_map('dol_escape_htmltag', $attr);

            $TCompiledAttr = array();
            foreach ($attr as $key => $value) {
                $TCompiledAttr[] = $key . '="' . $value . '"';
            }

            $compiledAttributes = !empty($TCompiledAttr) ? implode(' ', $TCompiledAttr) : '';

            $tag = !empty($url) ? 'a' : 'span';

            return '<' . $tag . ' ' . $compiledAttributes . '>' . $html . '</' . $tag . '>';
        }
    }


    /**
     * Function dolGetStatus
     *
     * @param string $statusLabel Label of badge no html : use in alt attribute for accessibility
     * @param string $statusLabelShort Short label of badge no html
     * @param string $html Optional : label of badge with html
     * @param string $statusType status0 status1 status2 status3 status4 status5 status6 status7 status8 status9 : image name or badge name
     * @param int $displayMode 0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
     * @param string $url The url for link
     * @param array $params Various params for future : recommended rather than adding more function arguments
     * @return  string                     Html status string
     */

    if (!function_exists('dolGetStatus')) {
        function dolGetStatus($statusLabel = '', $statusLabelShort = '', $html = '', $statusType = 'status0', $displayMode = 0, $url = '', $params = array())
        {
            global $conf;

            $return = '';

            // image's filename are still in French
            $statusImg = array(
                'status0' => 'statut0'
            , 'status1' => 'statut1'
            , 'status2' => 'statut2'
            , 'status3' => 'statut3'
            , 'status4' => 'statut4'
            , 'status5' => 'statut5'
            , 'status6' => 'statut6'
            , 'status7' => 'statut7'
            , 'status8' => 'statut8'
            , 'status9' => 'statut9'
            );

            // TODO : add a hook

            if ($displayMode == 0) {
                $return = !empty($html) ? $html : $statusLabel;
            } elseif ($displayMode == 1) {
                $return = !empty($html) ? $html : (!empty($statusLabelShort) ? $statusLabelShort : $statusLabel);
            } // use status with images
            elseif (empty($conf->global->MAIN_STATUS_USES_CSS)) {
                $return = '';
                $htmlLabel = (in_array($displayMode, array(1, 2, 5)) ? '<span class="hideonsmartphone">' : '') . (!empty($html) ? $html : $statusLabel) . (in_array($displayMode, array(1, 2, 5)) ? '</span>' : '');
                $htmlLabelShort = (in_array($displayMode, array(1, 2, 5)) ? '<span class="hideonsmartphone">' : '') . (!empty($html) ? $html : (!empty($statusLabelShort) ? $statusLabelShort : $statusLabel)) . (in_array($displayMode, array(1, 2, 5)) ? '</span>' : '');

                if (!empty($statusImg[$statusType])) {
                    $htmlImg = img_picto($statusLabel, $statusImg[$statusType]);
                } else {
                    $htmlImg = img_picto($statusLabel, $statusType);
                }

                if ($displayMode === 2) {
                    $return = $htmlImg . ' ' . $htmlLabelShort;
                } elseif ($displayMode === 3) {
                    $return = $htmlImg;
                } elseif ($displayMode === 4) {
                    $return = $htmlImg . ' ' . $htmlLabel;
                } elseif ($displayMode === 5) {
                    $return = $htmlLabelShort . ' ' . $htmlImg;
                } else { // $displayMode >= 6
                    $return = $htmlLabel . ' ' . $htmlImg;
                }
            } // Use new badge
            elseif (!empty($conf->global->MAIN_STATUS_USES_CSS) && !empty($displayMode)) {
                $statusLabelShort = !empty($statusLabelShort) ? $statusLabelShort : $statusLabel;

                if ($displayMode == 3) {
                    $return = dolGetBadge($statusLabel, '', $statusType, 'dot');
                } elseif ($displayMode === 5) {
                    $return = dolGetBadge($statusLabelShort, $html, $statusType);
                } else {
                    $return = dolGetBadge($statusLabel, $html, $statusType);
                }
            }

            return $return;
        }
    }

    /**
     * Function dolGetButtonAction
     *
     * @param string $label label of button no html : use in alt attribute for accessibility $html is not empty
     * @param string $html optional : content with html
     * @param string $actionType default, delete, danger
     * @param string $url the url for link
     * @param string $id attribute id of button
     * @param int $userRight user action right
     * @param array $params various params for future : recommended rather than adding more function arguments
     * @return string               html button
     */
    if (!function_exists('dolGetButtonAction')) {
        function dolGetButtonAction($label, $html = '', $actionType = 'default', $url = '', $id = '', $userRight = 1, $params = array())
        {
            $class = 'butAction';
            if ($actionType == 'danger' || $actionType == 'delete') {
                $class = 'butActionDelete';
            }

            $attr = array(
                'class' => $class
            , 'href' => empty($url) ? '' : $url
            );

            if (empty($html)) {
                $html = $label;
            } else {
                $attr['aria-label'] = $label;
            }


            if (empty($userRight)) {
                $attr['class'] = 'butActionRefused';
                $attr['href'] = '';
            }

            if (empty($id)) {
                $attr['id'] = $id;
            }

// Override attr
            if (!empty($params['attr']) && is_array($params['attr'])) {
                foreach ($params['attr'] as $key => $value) {
                    $attr[$key] = $value;
                }
            }

            if (isset($attr['href']) && empty($attr['href'])) {
                unset($attr['href']);
            }

// TODO : add a hook

// escape all attribute
            $attr = array_map('dol_escape_htmltag', $attr);

            $TCompiledAttr = array();
            foreach ($attr as $key => $value) {
                $TCompiledAttr[] = $key . '="' . $value . '"';
            }

            $compiledAttributes = !empty($TCompiledAttr) ? implode(' ', $TCompiledAttr) : '';

            $tag = !empty($attr['href']) ? 'a' : 'span';

            return '<div class="inline-block divButAction"><' . $tag . ' ' . $compiledAttributes . '>' . $html . '</' . $tag . '></div>';
        }
    }


    /**
     * Function dolGetButtonTitle : this kind of buttons are used in title in list
     *
     * @param string $label label of button
     * @param string $helpText optional : content for help tooltip
     * @param string $iconClass class for icon element
     * @param string $url the url for link
     * @param string $id attribute id of button
     * @param int $status 0 no user rights, 1 active, -1 Feature Disabled, -2 disable Other reason use helpText as tooltip
     * @param array $params various params for future : recommended rather than adding more function arguments
     * @return string               html button
     */
    if (!function_exists('dolGetButtonTitle')) {
        function dolGetButtonTitle($label, $helpText = '', $iconClass = 'fa fa-file', $url = '', $id = '', $status = 1, $params = array())
        {
            global $langs, $conf, $user;

// Actually this conf is used in css too for external module compatibility and smooth transition to this function
            if (!empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED) && (!$user->admin) && $status <= 0) {
                return '';
            }

            $class = 'btnTitle';

// hidden conf keep during button transition TODO: remove this block
            if (empty($conf->global->MAIN_USE_NEW_TITLE_BUTTON)) {
                $class = 'butActionNew';
            }

            $attr = array(
                'class' => $class
            , 'href' => empty($url) ? '' : $url
            );

            if (!empty($helpText)) {
                $attr['title'] = dol_escape_htmltag($helpText);
            }

            if ($status <= 0) {
                $attr['class'] .= ' refused';

// hidden conf keep during button transition TODO: remove this block
                if (empty($conf->global->MAIN_USE_NEW_TITLE_BUTTON)) {
                    $attr['class'] = 'butActionNewRefused';
                }

                $attr['href'] = '';

                if ($status == -1) { // Not enough permissions
                    $attr['title'] = dol_escape_htmltag($langs->transnoentitiesnoconv("FeatureDisabled"));
                } elseif ($status == 0) { // disable
                    $attr['title'] = dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions"));
                }
            }

            if (!empty($attr['title'])) {
                $attr['class'] .= ' classfortooltip';
            }

            if (empty($id)) {
                $attr['id'] = $id;
            }

// Override attr
            if (!empty($params['attr']) && is_array($params['attr'])) {
                foreach ($params['attr'] as $key => $value) {
                    if ($key == 'class') {
                        $attr['class'] .= ' ' . $value;
                    } elseif ($key == 'classOverride') {
                        $attr['class'] = $value;
                    } else {
                        $attr[$key] = $value;
                    }
                }
            }

            if (isset($attr['href']) && empty($attr['href'])) {
                unset($attr['href']);
            }

// TODO : add a hook

// escape all attribute
            $attr = array_map('dol_escape_htmltag', $attr);

            $TCompiledAttr = array();
            foreach ($attr as $key => $value) {
                $TCompiledAttr[] = $key . '="' . $value . '"';
            }

            $compiledAttributes = !empty($TCompiledAttr) ? implode(' ', $TCompiledAttr) : '';

            $tag = !empty($attr['href']) ? 'a' : 'span';


            $button = '<' . $tag . ' ' . $compiledAttributes . ' >';
            $button .= '<span class="' . $iconClass . ' valignmiddle btnTitle-icon"></span>';
            $button .= '<span class="valignmiddle text-plus-circle btnTitle-label">' . $label . '</span>';
            $button .= '</' . $tag . '>';

// hidden conf keep during button transition TODO: remove this block
            if (empty($conf->global->MAIN_USE_NEW_TITLE_BUTTON)) {
                $button = '<' . $tag . ' ' . $compiledAttributes . ' ><span class="text-plus-circle">' . $label . '</span>';
                $button .= '<span class="' . $iconClass . ' valignmiddle"></span>';
                $button .= '</' . $tag . '>';
            }

            return $button;
        }
    }

endif;
