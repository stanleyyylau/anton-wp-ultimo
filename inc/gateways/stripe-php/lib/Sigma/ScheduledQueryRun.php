<?php

namespace WU_Stripe\Sigma;

/**
 * Class Authorization
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property int $data_load_time
 * @property string $error
 * @property \WU_Stripe\File $file
 * @property bool $livemode
 * @property int $result_available_until
 * @property string $sql
 * @property string $status
 * @property string $title
 *
 * @package WU_Stripe\Sigma
 */
class ScheduledQueryRun extends \WU_Stripe\ApiResource
{
    const OBJECT_NAME = "scheduled_query_run";

    use \WU_Stripe\ApiOperations\All;
    use \WU_Stripe\ApiOperations\Retrieve;

    public static function classUrl()
    {
        return "/v1/sigma/scheduled_query_runs";
    }
}
