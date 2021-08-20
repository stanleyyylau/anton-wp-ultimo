<?php

namespace WU_Stripe\Reporting;

/**
 * Class ReportType
 *
 * @property string $id
 * @property string $object
 * @property int $data_available_end
 * @property int $data_available_start
 * @property string $name
 * @property int $updated
 * @property string $version
 *
 * @package WU_Stripe\Reporting
 */
class ReportType extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "reporting.report_type";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Retrieve;
}
