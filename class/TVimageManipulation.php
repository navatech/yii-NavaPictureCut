<?php
ini_set('gd.jpeg_ignore_warning', 1);

/**
 *   * TVimageManipulation.php
 * Copyright (c) 2013 Tuyoshi Vinicius (tuyoshi_vinicius@hotmail.com))
 * Version: 1.0
 *     Classe PHP para redimensionar dinamicamente, cortes e imagens rotativas para fins miniatura e quer exibi-los
 * on-the-fly, ou salvá-los.
 *   *
 *   */
class TVimageManipulation {

	/*******************************************
	 *     // Mensagem de erro para exibir, se houver*/
	private $errmsg;

	//Se existe ou não é um erro
	// @var boolean
	private $error;

	//Formato do arquivo de imagem
	//@var string
	private $format;

	//O nome do arquivo e caminho do arquivo de imagem
	//@var string
	private $fileName;

	//Imagem meta dados se algum está disponível (JPEG / TIFF) através da Biblioteca exif
	//@var array
	public $imageMeta;

	//Dimensões atuais da imagem de trabalho
	//@var array
	private $currentDimensions;

	//Novas dimensões da imagem de trabalho
	//@var array
	private $newDimensions;

	//Recurso de imagem para a imagem do recém-manipulado
	//@var resource
	private $newImage;

	//Recurso de imagem para a imagem antes de manipulação prévia
	//@var resource
	private $oldImage;

	//Recurso de imagem para a imagem que está sendo atualmente manipulado
	//@var resource
	private $workingImage;

	//Percentual para redimensionar a imagem por
	//@var int
	private $percent;

	//Largura máxima de imagem durante redimensionamento
	//@var int
	private $maxWidth;

	//Altura máxima de imagem durante redimensionamento
	//@var int
	private $maxHeight;

	/*********************************************
	 * construtor da classe
	 **********************************************
	 *
	 * @param $fileName
	 */
	public function __construct($fileName) {
		// garante que a biblioteca GD está instalado
		if (!function_exists("gd_info")) {
			echo 'Você não tem a biblioteca GD instalada. Esta classe exige a biblioteca GD para funcionar corretamente.';
			exit;
		}
		// inicializar variáveis
		$this->errmsg            = '';
		$this->error             = false;
		$this->currentDimensions = array();
		$this->newDimensions     = array();
		$this->fileName          = $fileName;
		$this->imageMeta         = array();
		$this->percent           = 100;
		$this->maxWidth          = 0;
		$this->maxHeight         = 0;
		// verifique se o arquivo existe
		if (!file_exists($this->fileName)) {
			$this->errmsg = 'Arquivo não encontrado';
			$this->error  = true;
		} //verifique se o arquivo é legível
		elseif (!is_readable($this->fileName)) {
			$this->errmsg = 'O arquivo não é legível';
			$this->error  = true;
		}
		//inicializar os recursos se não houver erros
		if ($this->error == false) {
			if (function_exists('exif_imagetype')) {
				$case = exif_imagetype($this->fileName);
			} else {
				$image_size = getimagesize($this->fileName);
				if ($image_size != null) {
					$case = $image_size[2];
				} else {
					$case = 3;
				}
			}
			switch ($case) {
				case 1:
					$this->format   = "GIF";
					$this->oldImage = imagecreatefromgif($this->fileName);
					break;
				case 2:
					$this->format   = "JPG";
					$this->oldImage = @imagecreatefromjpeg($this->fileName);
					break;
				case 3:
					$this->format   = "PNG";
					$this->oldImage = imagecreatefrompng($this->fileName);
					break;
				default:
					$this->errmsg = 'formato de arquivo desconhecido';
					$this->error  = true;
					break;
			}
			$size                    = getimagesize($this->fileName);
			$this->currentDimensions = array(
				'width'  => $size[0],
				'height' => $size[1],
			);
			$this->newImage          = $this->oldImage;
			$this->gatherImageMeta();
		}
		if ($this->error == true) {
			$this->showErrorImage();
		}
	}

