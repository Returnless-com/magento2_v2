<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Controller;

use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class ApiRouter
 * @package Returnless\ConnectorV2
 */
class ApiRouter implements RouterInterface
{
    /**
     * const ORDER_V2_ORDER_SEARCH
     */
    const ORDER_V2_ORDER_SEARCH = "api/order-search";

    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Router constructor.
     *
     * @param ActionFactory $actionFactory
     * @param ResponseInterface $response
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ActionFactory $actionFactory,
        ResponseInterface $response,
        ObjectManagerInterface $objectManager
    ) {
        $this->actionFactory = $actionFactory;
        $this->response = $response;
        $this->objectManager = $objectManager;
    }

    /**
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        $identifier = trim($request->getPathInfo(), '/');

        if (strpos($identifier, self::ORDER_V2_ORDER_SEARCH) !== false
            || (function_exists('str_contains') && str_contains($identifier, self::ORDER_V2_ORDER_SEARCH))
        ) {
            if (class_exists(\Zend\Http\Headers::class)) {
                $headers = $this->objectManager->create(\Zend\Http\Headers::class)->addHeaders(['X-Requested-With' => 'XMLHttpRequest']);
                $request->setHeaders($headers);
            } else if (class_exists(\Laminas\Http\Headers::class)) {
                $headers = $this->objectManager->create(\Laminas\Http\Headers::class)->addHeaders(['X-Requested-With' => 'XMLHttpRequest']);
                $request->setHeaders($headers);
            }

            $request->setModuleName('returnless_connector_v2');
            $request->setControllerName('searchorder');
            $request->setActionName('info');

            return $this->actionFactory->create(Forward::class, ['request' => $request]);
        }

        return null;
    }
}
