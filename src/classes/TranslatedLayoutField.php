<?php

// Idea: make blocks syncing optional ? (some block have no translations?)

use \Kirby\Form\Field\LayoutField;
use \Kirby\Cms\Blueprint;
use \Kirby\Cms\Fieldset;
use \Kirby\Cms\Layouts;
use \Kirby\Exception\LogicException;

//use \Kirby\Cms\ModelWithContent;
//require_once( __DIR__ . '/TranslatedLayoutFieldContent.php');

// Todo:
// - Add options to configure the behaviour of the field :
//      - Provide a toLayouts() with and without sanitation ? (optimizes: prevent loading default lang in translations by trusting the translation content file )
//      - Provide an option not to save non-translateable duplicate content in the content file.
//      - 
// - Facilitate blueprint setup by providing a way to automatically inject `translate: true|false` to blocks and their fieldset fields.
// - Check if all fieldsets are modified. (There are fieldset api routes also)
// - Ensure that `fill()` called on panel.save throws are notified to user instead of erasing the translation without any notice.
// - Miscellaneous improvements :
//      - Double check error handling behaviour
//      - Performance checks
//      - Test suite

require_once( __DIR__ . '/TranslatedBlockTraits.php');

// Class for extending the default layout field to have translateable content with layout structure sync
class TranslatedLayoutField extends LayoutField {
    use TranslatedBlocksTraits;

    public function __construct(array $params = []){
        parent::__construct($params);

        // Invert default translate value ?
        //$this->setTranslate(false);//$params['translate'] ?? false);
    }

    const string LAYOUTS_KEY = 'layouts';
    const string BLOCKS_KEY = 'blocks';
    const array EMPTY_VALUE = [
        'layouts'   => [],
        'blocks'    => [],
    ];

    // Extend layout mixin (todo: still needed ??)
    // 'extends' => 'layout', // No works...
    public function extends(){
        return 'layout';
    }

    /**
	 * Returns the field type
	 *
	 * @return string
	 */
	public function type(): string {
        // Needs uppercase, see FieldClass.php::type() --> classname is automatically converted from class otherwise, which grabs the wrong component in the panel
		return 'translatedlayout';
	}

    // public function props() : array { // from/in-sync-with the blueprint
    //     return array_merge(parent::props(), [
	// 		//'empty'          => $this->empty(),
    //         //'translate' => false,
    //         //'disabled' => true, // Disabled state for the layouts field, disables adding/removing layouts. BUT disables all contained blocks too. Needs to be modified within the field component.
    //         //'type' => 'translatedlayout', // dunno why, php sets it to translatedLayout....
	// 	]);
    // }

    // // Replaces numbered indexes by a string from item[$key].
    // public static function indexesToKeys(array $array, string $key='id'): array {
    //     $ret = [];
    //     foreach ($array as $layoutKey => $layoutValue) {
    //         $layoutKey = $layoutValue['id']??$layoutKey;
    //         $ret[$layoutKey]=$layoutValue;

    //         if(array_key_exists('columns', $ret[$layoutKey])){
    //             foreach ($ret[$layoutKey]['columns'] as $columnKey => $columnValue) {
    //                 unset($ret[$layoutKey]['columns'][$columnKey]);
    //                 $columnKey = $columnValue['id']??$columnKey;
    //                 $ret[$layoutKey]['columns'][$columnKey]=$columnValue;

    //                 if(array_key_exists('blocks', $ret[$layoutKey]['columns'][$columnKey])){
    //                     foreach ($ret[$layoutKey]['columns'][$columnKey]['blocks'] as $blockKey => $blockValue) {
    //                         unset($ret[$layoutKey]['columns'][$columnKey]['blocks'][$blockKey]);
    //                         $blockKey = $blockValue['id']??$blockKey;
    //                         $ret[$layoutKey]['columns'][$columnKey]['blocks'][$blockKey]=$blockValue;
    //                     }
    //                 }
    //             }
    //         }
    //     }
    //     return $ret;
    // }

    // // Replaces named keys to numbered indexes.
    // public static function keysToIndexes(array $array, string $key='id'): array {

