<?php declare(strict_types=1);

namespace Simtabi\Pheg\Toolbox\Colors\Traits;

trait WithAlphaTrait
{
    /**
     * @var double
     */
    protected $alpha;

    /**
     * @param null $alpha
     *
     * @return $this|float
     */
    public function alpha($alpha = null)
    {
        if ($alpha !== null) {
            $this->alpha = ($alpha <= 1) ? $alpha : 1;
            return $this;
        }
        return $this->alpha;
    }

    /**
     * @return string
     */
    protected function validationRules()
    {
        return '/^(\d{1,3}),(\d{1,3}),(\d{1,3}),(\d\.\d{1,})$/';
    }

    /**
     * @param $color
     *
     * @return string
     */
    protected function fixPrecision($color)
    {
        if (strpos($color, ',') !== false) {
            $parts = explode(',', $color);
            $parts[3] = strpos($parts[3], '.') === false ? $parts[3] . '.0' : $parts[3];
            $color = implode(',', $parts);
        }
        return $color;
    }

    /**
     * @param string $alpha
     * @return float
     */
    protected function alphaHexToFloat(string $alpha): float
    {
        return sprintf('%0.2f', hexdec($alpha) / 255);
    }

    /**
     * @param float $alpha
     * @return string
     */
    protected function alphaFloatToHex(float $alpha): string
    {
        return dechex($alpha * 255);
    }
}
