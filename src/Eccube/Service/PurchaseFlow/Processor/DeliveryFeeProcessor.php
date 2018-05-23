<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Service\PurchaseFlow\ItemHolderProcessor;
use Eccube\Service\PurchaseFlow\ProcessResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * 送料明細追加.
 */
class DeliveryFeeProcessor implements ItemHolderProcessor
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * DeliveryFeeProcessor constructor.
     *
     * @param $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext     $context
     *
     * @return ProcessResult
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if ($this->containsDeliveryFeeItem($itemHolder) == false) {
            $this->addDeliveryFeeItem($itemHolder);
        }

        return ProcessResult::success();
    }

    /**
     * @param ItemHolderInterface $itemHolder
     *
     * @return bool
     */
    private function containsDeliveryFeeItem(ItemHolderInterface $itemHolder)
    {
        foreach ($itemHolder->getItems() as $item) {
            if ($item->isDeliveryFee()) {
                return true;
            }
        }

        return false;
    }

    /**
     * TODO 送料無料計算.
     *
     * @param ItemHolderInterface $itemHolder
     */
    private function addDeliveryFeeItem(ItemHolderInterface $itemHolder)
    {
        $DeliveryFeeType = $this->entityManager
            ->find(OrderItemType::class, OrderItemType::DELIVERY_FEE);
        // TODO
        $TaxInclude = $this->entityManager
            ->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        $Taxion = $this->entityManager
            ->find(TaxType::class, TaxType::TAXATION);

        /** @var Order $Order */
        $Order = $itemHolder;
        /* @var Shipping $Shipping */
        foreach ($Order->getShippings() as $Shipping) {
            $OrderItem = new OrderItem();
            $OrderItem->setProductName(trans('deliveryfeeprocessor.label.shippint_charge'))
                ->setPrice($Shipping->getShippingDeliveryFee())
                ->setPriceIncTax($Shipping->getShippingDeliveryFee())
                ->setTaxRate(8)
                ->setQuantity(1)
                ->setOrderItemType($DeliveryFeeType)
                ->setShipping($Shipping)
                ->setOrder($itemHolder)
                ->setTaxDisplayType($TaxInclude)
                ->setTaxType($Taxion);

            $itemHolder->addItem($OrderItem);
            $Shipping->addOrderItem($OrderItem);
        }
    }
}