    //     foreach ($array as $layoutKey => $layoutValue) {
    //         //$array[$layoutKey][$key]=$layoutKey; // Sync key with id
    //         if(array_key_exists('columns', $array[$layoutKey])){
    //             foreach ($array[$layoutKey]['columns'] as $columnKey => $columnValue) {
    //                 //$array[$layoutKey]['columns'][$columnKey][$key]=$columnKey; // Sync key with id
    //                 if(array_key_exists('blocks', $array[$layoutKey]['columns'][$columnKey])){
    //                     foreach ($array[$layoutKey]['columns'][$columnKey]['blocks'] as $blockKey => $blockValue) {
    //                         //$array[$layoutKey]['columns'][$columnKey]['blocks'][$blockKey][$key]=$blockKey; // Sync key with id
    //                     }
    //                     $array[$layoutKey]['columns'][$columnKey]['blocks'] = array_values($array[$layoutKey]['columns'][$columnKey]['blocks']); // remove blocks keys
    //                 }
    //             }
    //             $array[$layoutKey]['columns'] = array_values($array[$layoutKey]['columns']); // remove columns keys
    //         }
    //     }
    //     $array = array_values($array); // remove keys on level 1
    //     return $array;
    // }

    // k3 code, depreciated k5 !
    // public function store($value){ // Returns the (array) value to store (string). The value has been fill()ed already.
    //     return parent::store($value);
    // }

    // Flattens a layout. All blocks, columns and layouts are in their own array. 
    protected static function flattenLayoutsColumnsBlocks( Kirby\Cms\Layouts $layouts/*, array $columns = ['layouts','columns','blocks']*/ ) : array {
        $flatStructure = static::EMPTY_VALUE;
        if( !$layouts->isEmpty() ){
            foreach ($layouts->toArray() as $layoutIndex => $layout) {
                // We should have: $layout.id , $layout.columns , $layout.attrs
                if( isset($layout['columns']) ){
                    foreach($layout['columns'] as $columnIndex => $column) {
                        // We should have: $column.id , $column.blocks , $column.width
                        if( isset($column['blocks']) ){
                            foreach( $column['blocks'] as $blockIndex => $block){
                                // We should have: $block.id , $block.content , $block.type, $block.isHidden
                                $keyB = $block['id']??('block_'.$layoutIndex.'_'.$columnIndex.'_'.$blockIndex);
                                if(isset($flatStructure['blocks'][$keyB])) {
                                    // In default lang, generate new ID if numerical OR
                                    throw new LogicException("Ouch, now unique IDs can exist twice ! I can't handle this, please fix any duplicate ID in your content file. (duplicate block of type ".($block['type']??'Unknown')." with ID: ".$block['id'].").");
                                }

                                $flatStructure['blocks'][$keyB] = $block;
                            }
                            unset($column['blocks']);
                        }
                        // $keyC = $column['id']??$columnIndex;
                        // $flatStructure['columns'][$keyC] = $column;
                    }
                    unset($layout['columns']);
                }
                $keyL = $layout['id']??('layout_'.$layoutIndex);
                if(isset($flatStructure['blocks'][$keyL]))
                    throw new LogicException("Ouch, now unique IDs can exist twice ! I can't handle this.");
                $flatStructure['layouts'][$keyL] = $layout; // Note: Attrs are simply copied within
            }
        }
        return $flatStructure;
    }

    /**
     * Returns the value of the field in a format
     * to be stored by our storage classes
     */
    public function toStoredValue(bool $default = false): mixed
	{
        
        // Default lang uses native kirby code, which is faster & won't break. :)
        if(
            // Single lang has normal behaviour
            ( $this->kirby()->multilang() === false ) ||
            // Default lang has normal behaviour.
            ( $this->model()->translation()->language()->isDefault() ) ||
            // if attrs.translate is set to false
            ( $this->translate() === false )
        ){
            return parent::toStoredValue($default);
        }


        // Keep translations only.
        $value = $this->flattenLayoutsColumnsBlocks(Layouts::factory($this->toFormValue($default), ['parent' => $this->model]));

        // Original return
		return \Kirby\Data\Json::encode($value, pretty: $this->pretty());
	}

