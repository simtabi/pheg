<?php declare(strict_types=1);

namespace Simtabi\Pheg\Toolbox\Media\Image;

use Exception;
use Simtabi\Pheg\Toolbox\Filter;
use Simtabi\Pheg\Toolbox\Vars;
use Simtabi\Enekia\Vanilla\Validators;

/**
 * Class Image
 *
 * @package Simtabi\Pheg\Toolbox
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class ImageHandler
{
    
    public const TOP_LEFT        = 'tl';
    public const LEFT            = 'l';
    public const BOTTOM_LEFT     = 'bl';
    public const TOP             = 't';
    public const CENTER          = 'c';
    public const BOTTOM          = 'b';
    public const TOP_RIGHT       = 'tr';
    public const RIGHT           = 'r';
    public const BOTTOM_RIGHT    = 'bt';

    private const BASE_PARTS     = 3;
    private const EXTENDS_PARTS  = 4;
    private const DETAILED_PARTS = 6;

    private Validators $validators;
    private Vars       $vars;

    public function __construct()
    {
        $this->validators = new Validators();
        $this->vars       = new Vars;
    }

    /**
     * Require GD library
     *
     * @param bool $throwException
     * @return bool
     * @throws Exception
     */
    public function checkGD(bool $throwException = true): bool
    {
        $isGd = extension_loaded('gd');

        // Require GD library
        if ($throwException && !$isGd) {
            throw new Exception('Required extension GD is not loaded.');
        }

        return $isGd;
    }

    /**
     * Converts a hex color value to its RGB equivalent
     *
     * @param string|array $origColor Hex color string, array(red, green, blue) or array(red, green, blue, alpha).
     *                                Where red, green, blue - integers 0-255, alpha - integer 0-127
     * @return int[]
     * @throws Exception
     * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
     */
    public function normalizeColor($origColor): array
    {
        $result = [];

        if (is_string($origColor)) {
            $result = $this->normalizeColorString($origColor);
        } elseif ((count($origColor) === self::BASE_PARTS || count($origColor) === self::EXTENDS_PARTS)) {
            $result = $this->normalizeColorArray($origColor);
        }

        if (count($result) !== self::EXTENDS_PARTS) {
            throw new Exception('Undefined color format (string): ' . print_r($origColor, true));
        }

        return $result;
    }

    /**
     * Normalize color from string
     *
     * @param string $origColor
     * @return array
     * @throws Exception
     */
    protected function normalizeColorString(string $origColor): array
    {
        $color = trim($origColor, '#');
        $color = trim($color);

        if (strlen($color) === self::DETAILED_PARTS) {
            [$red, $green, $blue] = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
        } elseif (strlen($color) === self::BASE_PARTS) {
            [$red, $green, $blue] = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
        } else {
            throw new Exception('Undefined color format (string): ' . $origColor);
        }

        $red   = hexdec($red);
        $green = hexdec($green);
        $blue  = hexdec($blue);

        return [$red, $green, $blue, 0];
    }

    /**
     * Normalize color from array
     *
     * @param array $origColor
     * @return int[]
     */
    protected function normalizeColorArray(array $origColor): array
    {
        $result = [];

        if (
            array_key_exists('r', $origColor) &&
            array_key_exists('g', $origColor) &&
            array_key_exists('b', $origColor)
        ) {
            $result = [
                $this->color((int)$origColor['r']),
                $this->color((int)$origColor['g']),
                $this->color((int)$origColor['b']),
                $this->alpha((float)($origColor['a'] ?? 0)),
            ];
        } elseif (
            array_key_exists('0', $origColor) &&
            array_key_exists(1, $origColor) &&
            array_key_exists(2, $origColor)
        ) {
            $result = [
                $this->color((int)$origColor[0]),
                $this->color((int)$origColor[1]),
                $this->color((int)$origColor[2]),
                $this->alpha((float)($origColor[3] ?? 0)),
            ];
        }

        return $result;
    }

    /**
     * Same as PHP's imagecopymerge() function, except preserves alpha-transparency in 24-bit PNGs
     *
     * @link http://www.php.net/manual/en/function.imagecopymerge.php#88456
     *
     * @param mixed $dstImg   Dist image resource
     * @param mixed $srcImg   Source image resource
     * @param array $dist     Left and Top offset of dist
     * @param array $src      Left and Top offset of source
     * @param array $srcSizes Width and Height  of source
     * @param int   $opacity
     */
    public function imageCopyMergeAlpha(
        $dstImg,
        $srcImg,
        array $dist,
        array $src,
        array $srcSizes,
        int $opacity
    ): void {
        [$dstX, $dstY]          = $dist;
        [$srcX, $srcY]          = $src;
        [$srcWidth, $srcHeight] = $srcSizes;

        // Get image width and height and percentage
        $opacity /= 100;
        $width   = (int)imagesx($srcImg);
        $height  = (int)imagesy($srcImg);

        // Turn alpha blending off
        $this->addAlpha($srcImg, false);

        // Find the most opaque pixel in the image (the one with the smallest alpha value)
        $minBaseAlpha = 127;
        $minAlpha     = $minBaseAlpha;
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $alpha = (imagecolorat($srcImg, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minAlpha) {
                    $minAlpha = $alpha;
                }
            }
        }

        // Loop through image pixels and modify alpha for each
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                // Get current alpha value (represents the TRANSPARENCY!)
                $colorXY = imagecolorat($srcImg, $x, $y);
                $alpha = ($colorXY >> 24) & 0xFF;

                // Calculate new alpha
                if ($minAlpha !== $minBaseAlpha) {
                    $alpha = $minBaseAlpha + $minBaseAlpha * $opacity * ($alpha - $minBaseAlpha)
                        / ($minBaseAlpha - $minAlpha);
                } else {
                    $alpha += $minBaseAlpha * $opacity;
                }

                // Get the color index with new alpha
                $alphaColorXY = imagecolorallocatealpha(
                    $srcImg,
                    ($colorXY >> 16) & 0xFF,
                    ($colorXY >> 8) & 0xFF,
                    $colorXY & 0xFF,
                    (int)$alpha
                );

                // Set pixel with the new color + opacity
                if (!imagesetpixel($srcImg, $x, $y, (int)$alphaColorXY)) {
                    return;
                }
            }
        }

        // Copy it
        $this->addAlpha($srcImg);
        $this->addAlpha($dstImg);
        imagecopy($dstImg, $srcImg, $dstX, $dstY, $srcX, $srcY, $srcWidth, $srcHeight);
    }

    /**
     * Check opacity value
     *
     * @param float $opacity
     * @return int
     */
    public function opacity(float $opacity): int
    {
        if ($opacity <= 1) {
            $opacity *= 100;
        }

        $opacity = (new Filter())->int($opacity);
        $opacity = Numbers::limit($opacity, 0, 100);

        return $opacity;
    }

    /**
     * Convert opacity value to alpha
     *
     * @param float $opacity
     * @return int
     */
    public function opacity2Alpha(float $opacity): int
    {
        $opacity = $this->opacity($opacity);
        $opacity /= 100;

        $alpha = 127 - (127 * $opacity);
        return $this->alpha((int)$alpha);
    }

    /**
     * Returns valid value to change color segment of a image (0..255)
     *
     * @param float $color
     * @return int
     */
    public function color(float $color): int
    {
        return $this->vars->range($color, 0, 255);
    }

    /**
     * Returns valid value of alpha-channel
     *
     * @param float $color
     * @return int
     */
    public function alpha(float $color): int
    {
        return $this->vars->range($color, 0, 127);
    }

    /**
     * Returns valid value of image rotation (-360..360)
     *
     * @param float $color
     * @return int
     */
    public function rotate(float $color): int
    {
        return $this->vars->range($color, -360, 360);
    }

    /**
     * Returns valid value to make image bright (-255..255)
     *
     * @param float $brightness
     * @return int
     */
    public function brightness(float $brightness): int
    {
        return $this->vars->range($brightness, -255, 255);
    }

    /**
     * Returns valid value to change contrast of a image (-100..100)
     *
     * @param float $contrast
     * @return int
     */
    public function contrast(float $contrast): int
    {
        return $this->vars->range($contrast, -100, 100);
    }

    /**
     * Returns valid value to change color segment of a image (-255..255)
     *
     * @param float $colorize
     * @return int
     */
    public function colorize(float $colorize): int
    {
        return $this->vars->range($colorize, -255, 255);
    }

    /**
     * Returns valid value to change smoothness of a image (1..10)
     *
     * @param float $smooth
     * @return int
     */
    public function smooth(float $smooth): int
    {
        return $this->vars->range($smooth, 1, 10);
    }

    /**
     * Returns valid value of image direction: 'x', 'y', 'xy', 'yx'
     *
     * @param string $direction
     * @return string
     */
    public function direction(string $direction): string
    {
        $direction = strtolower(trim($direction));

        if (in_array($direction, ['x', 'y', 'xy', 'yx'], true)) {
            return $direction;
        }

        return 'x';
    }

    /**
     * Return valid value to blur image (1-10)
     *
     * @param float $blur
     * @return int
     */
    public function blur(float $blur): int
    {
        return $this->vars->range($blur, 1, 10);
    }

    /**
     * Return valid value of percent (0-100)
     *
     * @param float $percent
     * @return int
     */
    public function percent(float $percent): int
    {
        return $this->vars->range($percent, 0, 100);
    }

    /**
     * Returns valid value of image quality (0..100)
     *
     * @param float $percent
     * @return int
     */
    public function quality(float $percent): int
    {
        return $this->vars->range($percent, 0, 100);
    }

    /**
     * Convert string to binary data
     *
     * @param string $imageString
     * @return string
     */
    public function strToBin(string $imageString): string
    {
        $cleanedString = str_replace(' ', '+', (string)preg_replace('#^data:image/[^;]+;base64,#', '', $imageString));
        $result        = base64_decode($cleanedString, true);

        if (!$result) {
            $result = $imageString;
        }

        return $result;
    }

    /**
     * Check position name
     *
     * @param string $position
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function position(string $position): string
    {
        $position = strtolower(trim($position));
        $position = str_replace(['-', '_'], ' ', $position);

        if (in_array($position, [self::TOP, 'top', 't'], true)) {
            return self::TOP;
        }

        if (in_array($position, [self::TOP_RIGHT, 'top right', 'right top', 'tr', 'rt'], true)) {
            return self::TOP_RIGHT;
        }

        if (in_array($position, [self::RIGHT, 'right', 'r'], true)) {
            return self::RIGHT;
        }

        if (in_array($position, [self::BOTTOM_RIGHT, 'bottom right', 'right bottom', 'br', 'rb'], true)) {
            return self::BOTTOM_RIGHT;
        }

        if (in_array($position, [self::BOTTOM, 'bottom', 'b'], true)) {
            return self::BOTTOM;
        }

        if (in_array($position, [self::BOTTOM_LEFT, 'bottom left', 'left bottom', 'bl', 'lb'], true)) {
            return self::BOTTOM_LEFT;
        }

        if (in_array($position, [self::LEFT, 'left', 'l'], true)) {
            return self::LEFT;
        }

        if (in_array($position, [self::TOP_LEFT, 'top left', 'left top', 'tl', 'lt'], true)) {
            return self::TOP_LEFT;
        }

        return self::CENTER;
    }

    /**
     * Determine position
     *
     * @param string $position Position name or code
     * @param array  $canvas   Width and Height of canvas
     * @param array  $box      Width and Height of box that will be located on canvas
     * @param array  $offset   Forced offset X, Y
     * @return array|null
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getInnerCoords(string $position, array $canvas, array $box, array $offset = [0, 0]): ?array
    {
        $positionCode = $this->position($position);
        [$canvasW, $canvasH] = $canvas;
        [$boxW, $boxH] = $box;
        [$offsetX, $offsetY] = $offset;

        // Coords map:
        // 00  10  20  =>  tl  t   tr
        // 01  11  21  =>  l   c   r
        // 02  12  22  =>  bl  b   br

        // X coord
        $xCord0 = $offsetX + 0;                             //  bottom-left     left        top-left
        $xCord1 = $offsetX + ($canvasW / 2) - ($boxW / 2);  //  bottom          center      top
        $xCord2 = $offsetX + $canvasW - $boxW;              //  bottom-right    right       top-right

        // Y coord
        $yCord0 = $offsetY + 0;                             //  top-left        top         top-right
        $yCord1 = $offsetY + ($canvasH / 2) - ($boxH / 2);  //  left            center      right
        $yCord2 = $offsetY + $canvasH - $boxH;              //  bottom-left     bottom      bottom-right

        if ($positionCode === self::TOP_LEFT) {
            return [$xCord0, $yCord0];
        }

        if ($positionCode === self::LEFT) {
            return [$xCord0, $yCord1];
        }

        if ($positionCode === self::BOTTOM_LEFT) {
            return [$xCord0, $yCord2];
        }

        if ($positionCode === self::TOP) {
            return [$xCord1, $yCord0];
        }

        if ($positionCode === self::BOTTOM) {
            return [$xCord1, $yCord2];
        }

        if ($positionCode === self::TOP_RIGHT) {
            return [$xCord2, $yCord0];
        }

        if ($positionCode === self::RIGHT) {
            return [$xCord2, $yCord1];
        }

        if ($positionCode === self::BOTTOM_RIGHT) {
            return [$xCord2, $yCord2];
        }

        return [$xCord1, $yCord1];
    }

    /**
     * Add alpha chanel to image resource
     *
     * @param mixed $image   Image GD resource
     * @param bool  $isBlend Add alpha blending
     */
    public function addAlpha($image, bool $isBlend = true): void
    {
        imagesavealpha($image, true);
        imagealphablending($image, $isBlend);
    }

    public function dpi($res_x, $res_y, $dpiUnit = 2.54){

        $height = intval($res_y) / $dpiUnit;
        $width  = intval($res_x) / $dpiUnit;

        return round($width / $height);
    }
}
