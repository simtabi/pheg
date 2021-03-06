<?php declare(strict_types=1);

namespace Simtabi\Pheg\Toolbox\Media\File;

use Simtabi\Pheg\Toolbox\Media\File\Exception\ResourceException;

/**
 * Manages file contents that are stored in memory as a string.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class FileMemory extends FileStream
{
    /**
     * Initializes the new in memory file manager.
     *
     * @param string  $string The string contents.
     * @param boolean $append Append new contents?
     *
     * @throws ResourceException If the stream could not be created.
     */
    public function __construct($string, $append)
    {
        $stream = fopen('php://memory', $append ? 'a+' : 'w+');

        if (!$stream) {
            throw new ResourceException('A new in memory file stream could not be created.');
        }

        parent::__construct($stream);

        $this->write($string);
        $this->seek(0);
    }
}
