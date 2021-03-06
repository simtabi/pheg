<?php

declare(strict_types=1);

/*
 * This file is part of the PHP Humanizer Library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Simtabi\Pheg\Toolbox\Humanizer\Resources\Ordinal;

use Simtabi\Pheg\Toolbox\Humanizer\Number\Ordinal\StrategyInterface;

final class FrStrategy implements StrategyInterface
{
    public function isPrefix() : bool
    {
        return false;
    }

    public function ordinalIndicator($number) : string
    {
        $absNumber = \abs((int) $number);

        if ($absNumber == 1) {
            return 'er';
        }

        return 'e';
    }
}
