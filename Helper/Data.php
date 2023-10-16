<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Helper;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\OrderRepository;

/**
 * Class Data
 * @package Returnless\ConnectorV2
 */
class Data extends AbstractHelper
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Data constructor.
     *
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Context $context
     */
    public function __construct(
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Context $context
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        parent::__construct($context);
    }

    /**
     * @param $incrementId
     * @param string $searchKey
     * @return \Magento\Framework\DataObject
     */
    public function searchOrder($incrementId, $searchKey = 'increment_id')
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                $searchKey,
                $incrementId,
                'eq'
            )
            ->create();

        return $this->orderRepository->getList($searchCriteria)->getFirstItem();
    }

    /**
     * @param $order
     * @param $sku
     * @return false|mixed
     */
    public function getItemBySku($order, $sku)
    {
        $items = $order->getAllVisibleItems();

        foreach($items as $item){
            if ($sku == $item->getSku()) {
                return $item;
            }
        }

        return false;
    }
}
