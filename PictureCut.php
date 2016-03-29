<?php

/**
 * Created by PhpStorm.
 * User: notte
 * Date: 09/10/2015
 * Time: 1:09 SA
 */
Yii::import('ext.NavaPictureCut.class.PictureCutClass');

class PictureCut extends CWidget {
	public $name;
	public $id        = null;
	public $crop      = true;
	public $ratio     = 1.4;
	public $button    = null;
	public $icon      = 'icon_add_image.png';
	public $uploadUrl = null;
	public $cropUrl   = null;
	public $model     = null;
	public $attr      = null;
	public $value     = null;

	private $pictureCutJs  = 'picture.cut.js';
	private $pictureCutCss = 'picture.cut.css';

	public function init() {
		if($this->id == null) {
			$this->id = 'image';
		}
		if($this->button == null) {
			$this->button = array(
				'border' => '1px #CCC dashed',
				'width'  => 140,
				'height' => 100
			);
		} else {
			$this->button = CMap::mergeArray(array(
				'border' => '1px #CCC dashed',
				'width'  => 140,
				'height' => 100
			), $this->button);
		}
		$inputAttr = array('name' => $this->name);
		if($this->model != null) {
			$inputAttr['data-model'] = $this->model;
		}
		if($this->attr != null) {
			$inputAttr['data-attr'] = $this->attr;
		}
		if($this->value != null && !strpos($this->value, 'no-image')) {
			$inputAttr['value'] = $this->value;
		}
		if($this->uploadUrl == null) {
			$this->uploadUrl = Yii::app()->createUrl('site/upload');
		}
		if($this->cropUrl == null) {
			$this->cropUrl = Yii::app()->createUrl('site/crop');
		}
		$ran = rand();
		echo '<div class="picture-cut" id="nava-picture-cut-' . $ran . '"></div>';
		$assets  = dirname(__FILE__) . '/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);
		$js      = '
            $("#nava-picture-cut-' . $ran . '").PictureCut({
                ActionToSubmitUpload: "' . $this->uploadUrl . '",
                ActionToSubmitCrop: "' . $this->cropUrl . '",
                DefaultImageButton: "' . $baseUrl . '/' . $this->icon . '",
                InputOfImageDirectory: "' . $this->id . '",
                InputOfImageDirectoryAttr: ' . CJSON::encode($inputAttr) . ',
                PluginFolderOnServer: "' . Yii::app()->baseUrl . '/",
                AssetsFolderOnServer: "' . $baseUrl . '/",
                FolderOnServer: "' . Yii::app()->baseUrl . '/uploads/tmp/",
                EnableCrop: ' . ($this->crop ? 'true' : 'false') . ',
                CropWindowStyle: "Bootstrap",
                Title: "Sửa ảnh",
                ImageButtonCSS: ' . CJSON::encode($this->button) . ',
                Ratio: ' . $this->ratio . '
            });
    ';
		echo '<script type="text/javascript">' . $js . '</script>';
		$cs = Yii::app()->getClientScript();
		$cs->registerCssFile($baseUrl . '/' . $this->pictureCutCss);
		$cs->registerScriptFile($baseUrl . '/' . $this->pictureCutJs, CClientScript::POS_HEAD);
		$cs->registerCoreScript('jquery');
		$cs->registerCoreScript('jquery.ui');
	}
}
