<?php

namespace YoHang88\LetterAvatar;

use Intervention\Image\Gd\Font;
use Intervention\Image\Gd\Shapes\CircleShape;
use Intervention\Image\ImageManager;

class LetterAvatar
{
    /**
     * Image Type PNG
     */
    const MIME_TYPE_PNG = 'image/png';

    /**
     * Image Type JPEG
     */
    const MIME_TYPE_JPEG = 'image/jpeg';

    /**
     * @var string
     */
    private $name;


    /**
     * @var string
     */
    private $nameInitials;


    /**
     * @var string
     */
    private $shape;


    /**
     * @var int
     */
    private $size;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var string
     */
    private $backgroundColor;

    /**
     * @var string
     */
    private $foregroundColor;

    /**
     * LetterAvatar constructor.
     * @param string $name
     * @param string $shape
     * @param int    $size
     */
    public function __construct(string $name, string $shape = 'circle', int $size = 48)
    {
        $this->setName($name);
        $this->setImageManager(new ImageManager());
        $this->setShape($shape);
        $this->setSize($size);
    }

 /**
     * Generate a hash for the given settings combination. 
     * 
     * @param string $mimetype
     * @param int $quality
     * @return string
     */
    public function hashWithSettings($mimetype = self::MIME_TYPE_PNG, $quality = 90): string
    {
        $allowedMimeTypes = [
            self::MIME_TYPE_PNG,
            self::MIME_TYPE_JPEG,
        ];

        if(!in_array($mimetype, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('Invalid mimetype');
        }

        return sha1(json_encode([
            'mime' => $mimetype,
            'quality' => $quality,
            'shape' => $this->shape,
            'foreground' => $this->foregroundColor,
            'background_color' => $this->backgroundColor,
            'content' => $this->getInitials($this->name),
            'size' => $this->size
        ]));
    }

    /**
     * color in RGB format (example: #FFFFFF)
     * 
     * @param $backgroundColor
     * @param $foregroundColor
     */
    public function setColor($backgroundColor, $foregroundColor)
    {
        $this->backgroundColor = $backgroundColor;
        $this->foregroundColor = $foregroundColor;
        return $this;
    }
    
    /**
     * @param string $name
     */
    private function setName(string $name)
    {
        $this->name = $name;
    }


    /**
     * @param ImageManager $imageManager
     */
    private function setImageManager(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    /**
     * @param string $shape
     */
    private function setShape(string $shape)
    {
        $this->shape = $shape;
    }


    /**
     * @param int $size
     */
    private function setSize(int $size)
    {
        $this->size = $size;
    }


    /**
     * @return \Intervention\Image\Image
     */
    private function generate(): \Intervention\Image\Image
    {
        $isCircle = $this->shape === 'circle';

        $this->nameInitials = $this->getInitials($this->name);
        $this->backgroundColor = $this->backgroundColor ?: $this->stringToColor($this->name);
        $this->foregroundColor = $this->foregroundColor ?: '#fafafa';

        $canvas = $this->imageManager->canvas(480, 480, $isCircle ? null : $this->backgroundColor);

        if ($isCircle) {
            $canvas->circle(480, 240, 240, function (CircleShape $draw) {
                $draw->background($this->backgroundColor);
            });

        }

        $canvas->text($this->nameInitials, 240, 240, function (Font $font) {
            $font->file(__DIR__ . '/fonts/SourceSansPro-Semibold.ttf');
            $font->size(220);
            $font->color($this->foregroundColor);
            $font->valign('middle');
            $font->align('center');
        });

        return $canvas->resize($this->size, $this->size);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getInitials(string $name): string
    {
        $nameParts = $this->break_name($name);

        if(!$nameParts) {
            return '';
        }

        $secondLetter = isset($nameParts[1]) ? $this->getFirstLetter($nameParts[1]) : '';

        return $this->getFirstLetter($nameParts[0]) . $secondLetter;

    }

    /**
     * @param string $word
     * @return string
     */
    private function getFirstLetter(string $word): string
    {
        return mb_strtoupper(trim(mb_substr($word, 0, 1, 'UTF-8')));
    }

    /**
     * Get the generated Letter-Avatar as a png or jpg string
     *
     * @param string $mimetype
     * @param int    $quality
     * @return string
     */
    public function encode($mimetype = self::MIME_TYPE_PNG, $quality = 90): string
    {
        $allowedMimeTypes = [
            self::MIME_TYPE_PNG,
            self::MIME_TYPE_JPEG,
        ];
        if(!in_array($mimetype, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('Invalid mimetype');
        }
        return $this->generate()->encode($mimetype, $quality);
    }

    /**
     * Save the generated Letter-Avatar as a file
     *
     * @param        $path
     * @param string $mimetype
     * @param int    $quality
     * @return bool
     */
    public function saveAs($path, $mimetype = self::MIME_TYPE_PNG, $quality = 90): bool
    {
        if (empty($path)) {
            return false;
        }

        return \is_int(@file_put_contents($path, $this->encode($mimetype, $quality)));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->generate()->encode('data-url');
    }

    /**
     * Explodes Name into an array.
     * The function will check if a part is , or blank
     *
     * @param string $name Name to be broken up
     * @return array Name broken up to an array
     */
    private function break_name(string $name): array
    {
        $words = \explode(' ', $name);
        $words = array_filter($words, function($word) {
            return $word!=='' && $word !== ',';
        });
        return array_values($words);
    }

    /**
     * @param string $string
     * @return string
     */
    private function stringToColor(string $string): string
    {
        $colors = [
            '#1abc9c',
            '#2ecc71',
            '#3498db',
            '#9b59b6',
            '#34495e',
            '#16a085',
            '#27ae60',
            '#2980b9',
            '#8e44ad',
            '#2c3e50',
            '#f1c40f',
            '#e67e22',
            '#e74c3c',
            '#f39c12',
            '#d35400',
            '#c0392b'
        ];

        $index = crc32($string) % (count($colors) - 1);
        return $colors[$index];
    }

}
