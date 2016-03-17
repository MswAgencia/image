<?php

namespace MswAgencia\Image;

class Image {
  private $_resource;
  private $_edited_resource = null;
  private $_type;
  private $_filepath;
  private $_filename;
  private $_width;
  private $_height;

  public function __construct($filepath)
  {
    $imageinfo = getimagesize($filepath);
    $this->_width = $imageinfo[0];
    $this->_height = $imageinfo[1];
    $this->_type = $imageinfo[2];
    $this->_filepath = $filepath;
    $this->_filename = pathinfo($filepath, PATHINFO_BASENAME);
  }

  public function getType()
  {
    return $this->_type;
  }

  public function placeOver(Image $background, $posX, $posY)
  {
    $background->open();
    $this->open();

    imagecopy(
      $background->getResource(),
      $this->getResource(),
      $posX,
      $posY,
      0,
      0,
      $this->getWidth(),
      $this->getHeight()
    );
    $filepath = $background->getFilepath();
    return $this->_writeImageToDisk($filepath, $background->getResource(), $background->getType());
  }

  public function resizeTo($width, $height, $mode = 'resize')
  {
    switch($mode) {
      case 'resize':
        $this->resize($width, $height);
        break;
      case 'resizeCrop':
      case 'resize_crop':
        $this->resizeAndCrop($width, $height);
        break;
      case 'resize_fill':
        $this->resizeAndFill($width, $height);
        break;
      default:
        throw new \Exception('Modo de Redimensionamento desconhecido. ['. $mode .']');
    }
  }

  /**
   * @param string $dir deve terminar com trailing slash. (/)
   * @param string|bool $name (opcional) novo nome a ser dado para o arquivo ao salvá-lo. Se não for informado um nome aleatório gerato por getFilename() será usado.
   * @return Image ver método _writeImageToDisk()
   */
  public function save($dir, $name = false)
  {
    if(!is_dir($dir))
      throw new \Exception('Erro ao salvar imagem: $dir não é um diretório válido');

    if(!is_writable($dir))
      throw new \Exception('Erro ao salvar imagem: Não é possível escrever em $dir. Verifique permissões de escrita');

    if($name)
      $this->_filename = $name;
    else
      $name = $this->getFilename();

    $filepath = $dir . $name;
    $resource = (empty($this->_edited_resource))? $this->getResource() : $this->_edited_resource;

    return $this->_writeImageToDisk($filepath, $resource, $this->getType());
  }

  public function open()
  {
    switch($this->getType()) {
      case IMAGETYPE_JPEG:
        $this->_resource = imagecreatefromjpeg($this->_filepath);
        break;
      case IMAGETYPE_PNG:
        $this->_resource = imagecreatefrompng($this->_filepath);
        break;
      default:
        throw new \Exception('Tipo de Image não suportado. ['. $this->getType() .']');
    }
  }

  public function close()
  {
    if($this->_resource)
      imagedestroy($this->_resource);
    if($this->_edited_resource)
      imagedestroy($this->_edited_resource);
  }

  private function _writeImageToDisk($filepath, $resource, $type)
  {
    switch($type) {
      case IMAGETYPE_PNG:
        imagepng($resource, $filepath, 8);
        break;
      case IMAGETYPE_JPEG:
        imagejpeg($resource, $filepath, 80);
        break;
      default:
        throw new \Exception('Tipo de Imagem inválido: [' . $type .']');
    }

    if(!file_exists($filepath))
      throw new \Exception('Houve um problema ao salvar a imagem no disco.');

    return new Image($filepath);
  }

  public function resizeAndCrop($newWidth, $newHeight)
  {
    $this->open();
    $oldWidth = $this->_width;
    $oldHeight = $this->_height;

    $ratioX = $newWidth / $oldWidth;
    $ratioY = $newHeight / $oldHeight;

    if ($ratioX < $ratioY) {
      $startX = round(($oldWidth - ($newWidth / $ratioY))/2);
      $startY = 0;
      $oldWidth = round($newWidth / $ratioY);
      $oldHeight = $oldHeight;
    } else {
      $startX = 0;
      $startY = round(($oldHeight - ($newHeight / $ratioX))/2);
      $oldWidth = $oldWidth;
      $oldHeight = round($newHeight / $ratioX);
    }
    $applyWidth = $newWidth;
    $applyHeight = $newHeight;

    $this->_edited_resource = imagecreatetruecolor($applyWidth, $applyHeight);
    if($this->_type === IMAGETYPE_PNG) {
      imagealphablending($this->_edited_resource, false);
      imagesavealpha($this->_edited_resource, true);
    }
    imagecopyresampled($this->_edited_resource, $this->_resource, 0,0 , $startX, $startY, $applyWidth, $applyHeight, $oldWidth, $oldHeight);
  }

