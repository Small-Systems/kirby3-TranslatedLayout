<?php
use \Kirby\Data\Data;
use Kirby\Cms\Fieldsets;
use Kirby\Cms\Fieldset;

require_once(__DIR__ . '/src/classes/TranslatedLayoutHelpers.php');
require_once(__DIR__ . '/src/classes/TranslatedLayoutField.php');
require_once(__DIR__ . '/src/classes/TranslatedBlocksField.php');

Kirby::plugin(

    // Kirby plugin info (visible in panel)
    name: 'daandelange/translatedlayout',
    info: [
        'license' => 'MIT'
    ],
    version: '1.0.4',
    extends: [

    'fields' => [
        // Undocumented, but identical to kirby's blocks and layout registering
        // Strings are checked against classes then instanciated.
        // See: https://github.com/getkirby/kirby/issues/3961
        'translatedlayout' => 'TranslatedLayoutField',
        'translatedblocks' => 'TranslatedBlocksField',
    ],
    'fieldMethods' => [

        // Content field method to retrieve the content as toLayouts
        'toTranslatedLayouts' => function(\Kirby\Content\Field $field) : \Kirby\Cms\Layouts {
            // Native behaviour
            if(
                // Single lang has normal behaviour
                ( $field->model()->kirby()->multilang() === false ) ||
                // Default lang has normal behaviour.
                ( $field->model()->translation()->language()->isDefault() )
            ){
                return $field->toLayouts();
            }
            // Translation behaviour
            $returnLayouts = [];//null;

            // Grab primary language content
            $defaultLangCode = $field->model()->kirby()->defaultLanguage()->code();
            $defaultLangTranslation = $field->model()->translation($defaultLangCode);

            // Check content
            if( !$defaultLangTranslation || !$defaultLangTranslation->version()->exists() ){
                $returnLayouts = [];
            }
            // When the field doesn't exist in default lang : exit early
            else if(!array_key_exists($field->key(), $defaultLangTranslation->content())){
                $returnLayouts = [];
            }

            // Grab translation data
            $translationValue = Kirby\Data\Data::decode($field->value(), type: 'json', fail: false);

            // Continue with translation data ?
            if(!$returnLayouts){

                // Fetch primary lang data
                $defaultLangValue = \Kirby\Data\Data::decode($defaultLangTranslation->content()[$field->key()], type: 'json', fail: false)??[];

                // Start with primary language content (fallback return)
                $returnLayoutsObj = \Kirby\Cms\Layouts::factory($defaultLangValue, ['parent' => $field->parent(), 'field'=>$field]);
                $returnLayouts = $returnLayoutsObj->toArray();

                // Check translation data
                if(
                    $translationValue && is_array($translationValue) && // Got data ?
                    array_key_exists(TranslatedLayoutField::BLOCKS_KEY, $translationValue) && array_key_exists(TranslatedLayoutField::LAYOUTS_KEY, $translationValue) && // Got expected columns ?
                    is_array($translationValue[TranslatedLayoutField::BLOCKS_KEY]) && is_array($translationValue[TranslatedLayoutField::LAYOUTS_KEY]) // Are they arrays ?
                ){
                    // Inject translation data
                    $bp = getFieldBlueprintSelf($field, false); // <-- unparsed !!!
                    
                    // Parse fieldsets to know translation config (from user blueprint or field defaults)
                    $bpFieldsets = array_key_exists('fieldsets', $bp)?$bp['fieldsets']:null; // Note: null triggers default fieldset
                    $fieldsets = Fieldsets::factory($bpFieldsets, [
                        'parent' => $field->parent(),
                        'field' => $field,
                    ]);

                    // Like Kirby\Form\Layout::setSettings();
                    // $settings = $column['attrs'];
                    $attrsFieldSet = null;
                    if(array_key_exists('settings', $bp) && array_key_exists('fields', $bp['settings'])){
                        $settings = $bp['settings'];//['fields'];
                        $settings['type']   = 'layout';
                        $settings['parent'] = $field->parent();
                        $attrsFieldSet = Fieldset::factory($settings);
                    }
                    
                    // Convert index keys to id keys (removed by blocksToValues() on save)
                    $translationValue[TranslatedLayoutField::BLOCKS_KEY] = array_column($translationValue[TranslatedLayoutField::BLOCKS_KEY], null, 'id');
                    $translationValue[TranslatedLayoutField::LAYOUTS_KEY] = array_column($translationValue[TranslatedLayoutField::LAYOUTS_KEY], null, 'id');
                    
                    //$bp = getFieldBlueprint();
                    $returnLayouts = syncLanguages($returnLayouts, $translationValue, $fieldsets, $attrsFieldSet);
                }
                else {
                    // Ignore: incorrect translation data
                }
            }

            return \Kirby\Cms\Layouts::factory($returnLayouts, ['parent' => $field->parent(), 'field'=> $field]);
        },

    ],
    'blueprints' => [

        // Todo: Possible issue = when these blocks are not registered in the user blueprint, they get added. NVM they are just defaults.
        'fields/translatedlayoutwithfieldsetsbis' => __DIR__ . '/src/blueprints/fields/translatedlayoutwithfieldsets.yml',
        'fields/translatedlayoutwithfieldsets' => function ($kirby) { // Todo: rename this to translatedlayoutwithfieldsettranslations
            // Put all static definitions in an yml file so it's easier to copy/paste/write.
            // From Kirby/Cms/Blueprint.php in function find()

            // Query existing blocks
            $blockBlueprints = $kirby->blueprints('blocks');

            return array_merge(

                // Load static properties from file
                Data::read( __DIR__ . '/src/blueprints/fields/translatedlayoutwithfieldsets.yml' ),

                // Dynamically inject non-default blocks depending on installed addons
                // Todo: add more translation settings for community blocks

                // Inject support for some block plugins
                // Feel free to add the structure of your addon and submit a PR
                (in_array('woo/localvideo', $blockBlueprints) ? [
                    'translate' => false,
                    'tabs'  => [
                        'source' => [
                            'fields' => [
                                'vidfile' => [
                                    'translate' => false,
                                ],
                                'vidposter' => [
                                    'translate' => false,
                                ],
                            ],
                        ],
                        'settings' => [
                            'fields' => [
                                'class' => [
                                    'translate' => false,
                                ],
                                'controls' => [
                                    'translate' => false,
                                ],
                                'mute' => [
                                    'translate' => false,
                                ],
                                'autoplay' => [
                                    'translate' => false,
                                ],
                                'loop' => [
                                    'translate' => false,
                                ],
                                'playsinline' => [
                                    'translate' => false,
                                ],
                                'preload' => [
                                    'translate' => false,
                                ],
                            ],
                        ],
                    ]
                ] : [])
            );
        }
    ],
    ] // end: extends array
);
