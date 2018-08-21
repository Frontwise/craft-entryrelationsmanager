<?php

namespace frontwise\entryrelationsmanager\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class EntryRelationsManagerAssets
 */
class EntryRelationsManagerAssets extends AssetBundle
{

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->sourcePath = '@frontwise/entryrelationsmanager/resources';
        $this->depends = [CpAsset::class];

        $this->css = [
            'css/entryrelationsmanager.css',
        ];

        $this->js = [
            'js/entryrelationsmanager.js',
        ];
    }

}