<?php

namespace Palette\Effect;

use Palette\Picture;
use Imagick;

/**
 * Class Resize
 * @package Effect
 */
class Colorspace extends PictureEffect {


    /**
     * Aplikuje efekt na obr�zek
     * @param Picture $picture
     */
    public function apply(Picture $picture) {

        $image = $picture->getResource(Picture::WORKER_IMAGICK);

        if($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {

            $profiles = $image->getImageProfiles('*', FALSE);

            $path = realpath(__DIR__ . '/../Profiles/') . DIRECTORY_SEPARATOR;

            // POKUD NEM�ME CMYK ICC PROFIL P�ID�ME HO
            if(array_search('icc', $profiles) === false) {

                $image->profileImage('icc', file_get_contents($path . 'USWebUncoated.icc'));
            }

            // P�ID�N� RGB PROFILU
            $image->profileImage('icc', file_get_contents($path . 'sRGB _Color_Space_Profile.icm'));
        }

        $image->stripImage();
    }

}