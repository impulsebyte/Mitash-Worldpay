<?php

namespace Meetanshi\Worldpay\Model\ResourceModel\Cards;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Meetanshi\Worldpay\Model\Cards', 'Meetanshi\Worldpay\Model\ResourceModel\Cards');
    }
}
