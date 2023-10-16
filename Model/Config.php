<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Configs
 * @package Returnless\ConnectorV2
 */
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * const CONFIG_PATH_API_ENABLED
     */
    const CONFIG_PATH_API_ENABLED = 'returnless_connector_v2/general/enabled';

    /**
     * const CONFIG_EAN_ATTRIBUTE_CODE
     */
    const CONFIG_EAN_ATTRIBUTE_CODE = 'returnless_connector_v2/general/u_upc';

    /**
     * const CONFIG_BRAND_ATTRIBUTE_CODE
     */
    const CONFIG_BRAND_ATTRIBUTE_CODE = 'returnless_connector_v2/general/u_brand';

    /**
     * const Path to config enabled marketplace search
     */
    const CONFIG_MARKETPLACE_SEARCH_ENABLED = 'returnless_connector_v2/marketplace_orders/enabled';

    /**
     * const Path to config marketplace integration partner
     */
    const CONFIG_MARKETPLACE_SEARCH_VENDOR_ID = 'returnless_connector_v2/marketplace_orders/integration_partner';


    /**
     * const Order search priority
     */
    const CONFIG_MARKETPLACE_SEARCH_PRIORITY = 'returnless_connector_v2/marketplace_orders/search_priority';

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getEnabled($store = null): string
    {
        $enabled = (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_API_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $enabled;
    }

    /**
     * @param $store
     * @return string
     */
    public function getMarketplaceSearchEnabled($store = null): string
    {
        $enabled = (string)$this->scopeConfig->getValue(
            self::CONFIG_MARKETPLACE_SEARCH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $enabled;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getMarketplaceSearchPartnerId($store = null): string
    {
        $enabled = (string)$this->scopeConfig->getValue(
            self::CONFIG_MARKETPLACE_SEARCH_VENDOR_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $enabled;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getEanAttributeCode($store = null): string
    {
        $eanAttributeCode = (string)$this->scopeConfig->getValue(
            self::CONFIG_EAN_ATTRIBUTE_CODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $eanAttributeCode;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getBrandAttributeCode($store = null): string
    {
        $brandttributeCode = (string)$this->scopeConfig->getValue(
            self::CONFIG_BRAND_ATTRIBUTE_CODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $brandttributeCode;
    }

    /**
     * @param $store
     * @return string
     */
    public function getSearchPriority($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_MARKETPLACE_SEARCH_PRIORITY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
