<?php

use \Kirby\Cms\Blueprint;
use \Kirby\Cms\Fieldset;
use \Kirby\Cms\Fieldsets;

// Shared traits for TranslatedBlocksField and TranslatedLayoutField
trait TranslatedBlocksTraits {

    // Override fieldsets for translations. Fieldsets define block blueprints, which allow controlling their translation status.
    protected function setFieldsets(array|string|null $fieldsets, Kirby\Cms\ModelWithContent $model):void {

        // On default lang, use native kirby function, sure not to break.
        // Warning: kirby() accesses the model which can't before constructor !
        if(!$model->kirby()->multilang() || !$model->kirby()->language() || $model->kirby()->language()->isDefault()){
            parent::setFieldsets($fieldsets, $model);// added this line compared to native
            return;
        }

        if (is_string($fieldsets) === true) {
            $fieldsets = [];
        }

        $fieldsets = $this->adaptFieldsetsToTranslation($fieldsets);// added this line compared to native

        // Todo : if fieldsets is null,  factory() seems to set it to a default set, causing disabled not to be set correctly...
        $this->fieldsets = Fieldsets::factory($fieldsets, [
            'parent' => $model
        ]);
    }    

    // Adds translation statuses to all fields and modifies them according to blueprint.
    private static function adaptFieldsetsToTranslation(?array $fieldsets) : ?array {
        if($fieldsets) foreach($fieldsets as $key => &$fieldset){
            $fieldset = static::adaptFieldsetToTranslation($fieldset);

            // Todo: can it happen that groups contain more fieldsets ? They might need a dedicated if()...
        }
        return $fieldsets;
    }

    // Force-disables non-translateable fields
    private static function adaptFieldsetToTranslation(null|string|array $fieldset) : null|string|array {
        // Set translations ?
        // Already set via blueprint YML ? if using: "extends: translatedlayoutwithfields" ? Ensure to set defaults ?

        // Blueprint is not yet expanded here. Todo: What if the default should be translateable ?
        if(is_string($fieldset) === true){
            return $fieldset; // Leave as is, no translation logic for now !
        }

        // Set disabed ? Saveable ? if translate is false. So the field is disabled for editing in panel
        if($fieldset && isset($fieldset['translate']) && $fieldset['translate'] === false ){
            $fieldset['disabled']=true;
            //$fieldset['saveable']=false; // Assumes the field has no value ! Not possible
        }

        return $fieldset;
    }
}
