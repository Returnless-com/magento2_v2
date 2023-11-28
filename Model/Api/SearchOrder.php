<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Model\Api;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Module\ResourceInterface;
use Magento\Sales\Model\OrderRepository;
use Returnless\ConnectorV2\Api\SearchOrderInterface;
use Magento\Catalog\Model\ProductRepository;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Helper\Image;
use Returnless\ConnectorV2\Model\Config;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ObjectManager;
use Magento\Weee\Helper\Data;
use Returnless\ConnectorV2\Helper\Data as RetHelper;

/**
 * Class SearchOrder
 * @package Returnless\ConnectorV2
 */
class SearchOrder implements SearchOrderInterface
{
    /**
     * @var ResourceInterface
     */
    protected $moduleResource;

    /**
     * const NAMESPACE_MODULE
     */
    const NAMESPACE_MODULE = 'Returnless_ConnectorV2';

    /**
     * @var bool
     */
    protected $returnFlag = false;

    /**
     * @var LoggerInterface
     *
     */
    protected $logger;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Image
     */
    protected $image;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var Data
     */
    protected $weeeHelper;

    /**
     * @var RetHelper
     */
    protected $retHelper;

    /**
     * @param ProductRepository $productRepository
     * @param LoggerInterface $logger
     * @param Image $image
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Data $weeeHelper
     * @param RetHelper $retHelper
     */
    public function __construct(
        ProductRepository $productRepository,
        LoggerInterface $logger,
        Image $image,
        Config $config,
        ResourceInterface $moduleResource,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Data $weeeHelper,
        RetHelper $retHelper
    ){
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->image = $image;
        $this->config = $config;
        $this->moduleResource = $moduleResource;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->weeeHelper = $weeeHelper;
        $this->retHelper = $retHelper;
    }

    /**
     * @inheritdoc
     */
    public function getOrderInfoReturnless($incrementId)
    {
        $response['installed_module_version'] = $this->moduleResource->getDbVersion(self::NAMESPACE_MODULE);
        $orderInfo = [];

        $this->logger->debug('[RET_ORDER_INFO] Increment Id', [$incrementId]);

        try {
            if ($this->config->getMarketplaceSearchEnabled() && $this->config->getSearchPriority() === 'marketplace') {
                /** @var  $partnersSourceAdapter \Returnless\ConnectorV2\Model\PartnersSourceAdapter */
                $partnersSourceAdapter = ObjectManager::getInstance()->get('Returnless\ConnectorV2\Model\PartnersSourceAdapter');
                $order = $partnersSourceAdapter->getOrderByMarketplace($incrementId);
                if (!$order->getId()) {
                    $order = $this->retHelper->searchOrder($incrementId);
                }
            } else {
                $order = $this->retHelper->searchOrder($incrementId);
                if (!$order->getId() && $this->config->getMarketplaceSearchEnabled()) {
                    /** @var  $partnersSourceAdapter \Returnless\ConnectorV2\Model\PartnersSourceAdapter */
                    $partnersSourceAdapter = ObjectManager::getInstance()->get('Returnless\ConnectorV2\Model\PartnersSourceAdapter');
                    $order = $partnersSourceAdapter->getOrderByMarketplace($incrementId);
                }
            }

            $payment = $order->getPayment();
            if(!empty($payment)) {
                $method = $payment->getMethodInstance();
                $orderInfo['payment_method'] = $method->getTitle();
            }

            $shippingMethod = $order->getShippingMethod();
            if(!empty($shippingMethod)) {
                $orderInfo['shipping_method'] = $shippingMethod;
            }

            $orderInfo['order_number'] = $order->getIncrementId();
            $orderInfo['order_id'] = $order->getEntityId();
            $orderInfo['order_date'] = $order->getCreatedAt();
            $orderInfo['status'] = $order->getState();
            $orderInfo['currency'] = $order->getOrderCurrencyCode();
            $orderInfo['shipping_amount'] = (int) $order->getShippingAmount() * 100;
            $orderInfo['carrier'] = $order->getShippingDescription();
            $tracksCollection = $order->getTracksCollection();
            if ($tracksCollection->getSize() > 0) {
                $orderInfo['tracking_number'] = $tracksCollection->fetchItem()->getTrackNumber();
            }
            $orderInfo['discount_code'] = $order->getCouponCode();
            $orderInfo['discount_description'] = $order->getCouponDescription();
            $orderInfo['customer'] = $this->getCustomer($order);
            $orderItems = $order->getAllVisibleItems();

            $this->logger->debug('[RET_ORDER_INFO] Order Id', [$order->getEntityId()]);
            $this->logger->debug('[RET_ORDER_INFO] Customer Email', [$order->getCustomerEmail()]);
            $this->logger->debug('[RET_ORDER_INFO] Order has items', [count($orderItems)]);

            foreach ($orderItems as $orderItemKey => $orderItem) {
                $orderInfo['sales_order_items'][$orderItemKey] = $this->getSalesOrderItem($orderItem);
            }

            $response = $orderInfo;
        } catch (\Exception $e) {
            $this->logger->debug("[RET_ORDER_INFO] " . $e->getMessage());
            throw new \Exception( $e->getMessage());
        }

        if ($this->returnFlag) {
            return $response;
        }

        $this->returnResult($response);
    }

