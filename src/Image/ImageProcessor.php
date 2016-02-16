<?php

namespace MswAgencia\Image;

class ImageProcessor {

	public function create($image) {
		$imageInfo = getimagesize($image);
		switch($imageInfo[2]) {
			case 2:
				return imagecreatefromjpeg($image);
				break;
			case 3:
				return imagecreatefrompng($image);
				break;
			default:
				throw new \Exception('Tipo de imagem não suportado.');
		}
	}

	public function resize(&$image, $width, $height) {
		$resized = imagecreatetruecolor($width, $height);
		imagecopyresized($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
		$image = $resized;
	}

	/**
	 * No centro somente.
	 * @param  [type] &$image      [description]
	 * @param  [type] &$background [description]
	 * @return [type]              [description]
	 */
	public function placeOver(&$image, &$background, $posX = null, $posY = null) {
		if(empty($posX))
			$posX = (imagesx($background) / 2) - (imagesx($image) / 2);

		$newImage = imagecopy(
			$background,
			$image,
			$posX,
			$posY,
			0,
			0,
			imagesx($image),
			imagesy($image)
		);

		return $newImage;
	}

	public function saveFile(&$image, $path) {
		imagejpeg($image, $path, 90);
		imagedestroy($image);
	}

	public function writeTextOnImage($fontSize, $fontFile, $fontColor, $text, &$img, $positionX, $positionY) {
		if(!$text)
			return false;

		$text = preg_replace('/[?]+/', "", $text);

		$text = \Cake\Utility\Text::wrap($text, 40);
		$text = \Cake\Utility\Text::truncate($text, 80, ['exact' => false]);

		$fontColor = $this->hex2rgb($fontColor);
		$color = imagecolorallocate($img, $fontColor[0], $fontColor[1], $fontColor[2]);

		$result = imagettftext($img, $fontSize, 0, $positionX, $positionY, $color, $fontFile, trim($text));
	}

	protected function hex2rgb($hex) {
		$hex = str_replace("#", "", $hex);

	   if(strlen($hex) == 3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }
	   $rgb = array($r, $g, $b);
	   return $rgb; // returns an array with the rgb values
	}
}
