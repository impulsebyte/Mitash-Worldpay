<?php

namespace Meetanshi\Worldpay\Controller\Saved;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Url;
use Meetanshi\Worldpay\Model\ResourceModel\Cards\CollectionFactory;

abstract class Saved extends Action
{
    protected $customerSession;
    protected $resultPageFactory;
    protected $customerUrl;
    protected $savedCardFactory;

    public function __construct(Context $context, Session $customerSession, PageFactory $resultPageFactory, Url $customerUrl, CollectionFactory $savedCardFactory)
    {
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerUrl = $customerUrl;
        $this->savedCardFactory = $savedCardFactory;
        parent::__construct($context);
    }
}
