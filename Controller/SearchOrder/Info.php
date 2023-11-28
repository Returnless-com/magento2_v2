<?php
declare(strict_types=1);

namespace Returnless\ConnectorV2\Controller\SearchOrder;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Returnless\ConnectorV2\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Authentication;
use Magento\Backend\Model\Auth;
use Magento\User\Model\User;
use Magento\Authorization\Model\Acl\AclRetriever;
use Returnless\ConnectorV2\Model\Api\SearchOrder;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Exception\ValidatorException;

/**
 * Class Info
 * @package Returnless\ConnectorV2
 */
class Info extends AbstractController implements CsrfAwareActionInterface
{
    /**
     * @var OrderInfo
     */
    protected $orderInfo;

    /**
     * @var string[]
     */
    protected $allowedResources = ['Magento_Sales::sales'];

    /**
     * private MAX_ORDER_LENGTH
     */
    private const MAX_ORDER_LENGTH = 32;

    /**
     * @var SearchOrder
     */
    protected $searchOrder;

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Authentication $authentication
     * @param Auth $auth
     * @param User $user
     * @param AclRetriever $aclRetriever
     * @param SearchOrder $searchOrder
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
        SearchOrder $searchOrder,
        Context $context
    ) {
        $this->searchOrder = $searchOrder;

        return parent::__construct(
            $config,
            $logger,
            $resultJsonFactory,
            $authentication,
            $auth,
            $user,
            $aclRetriever,
            $context
        );
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try{
            $this->isEnabled();
            $this->validAuth();
            $this->isAllowedResources($this->allowedResources);

            $incrementId = $this->getIncrement();

            $response = $this->searchOrder
                ->getOrderInfoReturnless($incrementId);

            $this->setResponse("Success!", 200, false, $response);
        } catch (AuthenticationException $authenticationException) {
            $this->setResponse("Error!", 401, false, ['result' => $authenticationException->getMessage()]);
        } catch (AuthorizationException $authorizationException) {
            $this->setResponse("Error!", 401, false, ['result' => $authorizationException->getMessage()]);
        }  catch (\Exception $exception) {
            $this->setResponse("Error!", 401, false, ['result' => $exception->getMessage()]);
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($this->response);
    }

    /**
     * @return string
     * @throws ValidatorException
     */
    private function getIncrement()
    {
        $post = json_decode($this->getRequest()->getContent());

        if (empty($post) || !property_exists($post, 'order_number') || !is_string($post->order_number)) {
            throw new ValidatorException(__('Order Number is not valid.'));
        }

        if (strlen($post->order_number) > self::MAX_ORDER_LENGTH) {
            throw new ValidatorException(__('Order Number Length should be less then %1.', self::MAX_ORDER_LENGTH));
        }

        if (preg_match("/^[a-zA-Z0-9_-]{3," . self::MAX_ORDER_LENGTH . "}$/", $post->order_number) != 1) {
            throw new ValidatorException(__('Order Number format is not correct.'));
        }

        return $post->order_number;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
