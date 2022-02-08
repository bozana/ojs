<?php

/**
* @file classes/statistics/IR_A1.inc.php
*
* Copyright (c) 2013-2021 Simon Fraser University
* Copyright (c) 2003-2021 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class IR_A1
* @ingroup statistics
*
* @brief COUNTER R5 SUSHI Journal Article Requests (IR_A1).
*
*/

namespace APP\sushi;

class IR_A1 extends IR
{
    /**
     * Get report name defined by COUNTER.
     */
    public function getName(): string
    {
        return 'Journal Article Requests';
    }

    /**
     * Get report ID defined by COUNTER.
     */
    public function getID(): string
    {
        return 'IR_A1';
    }

    /**
     * Get report description.
     */
    public function getDescription(): string
    {
        return __('sushi.reports.ir_a1.description');
    }

    /**
     * Get API path defined by COUNTER for this report.
     */
    public function getAPIPath(): string
    {
        return 'reports/ir_a1';
    }

    /**
     * Get request parameters supported by this report.
     */
    public function getSupportedParams(): array
    {
        return ['customer_id', 'begin_date', 'end_date', 'platform'];
    }

    /**
     * Get filters supported by this report.
     */
    public function getSupportedFilters(): array
    {
        return [];
    }

    /**
     * Get attributes supported by this report.
     */
    public function getSupportedAttributes(): array
    {
        return [];
    }

    /**
     * Set filters based on the requested parameters.
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        foreach ($filters as $filter) {
            switch ($filter['Name']) {
                case 'Begin_Date':
                    $this->beginDate = $filter['Value'];
                    break;
                case 'End_Date':
                    $this->endDate = $filter['Value'];
                    break;
            }
        }
        // The filters predefined for this report
        $predefinedFilters = [
            ['Name' => 'Metric_Type', 'Value' => 'Total_Item_Requests|Unique_Item_Requests'],
            ['Name' => 'Access_Method', 'Value' => 'Regular'],
            ['Name' => 'Data_Type', 'Value' => 'Article'],
            ['Name' => 'Parent_Data_Type', 'Value' => 'Journal']
        ];
        $this->filters = array_merge($filters, $predefinedFilters);
    }

    /**
     * Set attributes based on the requested parameters.
     * No attributes are supported by this report.
     */
    public function setAttributes(array $attributes)
    {
        $predefinedAttributes = [
            ['Name' => 'Attributes_To_Show', 'Value' => 'Article_Version|Authors|Access_Type|Publication_Date'],
            ['Name' => 'Include_Parent_Details', 'Value' => 'True'],
        ];
        parent::setAttributes($predefinedAttributes);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\sushi\IR_A1', '\IR_A1');
}