	/************************************
	 * destruidor de classe
	 *************************************/
	public function __destruct() {
		if (is_resource($this->newImage)) {
			@imagedestroy($this->newImage);
		}
		if (is_resource($this->oldImage)) {
			@imagedestroy($this->oldImage);
		}
		if (is_resource($this->workingImage)) {
			@imagedestroy($this->workingImage);
		}
	}

	/**************************************
	 * Retorna a largura atual da imagem
	 ***************************************/
	//@return int
	public function getCurrentWidth() {
		return $this->currentDimensions['width'];
	}

	/**************************************
	 * Retorna a altura atual da imagem
	 ************************************/
	//@return int
	public function getCurrentHeight() {
		return $this->currentDimensions['height'];
	}

	public function getCurrentFilesize() {
		clearstatcache();
		return (int) filesize($this->fileName);
	}

	/**************************************
	 * Calcula largura nova imagem
	 ************************************
	 *
	 * @param $width
	 * @param $height
	 *
	 * @return array
	 */
	//@param int $width
	//@param int $height
	//@return array
	private function calcWidth($width, $height) {
		$newWp     = (100 * $this->maxWidth) / $width;
		$newHeight = ($height * $newWp) / 100;
		return array(
			'newWidth'  => intval($this->maxWidth),
			'newHeight' => intval($newHeight),
		);
	}

	/**************************************
	 * Calcula altura nova imagem
	 ************************************
	 *
	 * @param $width
	 * @param $height
	 *
	 * @return array
	 */
	//@param int $width
	//@param int $height
	//@return array
	private function calcHeight($width, $height) {
		$newHp    = (100 * $this->maxHeight) / $height;
		$newWidth = ($width * $newHp) / 100;
		return array(
			'newWidth'  => intval($newWidth),
			'newHeight' => intval($this->maxHeight),
		);
	}

	/********************************************************
	 * Calcula tamanho nova imagem com base em percentual
	 *******************************************************
	 *
	 * @param $width
	 * @param $height
	 *
	 * @return array
	 */
	//@param int $width
	//@param int $height
	//@return array
	private function calcPercent($width, $height) {
		$newWidth  = ($width * $this->percent) / 100;
		$newHeight = ($height * $this->percent) / 100;
		return array(
			'newWidth'  => intval($newWidth),
			'newHeight' => intval($newHeight),
		);
	}

	/**************************************************************************************************************
	 * Calcula tamanho nova imagem com base na largura e altura, enquanto restringindo a maxWidth e maxHeight
	 ************************************************************************************************************
	 *
	 * @param $width
	 * @param $height
	 */
	//@param int $width
	//@param int $height
	private function calcImageSize($width, $height) {
		$newSize = array(
			'newWidth'  => $width,
			'newHeight' => $height,
		);
		if ($this->maxWidth > 0) {
			$newSize = $this->calcWidth($width, $height);
			if ($this->maxHeight > 0 && $newSize['newHeight'] > $this->maxHeight) {
				$newSize = $this->calcHeight($newSize['newWidth'], $newSize['newHeight']);
			}
			//$this->newDimensions = $newSize;
		}
		if ($this->maxHeight > 0) {
			$newSize = $this->calcHeight($width, $height);
			if ($this->maxWidth > 0 && $newSize['newWidth'] > $this->maxWidth) {
				$newSize = $this->calcWidth($newSize['newWidth'], $newSize['newHeight']);
			}
			//$this->newDimensions = $newSize;
		}
		$this->newDimensions = $newSize;
	}

	/***********************************************************************
	 * Calcula porcentagem novo tamanho de imagens baseado em
	 *********************************************************************
	 *
	 * @param $width
	 * @param $height
	 */
	//@param int $width
	//@param int $height
	private function calcImageSizePercent($width, $height) {
		if ($this->percent > 0) {
			$this->newDimensions = $this->calcPercent($width, $height);
		}
	}

