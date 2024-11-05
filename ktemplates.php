<?php
use Random\RandomException;

/**
 * kumo_random_string
 * @description This function generate random string
 *
 * @param int $length The random string length (default = 6)
 *
 * @return string $result The random string generated
 * @throws RandomException
 */
function kumo_random_string( int $length=6 ) : string {
    return bin2hex( random_bytes( intval( $length / 2 ) ) );
}

/**
 * kumo_parse_attributes
 * @description This function parse k-tags and their content
 *
 * @param string $tag The k-tag to parse
 * @param array $array_content The input content list
 *
 * @return array $array The result parsed content array
 */
function kumo_parse_attributes( string $tag, array $array_content ) : array {
    global $tags;
    $to_return = array();
    foreach( $array_content as $index => $content ) {
        preg_match_all( "/\[$tag.*?\]/m", $content, $matches_attributes );
        $matched = @$matches_attributes[ 0 ][ 0 ];
        $to_return[ "$index:$tag" ] = array(
            'base' => $matched,
            'tag' => $tag,
            'attributes' => array(),
            'closer' => 0
        );
        if( ! in_array( $tag, array_keys( $tags ) ) ) continue;
        foreach( $tags[ $tag ][ 'accept' ] as $accepted ) if( @kumo_str_contains( $matched, $accepted ) ) {
            $temp_value = null;
            preg_match_all( "/$accepted=\"(.*?)\"/m", $matched, $temp_value );
            if( count( $temp_value[ 1 ] ) === 0 ) $to_return[ "$index:$tag" ][ 'attributes' ][ $accepted ] = null;
            else $to_return[ "$index:$tag" ][ 'attributes' ][ $accepted ] = $temp_value[ 1 ][ 0 ];
        }
    }
    return $to_return;
}

/**
 * kumo_order_tags
 * @description Parse and order k-tags of the input content, and put them into an array
 *
 * @param string $content The input content
 * @param array &$array The array passed by reference
 *
 * @return void
 */
function kumo_order_tags( string $content, array &$array ) : void {
    preg_match( '/\[(.*?)\]/m', $content, $match );
    if( count( $match ) == 0 ) return;
    $tag = explode( ' ', str_replace( '/', '', $match[ 1 ] ) )[ 0 ];
    if( ! str_starts_with( $match[1], '/' ) ) $array[] = array_values( kumo_parse_attributes( $tag, array( $match[ 0 ] ) ) )[ 0 ];
    else $array[] = array(
        'base' => $match[ 0 ],
        'tag' => $tag,
        'closer' => 1
    );
    kumo_order_tags( str_replace( $match[ 0 ], '', $content ), $array );
    return;
}

/**
 * kumo_str_contains
 * @description This function apply str_contains function of PHP onto an array
 *
 * @param string $haystack The input haystack (see str_contains)
 * @param array $needles Array of input needle (see str_contains)
 *
 * @return bool $state Needles into haystack
 */
function kumo_str_contains( string $haystack, array $needles ) : bool {
    if( ! function_exists( 'str_contains' ) ) {
        if( ! is_array( $needles ) ) return strpos( $haystack, $needles ) !== false;
        foreach( $needles as $needle ) if( strpos( $haystack, $needle ) !== false ) return true;
    }
    if( ! is_array( $needles ) ) return str_contains( $haystack, $needles );
    foreach( $needles as $needle ) if( str_contains( $haystack, $needle ) ) return true;
    return false;
}

/**
 * kumo_generate_html
 * @description This function generate HTML code from the parsed AND ordered input content
 *
 * @param string $content The input content
 * @param array $ordered The parsed AND ordered array of tags
 *
 * @return string $result The HTML code of the parsed content
 */
