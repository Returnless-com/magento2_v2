<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class IntegrationPartner
 * @package Returnless\ConnectorV2
 */
class IntegrationPartner implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return
            [
                [
                    'value' => 'vendiro',
                    'label' => __('Vendiro')
                ]
            ];
    }
}
