<?php

declare(strict_types=1);

namespace M2E\OnBuy\Controller\Adminhtml\ControlPanel\Module;

class Integration extends \M2E\OnBuy\Controller\Adminhtml\ControlPanel\AbstractCommand
{
    private \Magento\Framework\Data\Form\FormKey $formKey;
    private \M2E\OnBuy\Model\ControlPanel\Module\Integration\RequestData $requestData;
    private \M2E\OnBuy\Model\ControlPanel\Module\Integration\InspectorData $inspectorData;
    private \M2E\OnBuy\Model\ControlPanel\Module\Integration\OrderQuoteData $orderQuoteData;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \M2E\OnBuy\Model\ControlPanel\Module\Integration\RequestData $requestData,
        \M2E\OnBuy\Model\ControlPanel\Module\Integration\InspectorData $inspectorData,
        \M2E\OnBuy\Model\ControlPanel\Module\Integration\OrderQuoteData $orderQuoteData,
        \M2E\OnBuy\Helper\View\ControlPanel $controlPanelHelper,
        \M2E\OnBuy\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($controlPanelHelper, $context);

        $this->formKey = $formKey;
        $this->requestData = $requestData;
        $this->inspectorData = $inspectorData;
        $this->orderQuoteData = $orderQuoteData;
    }

    /**
     * @title "Print Request Data"
     * @description "Calculate Allowed Action for Listing Product"
     */
    public function getRequestDataAction()
    {
        return $this->requestData->execute($this->getRequest());
    }

    /**
     * @title "Build Order Quote"
     * @description "Print Order Quote Data"
     * @throws \Throwable
     */
    public function getPrintOrderQuoteDataAction()
    {
        return $this->orderQuoteData->execute($this->getRequest());
    }

    /**
     * @title "Print Inspector Data"
     * @description "Print Inspector Data"
     * @new_line
     */
    public function getInspectorDataAction()
    {
        return $this->inspectorData->execute($this->getRequest());
    }
}
