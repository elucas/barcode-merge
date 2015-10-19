<?php

/** Load the source csv
 * then match against the specified list of scanned attendees
 * Echo out the result
 */
$shortopts = '';
$longopts = array(
    'source:',
    'sourcecol:',
    'barcodes:',
    'barcodecol:',
    'prefix:',
    'debug'
);

$options = getopt($shortopts,$longopts);

//var_dump($options);

function showUsageAndDie($msg=null) {
    if ($msg) {
	echo "Message: $msg\n";
    }
    echo "merge.php
USAGE
php merge.php --source=path/to/source.csv --barcodes=path/to/barcode.csv [--sourcecol=N] [--barcodecol=N] [--prefix=nn] [--debug]
  --sourcecol=<int> Default=0 : zero-indexed integer. Take the unique ids from this column number
  --barcodecol=<int> Default=1  : zero-indexed integer. Match the unique ids against this column number
  
";
    die();
}

if (count($options) < 3) { showUsageAndDie(); }

$file_source  = @$options['source'];
$file_barcodes  = @$options['barcodes'];

$source_col = @$options['sourcecol'] ? $options['sourcecol'] : 0;
$barcode_col = @$options['barcodecol'] ? $options['barcodecol'] : 1;

$prefix  = @$options['prefix'];

$debug = false;
if (isset($options['debug'])) { $debug = true; }

if (!is_readable($file_source)) { die("Cannot read source file: ".$file_source."\n"); }
if (!is_readable($file_barcodes)) { die("Cannot read barcode file: ".$file_barcodes."\n"); }


$source_raw = array_map('str_getcsv', file($file_source));
$source_indexed = array();

// Discard the first row, because it should be column headings
$firstrow = array_shift($source_raw);
    
// Build the db of source rows
foreach ($source_raw as $row) {
    // Barcode is the first column
    $unique_id = $row[$source_col];
    
    $source_indexed[$unique_id] = $row;
    if ($debug) { echo "\nUniqueID: $unique_id"; }
}

if ($debug) { echo "\nFound [".count($source_indexed)."] rows\n"; }

$barcodes_raw = array_map('str_getcsv', file($file_barcodes));
$barcodes = array();
foreach ($barcodes_raw as $bc) {
    // Deduplicate the list of barcodes scanned
    
    // Scanner codes may have a prefix e.g. '05' that will need to be stripped
    $barcode = $bc[$barcode_col];
    if ($prefix) {
	if (substr($barcode,0,strlen($prefix)) == $prefix) {
	    $barcode = substr($barcode, strlen($prefix));
	    $bc[$barcode_col] = $barcode;
	}
    }
    $barcodes[$barcode] = $bc;
}

$out = array();
$out[] = implode(',',$firstrow);

foreach ($barcodes as $barcode => $bc) {
    if (! isset($source_indexed[$barcode])) { die("Missing barcode: $barcode\n"); }
    
    $out[] = '"'.implode('","', $source_indexed[$barcode]).'"';
}


echo implode("\n",$out)."\n";