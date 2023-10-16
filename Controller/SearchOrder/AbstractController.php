<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Controller\SearchOrder;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Returnless\ConnectorV2\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Authentication;
use Magento\Backend\Model\Auth;
use Magento\User\Model\User;
use Magento\Authorization\Model\Acl\AclRetriever;

/**
 * Class AbstractController
 * @package Returnless\ConnectorV2
 */
abstract class AbstractController extends Action
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var array
     */
    protected $response = [];

    /**
     * @var LoggerInterface
     *
     */
    protected $logger;

    /**
     * @var Authentication
     */
    protected $httpAuthentication;

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var AclRetriever
     */
    protected $aclRetriever;

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Authentication $authentication
     * @param Auth $auth
     * @param User $user
     * @param AclRetriever $aclRetriever
     * @param Context $context
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        JsonFactory $resultJsonFactory,
        Authentication $authentication,
        Auth $auth,
        User $user,
        AclRetriever $aclRetriever,
        Context $context
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->httpAuthentication = $authentication;
        $this->auth = $auth;
        $this->user = $user;
        $this->aclRetriever = $aclRetriever;

        return parent::__construct($context);
    }

    /**
     * @return bool
     * @throws AuthenticationException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validAuth()
    {
        list($login, $password) = $this->httpAuthentication->getCredentials();

        if (!$this->user->authenticate($login, $password)) {
            throw new AuthenticationException(
                __(
                'The account sign-in was incorrect or your account is disabled temporarily. '
                . 'Please wait and try again later.'
                )
            );
        }

        $role = $this->user->getRole();
        $this->resources = $this->aclRetriever->getAllowedResourcesByRole($role->getId());

        return true;
    }

    /**
     * @param string $message
     * @param int $code
     * @param bool $debug
     * @param null $result
     * @return $this
     */
    protected function setResponse($message = '', $code = 0, $debug = false, $result = null)
    {
        if ($debug) {
            $this->logger->notice("[RETURNLESS_CONNECTORV2_DEBUG] " . $message);
        }

        header("Content-Type: application/json; charset=utf-8");

        if ($code == 200) {
            $this->response = $result;
            return $this;
        }

        $this->response['return_code'] = $code;
        $this->response['return_message'] = $message;

        if (isset($result['installed_module_version']) && !empty($result['installed_module_version'])) {
            $this->response['installed_module_version'] = $result['installed_module_version'];
        }

        if (isset($result['result']) && !empty($result['result'])) {
            $this->response['result'] = $result['result'];
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function getResources()
    {
        return $this->resources;
    }

    /**
     * @param array $allowedResources
     * @return bool
     * @throws AuthorizationException
     */
    protected function isAllowedResources(array $allowedResources): bool
    {
        $flag = array_intersect($this->resources, $allowedResources);

        if (empty($flag)) {
            throw new AuthorizationException( __(
                'Resource is not allowed.'
                . 'Please wait and try again later.'
            ));
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isEnabled()
    {
        if (!$this->config->getEnabled()) {
            throw new LocalizedException( __(
                'Extension is disabled.'
            ));
        }
    }
}
