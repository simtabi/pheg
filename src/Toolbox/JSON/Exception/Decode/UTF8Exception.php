<?php declare(strict_types=1);

namespace Simtabi\Pheg\Toolbox\JSON\Exception\Decode;

use Simtabi\Pheg\Toolbox\JSON\Exception\DecodeException;

/**
 * An exception that is thrown for a malformed UTF-8 character.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class UTF8Exception extends DecodeException
{
}
