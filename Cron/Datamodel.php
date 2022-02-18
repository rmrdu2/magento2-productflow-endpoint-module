<?php

namespace Productflow\Endpoint\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Datamodel
{
    public function __construct(
        DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem,
        \Productflow\Endpoint\Helper\Data $helper
    ) {
        $this->directoryList = $directoryList; // VAR Directory Path
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::PUB); // VAR Directory Path
        $this->_helper = $helper;
    }

    public function execute()
    {
        $rootPath = $this->directoryList->getPath('pub');
        $filepath = $rootPath.'/datamodel.json'; // at Directory path Create a Folder Export and FIle
        $json = $this->_helper->getDatamodelJson();
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $stream->write(json_encode($json));
        $stream->unlock();
        $stream->close();

        return $this;
    }
}
