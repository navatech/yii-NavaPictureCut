<?php

/**
 * Created by PhpStorm.
 * User: notte
 * Date: 09/10/2015
 * Time: 1:09 SA
 */
Yii::import('ext.NavaPictureCut.class.PictureCutClass');

class PictureCut extends CWidget
{
    public $name;
    public $crop = true;
    public $ratio = 4 / 3;
    public $button = null;
    public $icon = 'icon_add_image.png';
    public $uploadUrl = 'upload.php';
    public $cropUrl = 'crop.php';

    private $pictureCutJs = 'jquery.picture.cut.js';
    private $bootstrapJs = 'bootstrap.min.js';
    private $bootstrapCss = 'bootstrap.min.css';

    public function init()
    {
        if ($this->button == null) {
            $this->button = array(
                'border' => '1px #CCC dashed',
                'width' => 140,
                'height' => 100
            );
        }
        $ran = rand();
        echo '<div class="picture-cut" id="nava-picture-cut-' . $ran . '"></div>';
        $assets = dirname(__FILE__) . '/assets';
        $baseUrl = Yii::app()->assetManager->publish($assets);
        $css = '.upload.picture-element-principal.hover{border: 1px dashed #ffa200 !important;}';
        $js = '
            $("#nava-picture-cut-' . $ran . '").PictureCut({
                ActionToSubmitUpload: "' . $this->uploadUrl . '",
                ActionToSubmitCrop: "' . $this->cropUrl . '",
                DefaultImageButton: "' . $baseUrl . '/' . $this->icon . '",
                InputOfImageDirectoryAttr: {name: "' . $this->name . '"},
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
        $cs->registerCssFile($baseUrl . '/' . $this->bootstrapCss);
        $cs->registerScriptFile($baseUrl . '/' . $this->bootstrapJs, CClientScript::POS_END);
        $cs->registerScriptFile($baseUrl . '/' . $this->pictureCutJs, CClientScript::POS_HEAD);
        $cs->registerCss('pictureCut', $css);
        $cs->registerCoreScript('jquery');
        $cs->registerCoreScript('jquery.ui');
    }
}