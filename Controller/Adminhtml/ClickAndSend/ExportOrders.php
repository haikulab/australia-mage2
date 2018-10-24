<?php
/**
 * Fontis Australia Extension for Magento 2
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @copyright  Copyright (c) 2017 Fontis Pty. Ltd. (https://www.fontis.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Fontis\Australia\Controller\Adminhtml\ClickAndSend;

use Exception;
use Fontis\Australia\Model\ClickAndSend\Export;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

class ExportOrders extends Action
{
    const ADMINHTML_SALES_ORDER_INDEX = 'sales/order/index';

    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * Factory to create file model
     *
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Export
     */
    protected $clickAndSendExport;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Export $clickAndSendExport
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Export $clickAndSendExport
    ) {
        $this->fileFactory = $fileFactory;
        $this->clickAndSendExport = $clickAndSendExport;
        parent::__construct($context);
    }

    /**
     * Generate and export a CSV file for the given orders.
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $request = $this->getRequest();

        if (!$request->isPost()) {
            // No posted data, send back to order grid page
            $this->getMessageManager()->addErrorMessage(__('No orders found.'));

            return $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
        }

        $orderIds = $request->getPost('selected', array());

        if (empty($orderIds)) {
            $this->getMessageManager()->addErrorMessage(__('No orders found.'));

            return $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
        }

        try {
            $filePath = $this->clickAndSendExport->exportOrders($orderIds);

            return $this->fileFactory->create(basename($filePath), array("type" => "filename", "value" => basename($filePath)), DirectoryList::TMP);
        } catch (LocalizedException $e) {
            $this->getMessageManager()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->getMessageManager()->addError(__("An unknown error occurred while exporting ClickAndSend data for the selected orders."));
        }

        return $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
    }
}
