<?php

namespace Sachu\Eaterypayment\Model;

use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class FileUploader
{
    protected $uploaderFactory;
    protected $mediaDirectory;

    public function __construct(
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function uploadFile($fileId)
    {
        $basePath = 'eatery';
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);

        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'pdf']);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        $result = $uploader->save($this->mediaDirectory->getAbsolutePath($basePath));

        return $result;
    }
}