    /**
     * Returns the value of the field in a format to be used in forms
     * (e.g. used as data for Panel Vue components)
     */
    public function toFormValue(bool $default = false): mixed
	{
        // Default lang uses native kirby code, which is faster & won't break. :)
        if(
            // Single lang has normal behaviour
            ( $this->kirby()->multilang() === false ) ||
            // Default lang has normal behaviour.
            ( $this->model()->translation()->language()->isDefault() ) ||
            // if attrs.translate is set to false
            ( $this->translate() === false )
        ){
            return parent::toFormValue($default);
        }
		
        // Original below :
        if ($this->hasValue() === false) {
			return null;
		}

		if ($default === true && $this->isEmpty() === true) {
			return $this->default();
		}

		return $this->value;
	}
    // Value setter (used in construct, save, display, etc) // opposite of store() ? (also used before store  to recall js values)
    // Note : Panel.page.save passes an array while loadFromContent passes a yaml string.
    // Note: Only called from the panel, not in frontend !
    public function fill(mixed $value): static {

        // Default lang uses native kirby code, which is faster & won't break. :)
        if(
            // Single lang has normal behaviour
            ( $this->kirby()->multilang() === false ) ||
            // Default lang has normal behaviour.
            ( $this->model()->translation()->language()->isDefault() ) ||
            // if attrs.translate is set to false
            ( $this->translate() === false )
        ){
            return parent::fill($value);
        }

        // We got a translation !
        
        // Format incoming raw data to flattened blocks and layouts

        // Convert string to array
        // Ex: The value comes from the content file which only stores the translations
        // Ex: Or we got a default value from the blueprint 
        if( is_string($value) ){

            // Simply convert the string to array
            $value   = Kirby\Data\Data::decode($value, type: 'json', fail: false);
        }
        // The value is empty : Fill with empty data
        else if( is_null($value) ){
            $value = static::EMPTY_VALUE;
            // Exit early
            $this->value = $value;
            $this->errors = null;
            return $this;
        }

        // We got an array
        else if( is_array($value) ){

            // Is the array already formatted ?
            if(array_key_exists(static::BLOCKS_KEY, $value) && array_key_exists(static::LAYOUTS_KEY, $value)){
                // Secure
                // Fixme: Need to allow array AND null values ?
                if( !is_array($value[static::BLOCKS_KEY]) && !is_array($value[static::LAYOUTS_KEY]) ){
                    // Todo: on save, the value becomes null when throwing, which sets the stored value to null. The panel doesn't notify anything.
                    // Maybe: Rather die and respond with a panel error if  Or is there a way to handle this response natively ?
                    throw new LogicException('The layout field received an unfamiliar array format, throwing to ensure everything is OK.');
                }
                // Keep as is
                //$value = $value;
            }
            // We got another array format
            // Assume it's in full form-data format
            // Ex: when the panel sends us back a full layout that we have to parse, probably for saveing it
            else {
                // Secure
                if( !empty($value) && ( !isset($value[0]) || !isset($value[0]['columns']) || !isset($value[0]['id']) ) ){
                    // Todo: on save, the value becomes null when throwing, which sets the stored value to null. The panel doesn't notify anything.
                    // Maybe: Rather die and respond with a panel error if  Or is there a way to handle this response natively ?
                    throw new LogicException('The layout field received an unfamiliar array format, throwing to ensure everything is OK.');
                }

                // Keep flattened
                $value = $this->flattenLayoutsColumnsBlocks( Layouts::factory($value, ['parent' => $this->model]) );
            }
        }
        // Wrong data format
        else {
            // Todo: this could trigger when the save file is mistakenly hand-edited, or corrupted. Should this be a more gentle message ?
            throw new LogicException('Unrecognised translated layout value : Can\'t fill the field!');
            $this->errors[] = 'Unrecognised translated layout value : Can\'t fill the field!';
            //$this->value=...;
            return $this;
        }     
        
        
        // Check values ? (at this point w have an array of )
        if( !isset($value['layouts']) || !isset($value['blocks']) ){
            throw new LogicException('The parsed data looks wrong. Aborting.');
        }

        $flattenedLayouts = $value;

        // Todo : after some testing, the logic exceptions above and below could return the default language, just in case...

        // Check default lang for this model (should always exist anyways)
        $defaultLang = $this->kirby()->defaultLanguage()->code();
        $currentLang = $this->kirby()->language()->code();// $this->model()->translation()->code();// commented is more correct, but loads translation strings = useless here

        $defaultLangTranslation = $this->model()->translation($defaultLang);
        if( !$defaultLangTranslation || !$defaultLangTranslation->version()->exists() ){
            // Todo: rather return empty field !
            throw new LogicException('Multilanguage is enabled but there is no content for the default language... who\'s the wizzard ?!');
        }

        // Fetch default lang

        // When the field doesn't exist in default lang : exit early
        if(!array_key_exists($this->name(), $defaultLangTranslation->content())){
            $this->errors=null;
            $this->value=[];
            return $this;
        }

        $defaultLangValue = \Kirby\Data\Data::decode($defaultLangTranslation->content()[$this->name()], type: 'json', fail: false)??[];
        $defaultLangLayouts = Layouts::factory($defaultLangValue, ['parent' => $this->model])->toArray();
        // Start sanitizing / Syncing the structure

        // apply kirby functions
        if(true){ // Original sanitize functions (since k5)
            foreach ($defaultLangLayouts as $layoutIndex => $layout) {
                if ($this->settings !== null) {
                    $defaultLangLayouts[$layoutIndex]['attrs'] = $this->attrsForm($layout['attrs'])->values();
                }
    
                foreach ($layout['columns'] as $columnIndex => $column) {
                    $defaultLangLayouts[$layoutIndex]['columns'][$columnIndex]['blocks'] = $this->blocksToValues($column['blocks']);
                }
            }
        }
        // Loop the default language's structure and let translation content replace it
        foreach ($defaultLangLayouts as $layoutIndex => &$layout) { // <-- Apply blockstovalues
            $layoutID = $layout['id']??$layoutIndex;

            // Check the layout settings / attrs
            if ($this->settings !== null) { // 
                // Generate the corresponding form
                $attrForm = $this->attrsForm($layout['attrs']);

                // Load value from default lang
                $layout['attrs'] = $attrForm->values();

                // Check for translations
                $attrFields = $attrForm->fields();
                if( $attrFields->count() > 0 && array_key_exists($layoutID, $flattenedLayouts['layouts']) && array_key_exists('attrs', $flattenedLayouts['layouts'][$layoutID]) ){

                    // Loop default attrs by field
                    foreach($attrFields as $fieldName => $attrField){
                        // Translate if needed
                        if(
                            $attrField->translate() === true // the field translates
                            && array_key_exists($fieldName, $flattenedLayouts['layouts'][$layoutID]['attrs']) // The translation exists
                        ){
                            $layout['attrs'][$fieldName] = $flattenedLayouts['layouts'][$layoutID]['attrs'][$fieldName];
                            // Todo : What if translation is empty ?
                            // !V::empty($layouts[$layoutIndex]['attrs'][$attrIndex])

                            // Todo: also handle nested fields translation ?
                        }
                    }
                }
            }

            foreach($layout['columns'] as $columnIndex => &$column) {
                $columnID = $column['id']??$columnIndex;

                // Loop blocks and restrict them to the default language
                foreach( $column['blocks'] as $blockIndex => &$block){
                    $blockID = $block['id']??$blockIndex;
                    // Note: If code breaks: Useful inspiration for syncing translations --> ModelWithContent.php [in function content()] :

                    try {
                        $blockBlueprint    = $this->fieldset($block['type']);
                    } catch (Throwable $e) {
                        // skip invalid blocks
                        // checkme: skipping leaves default translation. Is this the desired behaviour ? (probably prevents saving the field too!?)
                        continue;
                    }

                    $translateByDefault = true; // todo: parse this from a plugin option ?

                    // Translateable and translation available ?
                    if(($blockBlueprint->translate() || $translateByDefault) && array_key_exists($blockID, $flattenedLayouts['blocks'])){
                        // Loop blueprint fields here (not defaultLanguage values) to enable translations not in the default lang
                        //foreach($defaultLangLayouts[$layoutIndex]['columns'][$columnIndex]['blocks'][$blockIndex]['content'] as $fieldName => $fieldData){
                        foreach($blockBlueprint->fields() as $fieldName => $fieldOptions){ // Todo: fields() can throw !
                            // Translate if field's translation is explicitly set or if the block is set to translate
                            $translateField = array_key_exists('translate', $fieldOptions) ? ($fieldOptions['translate'] === true) : ($translateByDefault && $blockBlueprint->translate());
                            if(
                                // Is the field translateable ?
                                $translateField

                                // Got keys in both contentTranslations ?
                                && array_key_exists($fieldName, $block['content'])
                                && array_key_exists($fieldName, $flattenedLayouts['blocks'][$blockID]['content'])
                                // todo: add empty condition on translation ? This brobably should take a blueprint option if translating empty values. Leaving translations empty can also be useful
                                //&& !V::empty($layouts[$layoutIndex]['columns'][$columnIndex]['blocks'][$blockIndex]['content'])
                            ){
                                //dump('Got a translation !='.$block['type'].'/'.$fieldName);
                                
                                // Replace the default lang block content with the translated one.
                                $block['content'][$fieldName]=$flattenedLayouts['blocks'][$blockID]['content'][$fieldName];
                            }
                            // Todo : Handle nested fields in a field ? ? ? (structure, etc...)

                            // Todo: the fields loop can be heavy to loop, maybe unset the field once used, to speed up the next iterations ?
                        }
                        // Alternative way, kirby's way, but needs to ensure that keys of the translation are not set, which requires modifying the values on save ideally, but also sanitization here. (todo)
                        //$defaultLangLayouts[$layoutIndex]['columns'][$columnIndex]['blocks'][$blockIndex]['content'] = array_merge($defaultLangLayouts[$layoutIndex]['columns'][$columnIndex]['blocks'][$blockIndex]['content'], $layouts[$layoutIndex]['columns'][$columnIndex]['blocks'][$blockIndex]['content']);

                        // Fallback when a block has no fields ? Are there blocks with content AND without fields ?
                    }

                }

                // Compute simplified blueprint to fully expanded options (like original Kirby fill() function)
                //$defaultLangLayouts[$layoutIndex]['columns'][$columnIndex]['blocks'] = $this->blocksToValues(array_merge($defaultLangLayouts[$layoutIndex]['columns'][$columnIndex]['blocks'], $layouts[$layoutIndex]['columns'][$columnIndex]['blocks']));
                $column['blocks'] = $this->blocksToValues($column['blocks']);

                // lazy-update/replace ? whole blocks part ? Too buggy in case items get add/removed; only works well when data is a perfect mirror. Also, array_combine tends to be quite slow.
                //$layouts[$layoutIndex]['columns'][$columnIndex]['blocks'] = array_combine($defaultLangLayouts[$layoutIndex]['columns'][$columnIndex]['blocks'], array_slice($layouts[$layoutIndex]['columns'][$columnIndex]['blocks']);
            }

        }

        // Reset keys
        //$defaultLangLayouts = static::keysToIndexes($defaultLangLayouts);

        // Remember value
        $this->value = $defaultLangLayouts;
        $this->errors = null;
        return $this;
    }

    // Override the layout settings blueprint, 
    protected function setSettings($settings = null) : void {
        // Changed : On default lang, use native kirby function, sure not to break.
        if(!$this->kirby()->multilang() || !$this->kirby()->language() || $this->kirby()->language()->isDefault()){
            parent::setSettings($settings);
            return;
        }
        
		if (empty($settings) === true) {
			$this->settings = null;
			return;
		}

		$settings = Blueprint::extend($settings);
		$settings['icon']   = 'dashboard';
		$settings['type']   = 'layout';
		$settings['parent'] = $this->model();

        // Lines below were added compared to native function
        $settings = $this->adaptFieldsetToTranslation($settings);
        //$settings['disabled'] = true;
        //$settings['editable'] = false; // Adding this line disables saving of attrs/settings ?

		$this->settings = Fieldset::factory($settings);
	}

    // Checkme: Need to disble any validations ?
    // public function validations(): array {
    //     return [];
    // }

    // Try to override these ModelWithContent methods
    //public function translation(string $languageCode = null) { return $this->parent->translation($languageCode); }
    //public function translations();

    // Check if this function is called ?
    // protected function i18n($param = null): ?string
    // {
    //     return empty($param) === false ? I18n::translate($param, $param) : null;
    // }
}
