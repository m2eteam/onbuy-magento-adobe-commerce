<?php

declare(strict_types=1);

namespace M2E\OnBuy\Model\ControlPanel\Module\Integration;

class OrderQuoteData
{
    private const PARAM_ORDER_ID = 'order_id';
    private const PARAM_PRINT = 'print';

    private \Magento\Framework\Data\Form\FormKey $formKey;
    private \Magento\Framework\UrlInterface $url;
    private \M2E\OnBuy\Model\Order\Repository $orderRepository;
    private \M2E\OnBuy\Model\Magento\Quote\BuilderFactory $quoteBuilderFactory;
    private \M2E\OnBuy\Model\Magento\Quote\Manager $quoteManager;

    public function __construct(
        \M2E\OnBuy\Model\Order\Repository $orderRepository,
        \M2E\OnBuy\Model\Magento\Quote\BuilderFactory $quoteBuilderFactory,
        \M2E\OnBuy\Model\Magento\Quote\Manager $quoteManager,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->formKey = $formKey;
        $this->url = $url;
        $this->orderRepository = $orderRepository;
        $this->quoteBuilderFactory = $quoteBuilderFactory;
        $this->quoteManager = $quoteManager;
    }

    public function execute(\Magento\Framework\App\RequestInterface $request): string
    {
        $orderId = $request->getParam(self::PARAM_ORDER_ID, '');
        $body = $this->printFormForCalculateAction($orderId);
        if ($request->getParam(self::PARAM_PRINT)) {
            try {
                $body .= $this->printOrderQuote((int)$orderId);
            } catch (\Throwable $exception) {
                $body .= sprintf(
                    '<div class="card"><h3>%s</h3><pre>%s</pre></div>',
                    'Error message: ' . $exception->getMessage(),
                    $exception->getTraceAsString()
                );
            }
        }

        return $this->renderHtml($body);
    }

    private function printFormForCalculateAction(string $orderId = ''): string
    {
        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->url->getUrl('*/*/*', ['action' => 'getPrintOrderQuoteData']);

        return <<<HTML
<form class="card gray" method="get" enctype="multipart/form-data" action="$actionUrl">
    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />
    <div class="row">
        <label>Order ID: </label>
        <input id="order_id" name="order_id" value="$orderId" required>
    </div>
    <div class="row">
        <button type="submit" class="button">Build</button>
    </div>
</form>
HTML;
    }

    private function printOrderQuote(int $orderId): string
    {
        $order = $this->orderRepository->get($orderId);

        // Store must be initialized before products
        // ---------------------------------------
        $order->associateWithStore();
        $order->associateItemsWithProducts();
        // ---------------------------------------

        $proxyOrder = $order->getProxy();
        $proxyOrder->setStore($order->getStore());

        $magentoQuoteBuilder = $this->quoteBuilderFactory->create($proxyOrder);

        $quote = $magentoQuoteBuilder->build();

        $shippingAddressData = $quote->getShippingAddress()->getData();
        unset(
            $shippingAddressData['cached_items_all'],
            $shippingAddressData['cached_items_nominal'],
            $shippingAddressData['cached_items_nonnominal'],
        );
        $billingAddressData = $quote->getBillingAddress()->getData();
        unset(
            $billingAddressData['cached_items_all'],
            $billingAddressData['cached_items_nominal'],
            $billingAddressData['cached_items_nonnominal'],
        );
        $quoteData = $quote->getData();
        unset(
            $quoteData['items'],
            $quoteData['extension_attributes'],
        );

        $items = [];
        foreach ($quote->getAllItems() as $item) {
            $items[] = $item->getData();
        }

        $this->quoteManager->save($quote->setIsActive(false));

        $orderData =  json_encode(
            json_decode(
                json_encode([
                    'Grand Total' => $quote->getGrandTotal(),
                    'Shipping Amount' => $quote->getShippingAddress()->getShippingAmount(),
                    'Quote Data' => $quoteData,
                    'Shipping Address Data' => $shippingAddressData,
                    'Billing Address Data' => $billingAddressData,
                    'Items' => $items,
                ]),
                true,
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return sprintf('<pre class="card">%s</pre>', $orderData);
    }

    private function renderHtml(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>OnBuy Module Tools | Order Quote Data</title>
    <style>
        * {
            font-size: 1rem;
            line-height: 1.5;
            box-sizing: border-box;
        }

        table.grid {
            border-color: black;
            border-style: solid;
            border-width: 1px 0 0 1px;
        }

        table.grid th {
            padding: 5px 20px;
            border-color: black;
            border-style: solid;
            border-width: 0 1px 1px 0;
            background-color: silver;
            color: white;
            font-weight: bold;
        }

        table.grid td {
            padding: 3px 10px;
            border-color: black;
            border-style: solid;
            border-width: 0 1px 1px 0;
        }

        pre {
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -o-pre-wrap;
            word-wrap: break-word;
        }

        input, select  {
            outline: 0;
            padding: .25rem .375rem;
            background-color: #fff;
            border: 1px solid #ced4da;
            color: #212529;
            border-radius: .25rem;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        input:focus, select:focus {
            color: #212529;
            background-color: #fff;
            border-color: #86b7fe;
            box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .25);
        }

        .row {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .row:not(:last-child) {
            margin-bottom: 10px;
        }

        .required:after {
            content: ' *';
            color: #d63745;
        }

        .button {
            display: inline-block;
            padding: .375rem .75rem;
            color: #fff;
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
            background-color: #0d6efd;
            border: 1px solid #0d6efd;
            border-radius: .25rem;
            transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .button:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .card {
            border-radius: .25rem;
            position: relative;
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid #dee2e6;
        }

        .red {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }

        .gray {
            color: #41464b;
            background-color: #e2e3e5;
            border-color: #d3d6d8;
        }

        .dark-gray {
            color: #141619;
            background-color: #d3d3d4;
            border-color: #bcbebf;
        }
    </style>
  </head>
  <body>$body</body>
</html>
HTML;
    }
}
