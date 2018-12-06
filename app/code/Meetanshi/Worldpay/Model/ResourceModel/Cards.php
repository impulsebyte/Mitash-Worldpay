<?php

namespace Meetanshi\Worldpay\Model\ResourceModel;
 
class Cards extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('meetanshi_worldpay_saved_cards', 'id');
    }
}
