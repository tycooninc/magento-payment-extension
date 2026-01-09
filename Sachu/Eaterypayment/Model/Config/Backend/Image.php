<?php

namespace Sachu\Eaterypayment\Model\Config\Backend;


use Sachu\Eaterypayment\Model\Config\Backend\File as BackendFile;

class Image extends BackendFile
{
    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'webp'];
    }
}
