<?php declare(strict_types=1);

namespace Simtabi\Pheg\Toolbox;

final class Avatar
{

    public function __construct() {}


    /**
     * Get Gravatar image by email.
     *
     * @param string $email
     * @param int $size
     * @param string $rating [g|pg|r|x]
     * @param string $default
     * @return string
     */

    public function getGravatar($email, $size = 200, $isHttps = false, $rating = 'g', $default = 'monsterid' ): ?string
    {
        if ( $isHttps ) {
            $url = 'https://secure.gravatar.com/';
        } else {
            $url = 'http://www.gravatar.com/';
        }

        return $url . 'avatar/' . md5(strtolower(trim($email))) . '/?d=' . $default . '&s=' . (int) abs( $size ) . '&r=' . $rating;
    }

}