<?php
/**
 * Build a WP-friendly ZIP for luwipress-gold/.
 * Uses forward slashes only (Linux unzip compatible).
 * Strips macOS noise files.
 */
$source_dir = __DIR__ . '/luwipress-gold';
$out_path   = 'C:/xampp/htdocs/luwipress/releases/luwipress-gold-1.0.0.zip';

if ( ! is_dir( $source_dir ) ) {
	fwrite( STDERR, "source missing: $source_dir\n" );
	exit( 1 );
}

@unlink( $out_path );

$zip = new ZipArchive();
$res = $zip->open( $out_path, ZipArchive::CREATE );
if ( $res !== TRUE ) {
	fwrite( STDERR, "open failed: $res\n" );
	exit( 1 );
}

$it = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
foreach ( $it as $file ) {
	$abs   = $file->getPathname();
	$rel   = substr( $abs, strlen( dirname( $source_dir ) ) + 1 );
	$rel   = str_replace( '\\', '/', $rel );

	// Skip macOS junk + thumbs.
	if ( preg_match( '#(\.DS_Store|__MACOSX|Thumbs\.db|desktop\.ini)$#', $rel ) ) continue;

	if ( $file->isDir() ) {
		$zip->addEmptyDir( $rel );
	} else {
		$zip->addFile( $abs, $rel );
		$count++;
	}
}

$zip->close();

if ( ! file_exists( $out_path ) ) {
	fwrite( STDERR, "zip not produced\n" );
	exit( 1 );
}

echo sprintf( "OK: %d files, %d bytes → %s\n", $count, filesize( $out_path ), $out_path );

// Verify style.css is at the top level inside luwipress-gold/.
$verify = new ZipArchive();
$verify->open( $out_path );
$has_style = false;
$first_paths = [];
for ( $i = 0; $i < min( $verify->numFiles, 30 ); $i++ ) {
	$name = $verify->getNameIndex( $i );
	$first_paths[] = $name;
	if ( $name === 'luwipress-gold/style.css' ) $has_style = true;
}
$verify->close();
echo $has_style ? "✓ style.css at correct path\n" : "✗ style.css MISSING\n";
echo "First 5 entries:\n";
foreach ( array_slice( $first_paths, 0, 5 ) as $p ) echo "  $p\n";
