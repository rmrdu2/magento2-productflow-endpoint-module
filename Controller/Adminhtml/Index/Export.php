<?php

namespace Productflow\Endpoint\Controller\Adminhtml\Index;

use Magento\Framework\App\Filesystem\DirectoryList;

class Export extends \Magento\Backend\App\Action
{
    protected $fileFactory = false;

    protected $resultRawFactory = false;

    private $directory;

    private $directoryList;

    private $storeManager;

    protected $_helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Filesystem $filesystem,
        DirectoryList $directoryList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Productflow\Endpoint\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR); // VAR Directory Path
        $this->directoryList = $directoryList; // VAR Directory Path
        $this->storeManager = $storeManager;
        $this->_helper = $helper;
    }

    public function execute()
    {
        $json = $this->getDatamodelJson();
        $name = date('m-d-Y-H-i-s').'.json';
        $varPath = $this->directoryList->getPath('var');
        $filepath = $varPath.'/productflow/datamodel-'.$name; // at Directory path Create a Folder Export and FIle

        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $stream->write(json_encode($json));
        $stream->unlock();
        $stream->close();

        try {
            $this->fileFactory->create(
                'datamodel-'.$name,
                [
                    'type' => 'filename',
                    'value' => $filepath,
                ],
                DirectoryList::VAR_DIR, //basedir
                'application/octet-stream',
                '' // content length will be dynamically calculated
            );
        } catch (\Exception $exception) {
            // Add your own failure logic here
            var_dump($exception->getMessage());
            exit;
        }
        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw;
    }

    public function getDatamodelJson()
    {
        $json = $this->_helper->getDatamodelJson();

        return $json;
    }
}
