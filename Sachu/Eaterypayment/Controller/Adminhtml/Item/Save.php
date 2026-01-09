<?php

namespace Sachu\Eaterypayment\Controller\Adminhtml\Item;

class Save extends \Magento\Backend\App\Action
{
    protected $fileUploader;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Sachu\Eaterypayment\Model\FileUploader $fileUploader
    ) {
        parent::__construct($context);
        $this->fileUploader = $fileUploader;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        // Handle file upload
        if (isset($_FILES['image_upload']) && isset($_FILES['image_upload']['name']) && strlen($_FILES['image_upload']['name'])) {
            try {
                $result = $this->fileUploader->uploadFile('image_upload');
                // The filename to be saved in your database
                $data['image_upload'] = $result['file'];
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        // Logic to save $data to your DB table goes here...
    }
}
