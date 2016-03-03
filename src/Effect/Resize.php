<?php

namespace Palette\Effect;

use Imagick;
use Palette\Picture;

/**
 * Class Resize
 * @package Effect
 */
class Resize extends PictureEffect {

    /**
     * @const mód změny rozměrů
     */
    const MODE_FIT = 'fit';

    /**
     * @const mód změny rozměrů
     */
    const MODE_FILL = 'fill';

    /**
     * @const mód změny rozměrů
     */
    const MODE_STRETCH = 'stretch';

    /**
     * @const mód změny rozměrů
     */
    const MODE_CROP = 'crop';

    /**
     * @const mód změny rozměrů
     */
    const MODE_EXACT = 'exact';

    /**
     * @var array nastavení tohoto filtru
     */
    protected $settings = array(

        'width'  => NULL,
        'height' => NULL,
        'resizeMode' => NULL,
        'resizeSmaller' => NULL,
        'color' => NULL,
    );



    /**
     * Efekt zmenšení obrázku podle zadaných kritérií
     * @param $width
     * @param null $height
     * @param null $resizeMode
     */
    public function __construct($width, $height = NULL, $resizeMode = NULL, $resizeSmaller = 0, $color = '#FFFFFF') {

        if(!$height) {

            $height = $width;
        }

        $this->width  = $width;
        $this->height = $height;
        $this->resizeMode = $resizeMode;
        $this->resizeSmaller = $resizeSmaller ? 1 : 0;
        $this->color = $color;
    }


    /**
     * Aplikuje efekt na obrázek
     * @param Picture $picture
     */
    public function apply(Picture $picture) {

        if($picture->isGd()) {

            $this->resizeGd($picture->getResource());
        }
        else {

            $this->resizeImagick($picture->getResource(), $picture);
        }
    }


    /**
     * @param $width
     * @param $height
     * @return array
     */
    public function getNewDimensions($width, $height) {

        if(!$this->resizeSmaller && $this->height >= $height && $this->width >= $width) {

            return parent::getNewDimensions($width, $height);
        }

        if(in_array($this->resizeMode, array($this::MODE_CROP, $this::MODE_STRETCH, $this::MODE_EXACT))) {

            return parent::getNewDimensions($this->width, $this->height);
        }
        elseif($this->resizeMode === $this::MODE_FILL) {

            $ratioH = $this->height / $height;
            $ratioW = $this->width / $width;

            $widthFill  = max($width * $ratioH, $width * $ratioW);
            $heightFill = max($height * $ratioH, $height * $ratioW);
            $ratio  = max($widthFill / $width, $heightFill / $height);

            return parent::getNewDimensions(round($width * $ratio), round($height * $ratio));
        }
        else {

            $pictureWidth  = $this->width;
            $pictureHeight = $this->height;

            if($width > $pictureWidth || $height > $pictureHeight) {

                if($width > $height) {

                    $pictureHeight = floor(($height / $width) * $pictureWidth);
                }
                elseif($width < $height) {

                    $pictureWidth = floor(($width / $height) * $pictureHeight);
                }
            }

            return parent::getNewDimensions($pictureWidth, $pictureHeight);
        }
    }


    /**
     * Změna rozměrů obrázku pomocí Imagick
     * @param Imagick $imagick
     */
    private function resizeImagick(Imagick $imagick, Picture $picture) {

        if(!$this->resizeSmaller && $this->height > $imagick->getImageHeight() && $this->width > $imagick->getImageWidth()) {

            return;
        }

        if($this->resizeMode === $this::MODE_CROP) {
            $imagick->cropThumbnailImage($this->width, $this->height);

        }
        elseif($this->resizeMode === $this::MODE_FILL) {

            $ratioH = $this->height / $imagick->getImageHeight();
            $ratioW = $this->width / $imagick->getImageWidth();

            $width  = max($imagick->getImageWidth() * $ratioH, $imagick->getImageWidth() * $ratioW);
            $height = max($imagick->getImageHeight() * $ratioH, $imagick->getImageHeight() * $ratioW);
            $ratio  = max($width / $imagick->getImageWidth(), $height / $imagick->getImageHeight());

            $imagick->scaleImage(round($imagick->getImageWidth() * $ratio), round($imagick->getImageHeight() * $ratio), TRUE);
        }
        elseif($this->resizeMode === $this::MODE_STRETCH) {

            $imagick->scaleImage($this->width, $this->height, FALSE);
        }
        elseif($this->resizeMode === $this::MODE_EXACT) {

            $imagick->scaleImage($this->width, $this->height, TRUE);

            if(strtolower(pathinfo($picture->getImage(), PATHINFO_EXTENSION)) === 'png') {

                $color = 'transparent';
            }
            else {

                $color = $this->color;
            }

            $rectangle = new Imagick();
            $rectangle->setFormat('png');
            $rectangle->newImage($this->width, $this->height, new \ImagickPixel($color));
            $rectangle->compositeImage($imagick, $imagick->getImageCompose(),

                ($rectangle->getImageWidth() - $imagick->getImageWidth()) / 2,
                ($rectangle->getImageHeight() - $imagick->getImageHeight()) / 2
            );

            $picture->setResource($rectangle);
        }
        else {
            $imagick->scaleImage($this->width, $this->height, TRUE);

        }
    }


    /**
     * Změna rozměrů obrázku pomocí GD
     * @param $resource
     */
    private function resizeGd(&$resource) {

        $origWidth  = imagesx($resource);
        $origHeight = imagesy($resource);
        $resizeX    = 0;
        $resizeY    = 0;

        if($this->resizeMode === $this::MODE_FIT) {

            if($origWidth > $this->width || $origHeight > $this->height) {

                if($origWidth > $origHeight) {

                    $this->height = floor(($origHeight / $origWidth) * $this->width);
                }
                elseif($origWidth < $origHeight) {

                    $this->width = floor(($origWidth / $origHeight) * $this->height);
                }
            }
        }
        elseif($this->resizeMode !== $this::MODE_SCALE) {

            if(($origWidth / $origHeight) > ($this->width / $this->height)) {

                $widthTmp = $origHeight * $this->width / $this->height;

                $resizeX = ($origWidth - $widthTmp) / 2;
                $origWidth = $widthTmp;
            }
            elseif(($origWidth / $origHeight) < ($this->width / $this->height)) {

                $heightTmp = $origWidth * $this->height / $this->width;

                $resizeY = ($origHeight - $heightTmp) / 2;
                $origHeight = $heightTmp;
            }
        }

        $pictureResized = imagecreatetruecolor($this->width, $this->height);

        imagecopyresampled($pictureResized, $resource, 0, 0, $resizeX, $resizeY, $this->width, $this->height, $origWidth, $origHeight);

        $resource = $pictureResized;
    }

}