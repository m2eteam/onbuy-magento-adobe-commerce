<?php

declare(strict_types=1);

namespace M2E\OnBuy\Model\ControlPanel\Module\Integration;

class InspectorData
{
    private const PARAM_PRODUCT_ID = 'listing_product_id';
    private const PARAM_PRINT = 'print';

    private \M2E\OnBuy\Model\Product\Repository $productRepository;
    private \M2E\OnBuy\Model\ResourceModel\Instruction\CollectionFactory $instructionCollectionFactory;
    private \M2E\OnBuy\Model\Instruction\SynchronizationTemplate\Checker\InputFactory $checkerInputFactory;
    private \M2E\OnBuy\Model\Instruction\SynchronizationTemplate\Checker\CheckerFactory $checkerFactory;
    private \Magento\Framework\Data\Form\FormKey $formKey;
    private \Magento\Framework\UrlInterface $url;

    public function __construct(
        \M2E\OnBuy\Model\Product\Repository $productRepository,
        \M2E\OnBuy\Model\ResourceModel\Instruction\CollectionFactory $instructionCollectionFactory,
        \M2E\OnBuy\Model\Instruction\SynchronizationTemplate\Checker\InputFactory $checkerInputFactory,
        \M2E\OnBuy\Model\Instruction\SynchronizationTemplate\Checker\CheckerFactory $checkerFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->productRepository = $productRepository;
        $this->instructionCollectionFactory = $instructionCollectionFactory;
        $this->checkerInputFactory = $checkerInputFactory;
        $this->checkerFactory = $checkerFactory;
        $this->formKey = $formKey;
        $this->url = $url;
    }

    public function execute(\Magento\Framework\App\RequestInterface $request): string
    {
        $listingProductId = $request->getParam(self::PARAM_PRODUCT_ID, '');

        $body = $this->printFormForCalculateAction($listingProductId);

        if ($request->getParam(self::PARAM_PRINT)) {
            try {
                $body .= $this->printCheckerResult((int)$listingProductId);
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

    private function printFormForCalculateAction(string $listingProductId = ''): string
    {
        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->url->getUrl('*/*/*', ['action' => 'getInspectorData']);

        return <<<HTML
<form class="card gray" method="get" enctype="multipart/form-data" action="$actionUrl">
    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />
    <div class="row">
        <label for="listing_product_id" class="required">Listing Product ID: </label>
        <input id="listing_product_id" name="listing_product_id" type="text" value="$listingProductId" required>
    </div>

    <div class="row">
        <button type="submit" class="button">Show</button>
    </div>
</form>
HTML;
    }

    private function printCheckerResult(int $listingProductId): string
    {
        $listingProduct = $this->productRepository->get($listingProductId);

        $instructionCollection = $this->instructionCollectionFactory->create();
        $instructionCollection->addFieldToFilter(
            \M2E\OnBuy\Model\ResourceModel\Instruction::COLUMN_LISTING_PRODUCT_ID,
            $listingProductId
        );

        $instructions = [];
        foreach ($instructionCollection->getItems() as $instruction) {
            $instructions[$instruction->getId()] = $instruction;
        }

        $checkerInput = $this->checkerInputFactory->create($listingProduct, $instructions);

        $html = '<pre class="card">';

        $notListedChecker = $this->checkerFactory->create(
            \M2E\OnBuy\Model\Instruction\SynchronizationTemplate\Checker\NotListedChecker::class,
            $checkerInput,
        );

        $html .= '<b>NotListed</b><br>';
        $html .= 'isAllowed: ' . json_encode($notListedChecker->isAllowed()) . '<br>';

        $inactiveChecker = $this->checkerFactory->create(
            \M2E\OnBuy\Model\Instruction\SynchronizationTemplate\Checker\InactiveChecker::class,
            $checkerInput,
        );

        $html .= '<b>Inactive</b><br>';
        $html .= 'isAllowed: ' . json_encode($inactiveChecker->isAllowed()) . '<br>';

        $activeChecker = $this->checkerFactory->create(
            \M2E\OnBuy\Model\Instruction\SynchronizationTemplate\Checker\ActiveChecker::class,
            $checkerInput,
        );

        $html .= '<b>Active</b><br>';
        $html .= 'isAllowed: ' . json_encode($activeChecker->isAllowed()) . '<br>';

        $html .= '<br>';
        $magentoProduct = $listingProduct->getMagentoProduct();
        $html .= 'isStatusEnabled: ' . json_encode($magentoProduct->isStatusEnabled()) . '<br>';
        $html .= 'isStockAvailability: ' . json_encode($magentoProduct->isStockAvailability());

        //--

        return $html;
    }

    private function renderHtml(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>OnBuy Module Tools | Inspector Data</title>
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
