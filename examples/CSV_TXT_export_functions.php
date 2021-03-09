<?php
/* This file includes functions to allow the export of account object information. 
These functions are used in the advertiser_objects.php and publisher_objects.php files. */

/* Function to output array of accuunt objects to a text file */
function text_output($array, $file) {
	// store keys of array
	$keys = array_keys($array);
	// open the file we want to output the array to
	$f = fopen($file, 'a');
	// add a new line 
	fputcsv($f, array("\n"));
	// loop through each key in the account array
	foreach($keys as $key => $value) {
		// if the value of the key is not an array then output directly to csv
		if (!is_array($array[$value])) {
			// split up string into an array
			$val = explode(",", $value . ' = ' . $array[$value]);
			// output to file
			fputcsv($f, $val);

		}
		// if the value of the key is an array itself, then ouput to csv
		// by making recursive calls
		else {
			// number of objects of a certain type  (e.g. number of Orders)
			$num_array = sizeof($array[$value]);
			// loop through each object and output to csv through recursive calls
			for ($i = 0; $i < $num_array; $i++) {
				text_output($array[$value][$i], $file);
			}
		}
	}
}


/* Function to output array of accuunt objects to a csv file */
function csv_output($array, $file) {
	/* If the argument is the original parent array (account), 
	we must add another dimension to the array because of the format  
	in which fputcsv takes its arguments */
	if (array_key_exists('Account Name', $array)) {
		$array = array($array);
	} 
	// store keys of array
	$keys = array_keys($array['0']);
	// open the file want to output the array to
	$f = fopen($file, 'a');
	// add a new line 
	fputcsv($f, array("\n"));
	// output the keys as the headers in the csv
	fputcsv($f, $keys);
	// output the values which will show up under the headers in the csv
	foreach($array AS $values) {
		fputcsv($f, $values);
	}
	// must go up one dimension in array to be able to access
	// desired key values
	$array = $array['0'];
	// if the value of the key is an array itself, then ouput to csv
	// by making recursive calls
	foreach($keys as $key => $value) {
		if (is_array($array[$value])) {
			csv_output($array[$value], $file);
		}
		
	}
}

?>