	/**************************************
	 * Apresenta a imagem de erro
	 *************************************/
	private function showErrorImage() {
		header('Content-type: image/png');
		$errImg   = imagecreate(220, 25);
		$fgColor1 = imagecolorallocate($errImg, 255, 255, 255);
		$fgColor2 = imagecolorallocate($errImg, 255, 0, 0);
		imagestring($errImg, 3, 6, 6, 'Error:', $fgColor2);
		imagestring($errImg, 3, 55, 6, $this->errmsg, $fgColor1);
		imagepng($errImg);
		imagedestroy($errImg);
	}



	/***********************************************************
	 * Redimensiona imagem para maxWidth x maxHeight
	 **********************************************************
	 *
	 * @param int $maxWidth
	 * @param int $maxHeight
	 */
	//@param int $maxWidth
	//@param int $maxHeight
	public function resize($maxWidth = 0, $maxHeight = 0) {
		$this->maxWidth  = $maxWidth;
		$this->maxHeight = $maxHeight;
		$this->calcImageSize($this->currentDimensions['width'], $this->currentDimensions['height']);
		if (function_exists("ImageCreateTrueColor")) {
			$this->workingImage = imagecreatetruecolor($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
		} else {
			$this->workingImage = imagecreate($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
		}
		if ($this->format == "PNG") {
			imagealphablending($this->workingImage, false);
			imagesavealpha($this->workingImage, true);
			$transparent = imagecolorallocatealpha($this->workingImage, 255, 255, 255, 127);
			imagefilledrectangle($this->workingImage, 0, 0, $this->newDimensions['newWidth'], $this->newDimensions['newHeight'], $transparent);
		}
		imagecopyresampled($this->workingImage, $this->oldImage, 0, 0, 0, 0, $this->newDimensions['newWidth'], $this->newDimensions['newHeight'], $this->currentDimensions['width'], $this->currentDimensions['height']);
		$this->oldImage                    = $this->workingImage;
		$this->newImage                    = $this->workingImage;
		$this->currentDimensions['width']  = $this->newDimensions['newWidth'];
		$this->currentDimensions['height'] = $this->newDimensions['newHeight'];
	}

	/**************************************
	 *Redimensiona a imagem por Percentual
	 ************************************
	 *
	 * @param int $percent
	 */
	//@param int $percent
	public function resizePercent($percent = 0) {
		$this->percent = $percent;
		$this->calcImageSizePercent($this->currentDimensions['width'], $this->currentDimensions['height']);
		if (function_exists("ImageCreateTrueColor")) {
			$this->workingImage = imagecreatetruecolor($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
		} else {
			$this->workingImage = imagecreate($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
		}
		if ($this->format == "PNG") {
			imagealphablending($this->workingImage, false);
			imagesavealpha($this->workingImage, true);
			$transparent = imagecolorallocatealpha($this->workingImage, 255, 255, 255, 127);
			imagefilledrectangle($this->workingImage, 0, 0, $this->newDimensions['newWidth'], $this->newDimensions['newHeight'], $transparent);
		}
		imagecopyresampled($this->workingImage, $this->oldImage, 0, 0, 0, 0, $this->newDimensions['newWidth'], $this->newDimensions['newHeight'], $this->currentDimensions['width'], $this->currentDimensions['height']);
		$this->oldImage                    = $this->workingImage;
		$this->newImage                    = $this->workingImage;
		$this->currentDimensions['width']  = $this->newDimensions['newWidth'];
		$this->currentDimensions['height'] = $this->newDimensions['newHeight'];
	}

	/********************************************************************************
	 * Recorta a imagem do centro calculada em um quadrado de $cropSize pixels
	 *******************************************************************************
	 *
	 * @param $cropSize
	 */
	//@param int $cropSize
	public function cropFromCenter($cropSize) {
		if ($cropSize > $this->currentDimensions['width']) {
			$cropSize = $this->currentDimensions['width'];
		}
		if ($cropSize > $this->currentDimensions['height']) {
			$cropSize = $this->currentDimensions['height'];
		}
		$cropX = intval(($this->currentDimensions['width'] - $cropSize) / 2);
		$cropY = intval(($this->currentDimensions['height'] - $cropSize) / 2);
		if (function_exists("ImageCreateTrueColor")) {
			$this->workingImage = imagecreatetruecolor($cropSize, $cropSize);
		} else {
			$this->workingImage = imagecreate($cropSize, $cropSize);
		}
		if ($this->format == "PNG") {
			imagealphablending($this->workingImage, false);
			imagesavealpha($this->workingImage, true);
			$transparent = imagecolorallocatealpha($this->workingImage, 255, 255, 255, 127);
			imagefilledrectangle($this->workingImage, 0, 0, $this->newDimensions['newWidth'], $this->newDimensions['newHeight'], $transparent);
		}
		imagecopyresampled($this->workingImage, $this->oldImage, 0, 0, $cropX, $cropY, $cropSize, $cropSize, $cropSize, $cropSize);
		$this->oldImage                    = $this->workingImage;
		$this->newImage                    = $this->workingImage;
		$this->currentDimensions['width']  = $cropSize;
		$this->currentDimensions['height'] = $cropSize;
	}

	/********************************************************************************************************************
	 * Recortando uma imagem usando $startx e $StartY como o canto superior esquerdo.
	 *******************************************************************************************************************
	 *
	 * @param $startX
	 * @param $startY
	 * @param $width
	 * @param $height
	 */
	//@param int $startX
	//@param int $startY
	//@param int $width
	//@param int $height
	public function crop($startX, $startY, $width, $height) {
		if ($width > $this->currentDimensions['width']) {
			$width = $this->currentDimensions['width'];
		}
		if ($height > $this->currentDimensions['height']) {
			$height = $this->currentDimensions['height'];
		}
		if (($startX + $width) > $this->currentDimensions['width']) {
			$startX = ($this->currentDimensions['width'] - $width);
		}
		if (($startY + $height) > $this->currentDimensions['height']) {
			$startY = ($this->currentDimensions['height'] - $height);
		}
		if ($startX < 0) {
			$startX = 0;
		}
		if ($startY < 0) {
			$startY = 0;
		}
		if (function_exists("ImageCreateTrueColor")) {
			$this->workingImage = imagecreatetruecolor($width, $height);
		} else {
			$this->workingImage = imagecreate($width, $height);
		}
		if ($this->format == "PNG") {
			imagealphablending($this->workingImage, false);
			imagesavealpha($this->workingImage, true);
			$transparent = imagecolorallocatealpha($this->workingImage, 255, 255, 255, 127);
			imagefilledrectangle($this->workingImage, 0, 0, $width, $height, $transparent);
		}
		@imagecopyresampled($this->workingImage, $this->oldImage, 0, 0, $startX, $startY, $width, $height, $width, $height);
		$this->oldImage                    = $this->workingImage;
		$this->newImage                    = $this->workingImage;
		$this->currentDimensions['width']  = $width;
		$this->currentDimensions['height'] = $height;
	}

	/*************************************************************************************************************************************
	 * Gera a imagem para a tela, ou salva  $nome, se fornecido. Qualidade de imagens JPEG pode ser controlada com a
	 * variável $qualidade
	 ************************************************************************************************************************************
	 *
	 * @param int    $quality
	 * @param string $name
	 */
	//@param int $quality
	//@param string $name
	public function show($quality = 100, $name = '') {
		switch ($this->format) {
			case 'GIF':
				if ($name != '') {
					imagegif($this->newImage, $name);
				} else {
					header('Content-type: image/gif');
					imagegif($this->newImage);
				}
				break;
			case 'JPG':
				if ($name != '') {
					imagejpeg($this->newImage, $name, $quality);
				} else {
					header('Content-type: image/jpeg');
					imagejpeg($this->newImage, '', $quality);
				}
				break;
			case 'PNG':
				if ($name != '') {
					imagepng($this->newImage, $name);
				} else {
					header('Content-type: image/png');
					imagepng($this->newImage);
				}
				break;
		}
	}

	/***********************************************************************************************************************
	 * Salva imagem como $ nome (pode incluir caminho do arquivo), com qualidade de # por cento se o arquivo é um jpeg
	 *********************************************************************************************************************
	 *
	 * @param     $name
	 * @param int $quality
	 */
	//@param string $name
	//@param int $quality
	public function save($name, $quality = 100) {
		$this->show($quality, $name);
	}

	/*********************************************************************************************************
	 * Cria estilo de reflexão sob imagem, adicionar uma borda a imagem principal (opcionalmente)
	 *******************************************************************************************************
	 *
	 * @param        $percent
	 * @param        $reflection
	 * @param        $white
	 * @param bool   $border
	 * @param string $borderColor
	 */
	//@param int $percent
	//@param int $reflection
	//@param int $white
	//@param bool $border
	//@param string $borderColor
	public function createReflection($percent, $reflection, $white, $border = true, $borderColor = '#a4a4a4') {
		$width              = $this->currentDimensions['width'];
		$height             = $this->currentDimensions['height'];
		$reflectionHeight   = intval($height * ($reflection / 100));
		$newHeight          = $height + $reflectionHeight;
		$reflectedPart      = $height * ($percent / 100);
		$this->workingImage = imagecreatetruecolor($width, $newHeight);
		imagealphablending($this->workingImage, true);
		$colorToPaint = imagecolorallocatealpha($this->workingImage, 255, 255, 255, 0);
		imagefilledrectangle($this->workingImage, 0, 0, $width, $newHeight, $colorToPaint);
		imagecopyresampled($this->workingImage, $this->newImage, 0, 0, 0, $reflectedPart, $width, $reflectionHeight, $width, ($height - $reflectedPart));
		$this->imageFlipVertical();
		imagecopy($this->workingImage, $this->newImage, 0, 0, 0, 0, $width, $height);
		imagealphablending($this->workingImage, true);
		for ($i = 0; $i < $reflectionHeight; $i ++) {
			$colorToPaint = imagecolorallocatealpha($this->workingImage, 255, 255, 255, ($i / $reflectionHeight * - 1 + 1) * $white);
			imagefilledrectangle($this->workingImage, 0, $height + $i, $width, $height + $i, $colorToPaint);
		}
		if ($border == true) {
			$rgb          = $this->hex2rgb($borderColor, false);
			$colorToPaint = imagecolorallocate($this->workingImage, $rgb[0], $rgb[1], $rgb[2]);
			imageline($this->workingImage, 0, 0, $width, 0, $colorToPaint); //top line
			imageline($this->workingImage, 0, $height, $width, $height, $colorToPaint); //bottom line
			imageline($this->workingImage, 0, 0, 0, $height, $colorToPaint); //left line
			imageline($this->workingImage, $width - 1, 0, $width - 1, $height, $colorToPaint); //right line
		}
		$this->oldImage                    = $this->workingImage;
		$this->newImage                    = $this->workingImage;
		$this->currentDimensions['width']  = $width;
		$this->currentDimensions['height'] = $newHeight;
	}

	/************************************************************
	 * Inverte imagem de trabalho, usada pela função reflexão
	 ***********************************************************/
	private function imageFlipVertical() {
		$x_i = imagesx($this->workingImage);
		$y_i = imagesy($this->workingImage);
		for ($x = 0; $x < $x_i; $x ++) {
			for ($y = 0; $y < $y_i; $y ++) {
				imagecopy($this->workingImage, $this->workingImage, $x, $y_i - $y - 1, $x, $y, 1, 1);
			}
		}
	}

	/**************************************************************************************************************
	 * Converte valor de cor hexadecimal em valores RGB e retorna como matriz / string
	 ************************************************************************************************************
	 *
	 * @param      $hex
	 * @param bool $asString
	 *
	 * @return array|string
	 */
	//@param string $hex
	//@param bool $asString
	//return array|string
	private function hex2rgb($hex, $asString = false) {
		if (0 === strpos($hex, '#')) {
			$hex = substr($hex, 1);
		} else if (0 === strpos($hex, '&H')) {
			$hex = substr($hex, 2);
		}
		// quebrar em hex 3-tupla
		$cutpoint = ceil(strlen($hex) / 2) - 1;
		$rgb      = explode(':', wordwrap($hex, $cutpoint, ':', $cutpoint), 3);
		//converter each tupla para decimal
		$rgb[0] = (isset($rgb[0]) ? hexdec($rgb[0]) : 0);
		$rgb[1] = (isset($rgb[1]) ? hexdec($rgb[1]) : 0);
		$rgb[2] = (isset($rgb[2]) ? hexdec($rgb[2]) : 0);
		return ($asString ? "{$rgb[0]} {$rgb[1]} {$rgb[2]}" : $rgb);
	}

	/******************************************************************************************************************************
	 * Lê metadados selecionados EXIF de imagens jpg e preenche $this->imageMeta com valores adequados, se for
	 * encontrado
	 ******************************************************************************************************************************/
	private function gatherImageMeta() {
		//apenas tentar recuperar informações se existe exif
		if (function_exists("exif_read_data") && ($this->format == 'JPG')) {
			$imageData = @exif_read_data($this->fileName);
			if (isset($imageData['Make'])) {
				$this->imageMeta['make'] = ucwords(strtolower($imageData['Make']));
			}
			if (isset($imageData['Model'])) {
				$this->imageMeta['model'] = $imageData['Model'];
			}
			if (isset($imageData['COMPUTED']['ApertureFNumber'])) {
				$this->imageMeta['aperture'] = $imageData['COMPUTED']['ApertureFNumber'];
				$this->imageMeta['aperture'] = str_replace('/', '', $this->imageMeta['aperture']);
			}
			if (isset($imageData['ExposureTime'])) {
				$exposure                    = explode('/', $imageData['ExposureTime']);
				$exposure                    = round($exposure[1] / $exposure[0], - 1);
				$this->imageMeta['exposure'] = '1/' . $exposure . ' second';
			}
			if (isset($imageData['Flash'])) {
				if ($imageData['Flash'] > 0) {
					$this->imageMeta['flash'] = 'Yes';
				} else {
					$this->imageMeta['flash'] = 'No';
				}
			}
			if (isset($imageData['FocalLength'])) {
				$focus                          = explode('/', $imageData['FocalLength']);
				$this->imageMeta['focalLength'] = round($focus[0] / $focus[1], 2) . ' mm';
			}
			if (isset($imageData['DateTime'])) {
				$date                         = $imageData['DateTime'];
				$date                         = explode(' ', $date);
				$date                         = str_replace(':', '-', $date[0]) . ' ' . $date[1];
				$this->imageMeta['dateTaken'] = date('m/d/Y g:i A', strtotime($date));
			}
		}
	}

	/**************************************************************************
	 * Gira a imagem ou 90 graus no sentido horário ou anti-horário
	 ************************************************************************
	 *
	 * @param string $direction
	 */
	//@param string $direction
	public function rotateImage($direction = 'CW') {
		if ($direction == 'CW') {
			$this->workingImage = imagerotate($this->workingImage, - 90, 0);
		} else {
			$this->workingImage = imagerotate($this->workingImage, 90, 0);
		}
		$newWidth                          = $this->currentDimensions['height'];
		$newHeight                         = $this->currentDimensions['width'];
		$this->oldImage                    = $this->workingImage;
		$this->newImage                    = $this->workingImage;
		$this->currentDimensions['width']  = $newWidth;
		$this->currentDimensions['height'] = $newHeight;
	}
}