  public function resize($newWidth, $newHeight)
  {
    $this->open();
    $widthScale = 2;
    $heightScale = 2;
    $oldWidth = $this->_width;
    $oldHeight = $this->_height;

    if($newWidth)
      $widthScale = $newWidth / $oldWidth;
    if($newHeight)
      $heightScale = $newHeight / $oldHeight;

    if($widthScale < $heightScale) {
      $maxWidth = $newWidth;
      $maxHeight = false;
    } elseif ($widthScale > $heightScale ) {
      $maxHeight = $newHeight;
      $maxWidth = false;
    } else {
      $maxHeight = $newHeight;
      $maxWidth = $newWidth;
    }

    if($maxWidth > $maxHeight){
      $applyWidth = $maxWidth;
      $applyHeight = ($oldHeight * $applyWidth) / $oldWidth;
    } elseif ($maxHeight > $maxWidth) {
      $applyHeight = $maxHeight;
      $applyWidth = ($applyHeight * $oldWidth) / $oldHeight;
    } else {
      $applyWidth = $maxWidth;
      $applyHeight = $maxHeight;
    }

    $this->_edited_resource = imagecreatetruecolor($applyWidth, $applyHeight);
    if($this->_type === IMAGETYPE_PNG) {
      imagealphablending($this->_edited_resource, false);
      imagesavealpha($this->_edited_resource, true);
    }
    imagecopyresampled($this->_edited_resource, $this->_resource, 0, 0, 0, 0, $applyWidth, $applyHeight, $oldWidth, $oldHeight);
  }

  public function resizeAndFill($newWidth, $newHeight)
  {
    $this->open();
    $widthScale = 2;
    $heightScale = 2;
    $oldWidth = $this->_width;
    $oldHeight = $this->_height;

    if($newWidth)
      $widthScale = $newWidth / $oldWidth;
    if($newHeight)
      $heightScale = $newHeight / $oldHeight;

    if($widthScale < $heightScale) {
      $maxWidth = $newWidth;
      $maxHeight = false;
    } elseif ($widthScale > $heightScale ) {
      $maxHeight = $newHeight;
      $maxWidth = false;
    } else {
      $maxHeight = $newHeight;
      $maxWidth = $newWidth;
    }

    if($maxWidth > $maxHeight){
      $applyWidth = $maxWidth;
      $applyHeight = ($oldHeight * $applyWidth) / $oldWidth;
    } elseif ($maxHeight > $maxWidth) {
      $applyHeight = $maxHeight;
      $applyWidth = ($applyHeight * $oldWidth) / $oldHeight;
    } else {
      $applyWidth = $maxWidth;
      $applyHeight = $maxHeight;
    }

    $this->_edited_resource = imagecreatetruecolor($newWidth, $newHeight);
    if($this->_type === IMAGETYPE_PNG) {
      $backgroundFiller = imagecolorallocatealpha($this->_edited_resource, 0, 0, 0, 127);
      imagealphablending($this->_edited_resource, false);
      imagesavealpha($this->_edited_resource, true);
    }
    else {
      $backgroundFiller = imagecolorallocate($this->_edited_resource, 255, 255, 255);
    }
    imagefill($this->_edited_resource, 0,0, $backgroundFiller);

    imagecopyresampled($this->_edited_resource, $this->_resource, (($newWidth - $applyWidth) / 2), (($newHeight - $applyHeight) / 2), 0, 0, $applyWidth, $applyHeight, $oldWidth, $oldHeight);
  }

  public function getFilename()
  {
    return $this->_filename;
  }

  public function getFilepath()
  {
    return $this->_filepath;
  }

  public function getResource()
  {
    return $this->_resource;
  }

  public function getWidth()
  {
    return $this->_width;
  }

  public function getHeight()
  {
    return $this->_height;
  }
}
