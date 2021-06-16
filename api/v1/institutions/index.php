<?php
/**
 * @defgroup api_v1_institutions Institution API requests
 */

/**
 * @file api/v1/institutions/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_institutions
 * @brief Handle API requests for institutions.
 */
import('lib.pkp.api.v1.institutions.PKPInstitutionHandler');
return new PKPInstitutionHandler();
