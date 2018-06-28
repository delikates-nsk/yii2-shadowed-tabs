<?php
namespace delikatesnsk\shadowedtabs;

use yii\web\AssetBundle;

class ShadowedTabsAsset extends AssetBundle
{
    public $sourcePath = '@vendor/delikates-nsk/yii2-shadowed-tabs/assets';

    public $js = [
    ];

    public $css = [
        'css/tabs.css?v=1',
    ];
}