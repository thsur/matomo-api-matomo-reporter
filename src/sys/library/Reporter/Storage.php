<?php

namespace Reporter;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Storage {

    /**
     * @var  FileSystem
     */
    protected $filesystem;
    
    /**
     * @param RequestStack
     */
    public function __construct(Filesystem $fs) {

        $this->filesystem = $fs;
    }
}