<?php

/**
 * @file api/v1/stats/StatsHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StatsHandler
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for statistics operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class StatsHandler extends APIHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'stats';
		$roles = array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern() . '/articles',
					'handler' => array($this, 'getSubmissionList'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/articles/{submissionId}',
					'handler' => array($this, 'getSubmission'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/issues',
					'handler' => array($this, 'getSubmissionList'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/issues/{issueId}',
					'handler' => array($this, 'getSubmission'),
					'roles' => $roles
				),
			),
		);
		parent::__construct();
	}

	//
	// Implement methods from PKPHandler
	//
	function authorize($request, &$args, $roleAssignments) {
		$routeName = null;
		$slimRequest = $this->getSlimRequest();

		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		if (!is_null($slimRequest) && ($route = $slimRequest->getAttribute('route'))) {
			$routeName = $route->getName();
		}
		if ($routeName === 'getSubmission') {
			import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
			$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Get total stats and a collection of submissions
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param $args array
	 * @return Response
	 */
	public function getSubmissionList($slimRequest, $response, $args) {
		$request = Application::getRequest();
		$context = $request->getContext();

		if (!$context) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$params = $this->_buildSubmissionListRequestParams($slimRequest);
		// validate parameters
		if (isset($params['dateStart'])) {
			if (!$this->validDate($params['dateStart'])) {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongDateFormat');
			}
		}
		if (isset($params['dateEnd'])) {
			if (!$this->validDate($params['dateEnd'])) {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongDateFormat');
			}
		}
		if (isset($params['dateStart']) && isset($params['dateEnd'])) {
			if (!$this->validDateRange($params['dateStart'], $params['dateEnd'])) {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongDateRange');
			}
		}
		if (isset($params['timeSegment']) && $params['timeSegment'] == 'daily') {
			if (isset($params['dateStart'])) {
				if (!$this->dateWithinLast90Days($params['dateStart'])) {
					return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimeSegmentDaily');
				}
			} else {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimeSegmentDaily');
			}
		}

		$statsService = ServicesContainer::instance()->get('stats');
		$submissionsRecords = $statsService->getOrderedSubmissions($context->getId(), $params);

		$data = $items = array();
		if (!empty($submissionsRecords)) {
			$propertyArgs = array(
				'request' => $request,
				'slimRequest' => $slimRequest,
				'params' => $params
			);
			// get total stats data
			$totalStatsRecords = $statsService->getTotalSubmissionsStats($context->getId(), $params);
			$data = $statsService->getTotalStatsProperties($totalStatsRecords, $propertyArgs);
			// get submisisons stats items
			$slicedSubmissionsRecords = array_slice($submissionsRecords, isset($params['offset'])?$params['offset']:0, $params['count']);
			foreach ($slicedSubmissionsRecords as $submissionsRecord) {
				$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
				$submission = $publishedArticleDao->getById($submissionsRecord['submission_id']);
				$items[] = $statsService->getSummaryProperties($submission, $propertyArgs);
			}
		} else {
			$data = array(
				'abstractViews' => 0,
				'totalGalleyViews' => 0,
				'timeSegments' => array()
			);
		}
		$data['itemsMax'] = count($submissionsRecords);
		$data['items'] = $items;

		return $response->withJson($data, 200);
	}

	/**
	 * Get a single submission usage statistics
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param $args array
	 * @return Response
	 */
	public function getSubmission($slimRequest, $response, $args) {
		$request = Application::getRequest();

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$params = $this->_buildSubmissionRequestParams($slimRequest);
		// validate parameters
		if (isset($params['dateStart'])) {
			if (!$this->validDate($params['dateStart'])) {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongDateFormat');
			}
		}
		if (isset($params['dateEnd'])) {
			if (!$this->validDate($params['dateEnd'])) {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongDateFormat');
			}
		}
		if (isset($params['dateStart']) && isset($params['dateEnd'])) {
			if (!$this->validDateRange($params['dateStart'], $params['dateEnd'])) {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongDateRange');
			}
		}
		if (isset($params['timeSegment']) && $params['timeSegment'] == 'daily') {
			if (isset($params['dateStart'])) {
				if (!$this->dateWithinLast90Days($params['dateStart'])) {
					return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimeSegmentDaily');
				}
			} else {
				return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimeSegmentDaily');
			}
		}

		$data = ServicesContainer::instance()
			->get('stats')
			->getFullProperties($submission, array(
				'request' => $request,
				'slimRequest' 	=> $slimRequest,
				'params' => $params
			));

		return $response->withJson($data, 200);
	}

	/**
	 * Has the given date valid fromat YYYY-MM-DD
	 * @param $dateString string
	 * @return boolean
	 */
	public function validDate($dateString) {
		return preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateString, $matches) === 1;
	}

	/**
	 * Is the given date range valid
	 * @param $dateStart string
	 * @param $dateEnd string
	 * @return boolean
	 */
	public function validDateRange($dateStart, $dateEnd) {
		$dateStartTimestamp = strtotime($dateStart);
		$dateEndTimestamp = strtotime($dateEnd);
		return $dateStartTimestamp <= $dateEndTimestamp;
	}

	/**
	 * Is the given date withing the last 90 days
	 * @param $dateString string
	 * @return boolean
	 */
	public function dateWithinLast90Days($dateString) {
		$dateTimestamp = strtotime($dateString);
		$lastNinetyDaysTimestamp = strtotime('-90 days');
		return $dateTimestamp >= $lastNinetyDaysTimestamp;
	}

	/**
	 * Convert params passed to the api end point. Coerce type and only return
	 * white-listed params.
	 *
	 * @param $slimRequest Request Slim request object
	 * @return array
	 */
	private function _buildSubmissionListRequestParams($slimRequest) {
		// Merge query params over default params
		$defaultParams = array(
			'count' => 30,
			'offset' => 0,
			'timeSegment' => 'monthly'
		);
		$requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

		$returnParams = array();
		// Process query params to format incoming data as needed
		foreach ($requestParams as $param => $val) {
			switch ($param) {
				case 'orderBy':
					$returnParams[$param] = in_array($val, array('total')) ? $val : 'total';
					break;
				case 'orderDirection':
					$returnParams[$param] = $val === 'ASC' ? $val : 'DESC';
					break;
				// Enforce a maximum count to prevent the API from crippling the
				// server
				case 'count':
					$returnParams[$param] = min(100, (int) $val);
					break;
				case 'offset':
					$returnParams[$param] = (int) $val;
					break;
				case 'timeSegment':
				case 'dateStart':
				case 'dateEnd':
				case 'searchPhrase':
					$returnParams[$param] = $val;
					break;
			}
		}

		\HookRegistry::call('API::statistics::submissionLists::params', array(&$returnParams, $slimRequest));

		return $returnParams;
	}

	/**
	 * Convert params passed to the api end point. Coerce type and only return
	 * white-listed params.
	 *
	 * @param $slimRequest Request Slim request object
	 * @return array
	 */
	private function _buildSubmissionRequestParams($slimRequest) {
		// Merge query params over default params
		$defaultParams = array(
				'timeSegment' => 'monthly'
		);
		$requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

		$returnParams = array();
		// Process query params to format incoming data as needed
		foreach ($requestParams as $param => $val) {
			switch ($param) {
				case 'timeSegment':
				case 'dateStart':
				case 'dateEnd':
					$returnParams[$param] = $val;
					break;
			}
		}

		\HookRegistry::call('API::statistics::submission::params', array(&$returnParams, $slimRequest));

		return $returnParams;
	}

}
