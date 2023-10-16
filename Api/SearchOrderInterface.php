<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Api;

/**
 * Class SearchOrderInterface
 * @package Returnless\ConnectorV2
 */
interface SearchOrderInterface
{
    /**
     * Returns Order Info By the Increment Id
     *
     * @api
     * @param string $incrementId
     * @return mixed
     */
    public function getOrderInfoReturnless($incrementId);
}
