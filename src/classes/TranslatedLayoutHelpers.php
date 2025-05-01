<?php

use \Kirby\Toolkit\V;

// A set of helpers for handling translations


// Helper for retrieving blueprint info from a Content or Cms object
function getFieldBlueprintSelf(\Kirby\Content\Field $field, bool $returnParsed = false) : ?array {

    if(!$field->exists() ) throw new InvalidArgumentException('fieldBlueprint() only works on existing fields !');
    $key = $field->key();
    if(!$key || $key==='title' ) throw new InvalidArgumentException('Sorry, Kirby provides no FormField nor FieldBlueprint for the title !'); // Todo: provide a fallback ?

    $page = $field->model();
    if($page instanceof \Kirby\Cms\Page === true || $page instanceof \Kirby\Cms\Site === true){
        return getFieldBlueprint($page, $key, $returnParsed);
    }
    
    // Should this rather return null ?
    throw new InvalidArgumentException("The provided field has no valid model !");
    return null;
};

// Helper for retrieving blueprint info from a Content or Cms object
function getFieldBlueprint(\Kirby\Cms\Page | \Kirby\Cms\Site $page, string $key, bool $returnParsed = false) : ?array {
    $pageBlueprint = $page->blueprint();
    $fieldBlueprint = $pageBlueprint->field($key);
    // SiteBlueprint has no title field... try calling the method directly ?
    if(!$fieldBlueprint) $fieldBlueprint = $pageBlueprint->{$key}();

    if(!$fieldBlueprint || !is_array($fieldBlueprint) ) throw new InvalidArgumentException('Weirdly, the field "'.$key.'" doesn\'t exist in the blueprint !');
    
    if($returnParsed) return \Kirby\Cms\Blueprint::fieldProps($fieldBlueprint??[]);
    return $fieldBlueprint??null;
};

// Syncs 2 data structures, returning fully translated data
// Mainly for frontend usage
function syncLanguages(array $defaultLangLayouts, array $translationData, \Kirby\Cms\Fieldsets $fieldsets, \Kirby\Cms\Fieldset|null $attrsFieldset/*, ?\Kirby\Content\Field $field=null*/) : array {
    // Got valid translation data ?
    if(
        // $translationData && is_array($translationData) && // Got data ?
        array_key_exists(TranslatedLayoutField::BLOCKS_KEY, $translationData) && array_key_exists(TranslatedLayoutField::LAYOUTS_KEY, $translationData) || // Got expected columns ?
        !is_array($translationData[TranslatedLayoutField::BLOCKS_KEY]) && !is_array($translationData[TranslatedLayoutField::LAYOUTS_KEY]) // Are they arrays ?
    ){
        // Inject translations

        // Loop the default language's structure and let translation content replace it
        foreach ($defaultLangLayouts as $layoutIndex => &$layout) { // <-- Apply blockstovalues
            $layoutID = $layout['id']??$layoutIndex;

            // Check the layout settings / attrs
            if ( $attrsFieldset !== null && array_key_exists($layoutID, $translationData['layouts']) && array_key_exists('attrs', $translationData['layouts'][$layoutID]) ) { // 

                // Check for translations
                $attrFields = $attrsFieldset->fields();
                if( count($attrFields) > 0 ){

                    // Loop default attrs by field
                    foreach($attrFields as $fieldName => $attrField){
                        // Translate if needed
                        if(
                            array_key_exists('translate', $attrField) && $attrField['translate'] === true // the field translates
                            && isset($translationData[TranslatedLayoutField::LAYOUTS_KEY][$layoutID]['attrs'][$fieldName]) // The translation exists
                            && !V::empty($translationData[TranslatedLayoutField::LAYOUTS_KEY][$layoutID]['attrs'][$fieldName])// The translation is not empty
                        ){
                            $layout['attrs'][$fieldName] = $translationData[TranslatedLayoutField::LAYOUTS_KEY][$layoutID]['attrs'][$fieldName];
                            // Todo : What if translation is empty ?
                            // !V::empty($layouts[$layoutIndex]['attrs'][$attrIndex])

                            // Todo: also handle nested fields translation ?
                        }
                    }
                }
            }

            // Translate columns & contents
            foreach($layout['columns'] as $columnIndex => &$column) {
                $columnID = $column['id']??$columnIndex;

                // Loop blocks and restrict them to the default language
                foreach( $column['blocks'] as $blockIndex => &$block){
                    $blockID = $block['id']??$blockIndex;
                    $blockType = $block['type'];
                    // Note: If code breaks: Useful inspiration for syncing translations --> ModelWithContent.php [in function content()] :

                    // Ignore unknown blocks (leave untranslated)
                    if(!array_key_exists($blockType, $fieldsets->data())){
                        continue;
                    }

                    $translateByDefault = false; // todo: parse this from a plugin option ?

                    // Get blueprint block attributes (its translation config)
                    if ($blockBlueprint = $fieldsets->find($blockType)) {
                        // Don't translate the whole block !
                        if(!$blockBlueprint->translate()){
                            continue;
                        }

                        $blockFields = $blockBlueprint->fields();
                        foreach($blockFields as $fieldKey => &$field){

                            $doTranslateThis = $translateByDefault;
                            if(array_key_exists('translate', $field)){
                                $doTranslateThis = $field['translate']===true;
                            }
                            // Need to translate ?
                            if($doTranslateThis){
                                // Translation available ?
                                if(
                                    array_key_exists($blockID, $translationData[TranslatedLayoutField::BLOCKS_KEY]) &&
                                    array_key_exists($fieldKey, $translationData[TranslatedLayoutField::BLOCKS_KEY][$blockID]['content']) &&
                                    !V::empty($translationData[TranslatedLayoutField::BLOCKS_KEY][$blockID]['content'][$fieldKey]) // The translation is not empty
                                ){
                                    // Replace the default lang block content with the translated one.
                                    $block['content'][$fieldKey] = $translationData[TranslatedLayoutField::BLOCKS_KEY][$blockID]['content'][$fieldKey];

                                    // Todo : Handle nested fields in a field ? ? ? (structure, etc...)
                                }
                                else {
                                    // No translation available, keep default lang as fallback
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Return the default or translated content.
    return $defaultLangLayouts;
}

?>