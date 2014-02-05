<?php

/**
 * @defgroup plugins_generic_vgWort
 */
 
/**
 * @file plugins/generic/vgWort/index.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_vgWort
 * @brief Wrapper for VG Wort plugin.
 *
 */
require_once('VGWortPlugin.inc.php');

return new VGWortPlugin();

?>
