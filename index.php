<?php

require_once './ktemplates.php';

// The template to translate
$content = '[kstyle link-css="./style.css"][/kstyle][krow wh="100% 100%" flex flex-col][krow class="flexed" wh="100% 100%" bg="black" flex flex-col flex-center][ktitle]Bienvenue sur ce site de test ![/ktitle][/krow][krow class="flexed" flex flex-row flex-center wh="100% 100%"][krow class="card-container" flex flex-row flex-center wh="50% 100%"][kimg wh="auto 33%" bordered][/kimg][kpar padding="1rem"]This is a perfect test paragraph :)[/kpar][/krow][/krow][/krow]';

// The current configuration to use (can be fetched from API)
$configuration_content = json_decode( file_get_contents( 'configuration.json' ), true );

// Set global attributes and tags
// @TODO Upgrade this to a better method
global $tags, $attributes;
$tags = $configuration_content[ 'tags' ];
$attributes = $configuration_content[ 'attributes' ];

$result = array();
kumo_order_tags( $content, $result ); // Reorder tags from input content to get the list of tag to generate in right order
echo kumo_generate_html( $content, $result ); // Generate HTML from ordered list of parsed tags
