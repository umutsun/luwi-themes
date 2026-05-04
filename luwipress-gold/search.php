<?php
/**
 * Search results fallback — defers to index.php which handles search via
 * is_search().
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require LUWIPRESS_GOLD_DIR . '/index.php';