    /**
     * @param $order
     * @return array
     */
    protected function getCustomer($order): array
    {
        $customer = [];

        $customer['customer_id'] = $order->getCustomerId();
        $customer['email'] = $order->getCustomerEmail();
        $customer['name'] = $order->getCustomerName();

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $customer['address']['country'] = $shippingAddress->getCountryId() ?: $billingAddress->getCountryId();
            $customer['address']['telephone'] = $shippingAddress->getTelephone() ?: $billingAddress->getTelephone();
            $street = $shippingAddress->getStreet();
            $street1 = $billingAddress->getStreet();
            $customer['address']['street'] = $street[0] ?? $street1[0] ?? null;
            $customer['address']['house_number'] = $street[1] ?? $street1[1] ?? null;
            $customer['address']['suffix'] = $street[2] ?? $street1[2] ?? null;
            $customer['address']['city'] = $shippingAddress->getCity() ?: $billingAddress->getCity();
            $customer['address']['postal_code'] = $shippingAddress->getPostcode() ?: $billingAddress->getPostcode();
            $customer['address']['state'] = $shippingAddress->getRegion() ?: $billingAddress->getRegion();
        }

        return $customer;
    }

    /**
     * @param $orderItem
     * @return array
     */
    protected function getSalesOrderItem ($orderItem)
    {
        $salesOrderItem = [];

        $salesOrderItem['sales_order_item_id'] = $orderItem->getItemId();
        $salesOrderItem['product_id'] = $orderItem->getProductId();
        $salesOrderItem['name'] = $orderItem->getName();
        $salesOrderItem['sku'] = $orderItem->getSku();
        $salesOrderItem['quantity'] = (int) $orderItem->getQtyOrdered();
        $salesOrderItem['vat_rate'] = (int) $orderItem->getTaxPercent();
        $salesOrderItem['item_price'] = $orderItem->getPrice() * 100;
        $salesOrderItem['item_discount'] = (int) ($orderItem->getDiscountAmount() * 100);

        $product = $this->getProductById($orderItem->getProductId());
        if ($product) {
            $eavAttributeCode = $this->config->getEanAttributeCode();
            $salesOrderItem['barcode'] = $eavAttributeCode ? $product->getData($eavAttributeCode) : null;
            $salesOrderItem['model'] = $product->getColor() ?: $product->getSize();
            $salesOrderItem['item_cost'] = $product->getCost();
            $salesOrderItem['width'] = $product->getWeight();
            $salesOrderItem['image_src'] = $this->getImageByProduct($product);
            $salesOrderItem['url'] = $product->getProductUrl();
            $salesOrderItem['brand'] = $this->getUBrand($product);
            $salesOrderItem['category'] = $product->getCategoryIds();
        }

        if ($orderItem->getChildrenItems()) {
            $salesOrderItem['has_children'] = true;
        }

        $salesOrderItem['parent_id'] = $orderItem->getParentItemId();

        return $salesOrderItem;
    }

    /**
     * This method provides an ability to return Response Data
     *
     * @return $this
     */
    public function setReturnFlag()
    {
        $this->returnFlag = true;
        return $this;
    }

    /**
     * @param $id
     * @return false|\Magento\Catalog\Api\Data\ProductInterface|mixed|null
     */
    protected function getProductById($id)
    {
        try {
            $product = $this->productRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            $product = false;
        }

        return $product;
    }

    /**
     * @param $product
     * @return bool|string
     */
    public function getImageByProduct($product): string
    {
        $image = $this->image
            ->init($product, 'product_page_image_large')
            ->setImageFile($product->getImage())
            ->getUrl();

        return $image;
    }

    /**
     * @param $product
     * @return null
     */
    protected function getUBrand($product)
    {
        $brandAttributeCode = $this->config->getBrandAttributeCode();
        $brandAttributeCode = !empty($brandAttributeCode) ? $brandAttributeCode : null;

        $uBrand = null;
        if (!empty($brandAttributeCode) && $product->getResource()->getAttribute($brandAttributeCode)) {
            $uBrand = $product->getResource()->getAttribute($brandAttributeCode)->getFrontend()->getValue($product);
        }

        return $uBrand ? $uBrand : null;
    }

    /**
     * @param $result
     * @return void
     */
    protected function returnResult($result): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $result = json_encode($result);
        print_r($result,false);

        die();
    }
}