function kumo_generate_html( string $content, array $ordered ) : string {
    global $attributes, $tags;
    foreach( $ordered as $content_ordered ) {
        if( ! in_array( $content_ordered[ 'tag' ], array_keys( $tags ) ) ) continue;
        $tag_content = $tags[ $content_ordered[ 'tag' ] ];
        foreach( $tag_content[ 'accept' ] as $index => $accepted ) {
            $tag_content[ 'accept' ][ $accepted ] = @$attributes[ $accepted ];
            unset( $tag_content[ 'accept' ][ $index ] );
        }
        $content_ordered = array_merge( $content_ordered, $tag_content );
        if( intval( $content_ordered[ 'closer' ] ) ) {
            $content = str_replace( $content_ordered[ 'base' ], "</" . $content_ordered[ 'tag' ] . ">", $content );
            continue;
        }
        $result = array( '<' . $content_ordered[ 'tag' ] );
        $set_attributes = array();
        $style_result = array();
        $real_attributes_result = array();
        $default_attributes_result = array();
        if( ! empty( $content_ordered[ 'attributes' ] ) ) foreach( $content_ordered[ 'attributes' ] as $attribute_name => $attribute ) {
            $real = @$content_ordered[ 'accept' ][ $attribute_name ];
            if( $real === null ) continue;
            if( ( $temp = @$real[ 'style' ] ) !== null ) {
                foreach( $real[ 'style' ] as $style_index => $style_content ) {
                    preg_match_all( '/%(\d+)/m', $style_content, $match );
                    if( count( $match[ 0 ] ) == 0 ) {
                        $style_result[] = $style_content;
                        continue;
                    }
                    if( $attribute !== null && @$match[ 1 ][ 0 ] == '@' ) array_push( $style_result, str_replace( $match[ 0 ][ 0 ], $attribute, $style_content ) );
                    else if( $attribute !== null ) $style_result[] = @str_replace( $match[ 0 ][ 0 ], explode( ' ', $attribute )[ intval( $match[ 1 ][ 0 ] ) ], $style_content );
                    else $style_result[] = str_replace( $match[ 0 ], @$real[ 'content' ][ $style_index ], $style_content );
                }
                if( ! in_array( 'style', $set_attributes ) ) $set_attributes[] = 'style';
            }
            if( ( $temp = @$real[ 'attributes' ] ) !== null ) {
                foreach( $real[ 'values' ] as $real_attribute_index => $real_attribute_content ) {
                    $real_tag_name = @$real[ 'attributes' ][ $real_attribute_index ];
                    if( ! $real_tag_name ) continue;
                    preg_match_all( '/%(\d+|@)/m', $real_attribute_content, $match );
                    if( count( $match[ 0 ] ) == 0 ) {
                        $real_attributes_result[] = "$real_tag_name=\"$real_attribute_content\"";
                        if( ! in_array( $real_tag_name, $set_attributes ) ) $set_attributes[] = $real_tag_name;
                        continue;
                    }
                    if( $attribute !== null && @$match[ 1 ][ 0 ] == '@' ) $real_attributes_result[] = "$real_tag_name=\"" . str_replace( $match[ 0 ][ 0 ], $attribute, $real_attribute_content ) . '"';
                    else if( $attribute !== null ) $real_attributes_result[] = "$real_tag_name=\"" . @str_replace( $match[ 0 ][ 0 ], @explode( ' ', $attribute )[ $match[ 1 ][ 0 ] ], $real_attribute_content ) . '"';
                    else $real_attributes_result[] = "$real_tag_name=\"" . @str_replace( $match[ 0 ], @$real[ 'content' ][ $real_attribute_index ], $real_attribute_content ) . '"';
                    if( ! in_array( $real_tag_name, $set_attributes ) ) $set_attributes[] = $real_tag_name;
                }
            }
        }
        if( count( $style_result ) !== 0 ) $result[] = ' style="' . implode( ' ', $style_result ) . '"';
        if( count( $real_attributes_result ) !== 0 ) $result[] = ' ' . implode( ' ', $real_attributes_result );
        if( ( $temp = @$content_ordered[ 'default' ] ) !== null ) foreach( $content_ordered[ 'default' ] as $default_attribute_index => $default_attribute ) {
            $real = @$content_ordered[ 'accept' ][ $default_attribute ];
            $temp_values = @$real[ 'values' ];
            if( ! $real || ! $temp_values || in_array( $default_attribute, $set_attributes ) || ( $temp = @$content_ordered[ 'accept' ][ $default_attribute ] ) === null ) continue;
            foreach( $temp_values as $default_index => $default_content ) {
                $real_tag_name = @$real[ 'attributes' ][ $default_index ];
                if( ! $real_tag_name ) continue;
                preg_match_all( '/%(\d+)/m', $default_content, $match );
                if( count( $match[ 0 ] ) == 0 ) {
                    $default_attributes_result[] = "$real_tag_name=\"$default_content\"";
                    if( ! in_array( $real_tag_name, $set_attributes ) ) $set_attributes[] = $real_tag_name;
                    continue;
                }
                if( ( $temp = @$content_ordered[ 'attributes' ][ $default_attribute ] ) !== null ) $default_attributes_result[] = "$real_tag_name=\"" . str_replace( $match[ 0 ], $temp, $default_content ) . '"';
                else $default_attributes_result[] = "$real_tag_name=\"" . str_replace( $match[ 0 ], @$real[ 'content' ][ $default_index ], $default_content ) . '"';
                if( ! in_array( $real_tag_name, $set_attributes ) ) $set_attributes[] = $real_tag_name;
            }
        }
        if( count( $default_attributes_result ) !== 0 ) foreach( $default_attributes_result as $attribute_result ) if( ! in_array( $attribute_result, $result ) ) $result[] = ' ' . $attribute_result;
        $result[] = '>';
        $content = str_replace( $content_ordered[ 'base' ], implode( '', $result ), $content );
    }
    return $content;
}

// Silence is golden
