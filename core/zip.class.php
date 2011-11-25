<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

require_once (ABSPATH . 'wp-admin/includes/class-pclzip.php');





class EasyZIP {
    var $filelist = array();
    var $data_segments = array();
    var $file_headers  = array();

    function EasyZIP() {
        if (!@function_exists('gzcompress')) 
		die(sprintf(__('Error: %s function is not found', 'SL_framework'), "<code>gzcompress()</code>"));
    }
 
    function addFile($filename) {
        if(file_exists($filename)) {
            $this -> filelist[] = str_replace('\\', '/', $filename);
        } else {
            // Nothing
        }
    }

    function addDir($dirname) {
        if ($handle = opendir($dirname)) { 
        
           while (false !== ($filename = readdir($handle))) { 
             if ($filename != "." && $filename != "..") 
                  $this->addFile($dirname . '/' . $filename);
           } 
        
           closedir($handle); 
        } else {
            //Nothing
        }
    }

	function createZip($splitfilename, $chunk_size) {

		$splitted = 1;
		$split_offset = 4;
		$old_offset = $split_offset;
		$disk_number = 1 ; 
		$split_signature = "\x50\x4b\x07\x08";
		
		// We create the zip file
		foreach($this -> filelist as $k => $filename) {
			
			//Get the data
			$filedata = file_get_contents($filename);
			
			//Compressing data
			$c_data   = gzcompress($filedata);
			$compressed_filedata    = substr(substr($c_data, 0, strlen($c_data) - 4), 2); // fix crc bug
			
			// Get the time
			clearstatcache();
			$filetime = filectime($filename);
			if ($filetime == 0) { 
				getdate() ;
			} else { 
				getdate($filetime) ; 
			}
			if ($timearray['year'] < 1980) {
				$timearray['year']    = 1980;
				$timearray['mon']     = 1;
				$timearray['mday']    = 1;
				$timearray['hours']   = 0;
				$timearray['minutes'] = 0;
				$timearray['seconds'] = 0;
			} 
			$dostime = (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
			$dtime    = dechex($dostime);
			$hexdtime = '\x' . $dtime[6] . $dtime[7] . '\x' . $dtime[4] . $dtime[5];
			$hexddate = '\x' . $dtime[2] . $dtime[3]. '\x' . $dtime[0] . $dtime[1];
			eval('$hexdtime = "' . $hexdtime . '";');
			eval('$hexddate = "' . $hexddate . '";');
			$last_mod_file_time = $hexdtime;
			$last_mod_file_date = $hexddate;
	
			//Set Local File Header
			$local_file_header_signature = "\x50\x4b\x03\x04";//4 bytes  (0x04034b50) local_file_header_signature
			$version_needed_to_extract = "\x14\x00";  //2 bytes version_needed_to_extract
			$general_purpose_bit_flag = "\x00\x00";  //2 bytes general_purpose_bit_flag
			$compression_method = "\x08\x00";  //2 bytes compression_method
			$crc_32 = pack('V', crc32($filedata)); //  4 bytes crc_32
			$compressed_size = pack('V', strlen($compressed_filedata));// 4 bytes compressed_size
			$uncompressed_size = pack('V', strlen($filedata));//4 bytes uncompressed_size
			$filename_length = pack('v', strlen($filename));// 2 bytes filename_length
			$extra_field_length = pack('v', 0);  //2 bytes extra_field_length
			
			$local_file_header =     $local_file_header_signature . $version_needed_to_extract . $general_purpose_bit_flag .$compression_method .$last_mod_file_time .$last_mod_file_date .$crc_32 .$compressed_size .$uncompressed_size .$filename_length .$extra_field_length . $filename;
			
			//Set Data Descriptor
			$data_descriptor =  $crc_32.$compressed_size . $uncompressed_size;          //4+4+4 bytes

			//Set Data Segment
			$data_segments[]     =     $local_file_header . $compressed_filedata . $data_descriptor;
			$data_block = implode('', $data_segments); 
			
			//Set File Header
			
			$new_offset        = strlen( $split_signature . $data_block );
			$central_file_header_signature  = "\x50\x4b\x01\x02";//4 bytes  (0x02014b50)
			$version_made_by                = pack('v', 0);  //2 bytes
			$file_comment_length            = pack('v', 0);  //2 bytes
			$disk_number_start              = pack('v', $disk_number - 1); //2 bytes
			$internal_file_attributes       = pack('v', 0); //2 bytes
			$external_file_attributes       = pack('V', 32); //4 bytes
			$relative_offset_local_header   = pack('V', $old_offset); //4 bytes
        
			if($splitted) {
				$disk_number = ceil($new_offset/$chunk_size);
				$old_offset = $new_offset - ($chunk_size * ($disk_number-1));
			} else {
				$old_offset = $new_offset;
			}
        
			$file_headers[] = $central_file_header_signature . $version_made_by . $version_needed_to_extract . $general_purpose_bit_flag . $compression_method . $last_mod_file_time . $last_mod_file_date . $crc_32 .$compressed_size .$uncompressed_size .$filename_length .$extra_field_length . $file_comment_length .  $disk_number_start . $internal_file_attributes . $external_file_attributes . $relative_offset_local_header . $filename;
			$central_directory = implode('', $file_headers) ; 
		}
		
		// We finalyze
		$zipfile_comment = "Compressed/Splitted by PHP EasyZIP";
		if($splitted) {
		    $data_len = strlen($split_signature . $data_block . $central_directory);
		    $last_chunk_len = $data_len - floor($data_len / $chunk_size) * $chunk_size;
		    $old_offset = $last_chunk_len - strlen($central_directory);
		}

		$end_central_dir_signature    = "\x50\x4b\x05\x06";//4 bytes  (0x06054b50)
		$number_this_disk             = pack('v', $disk_number - 1);//2 bytes
		$number_disk_start              = pack('v', $disk_number - 1);//  2 bytes
		$total_number_entries          = pack('v', sizeof($file_headers));//2 bytes
		$total_number_entries_central = pack('v', sizeof($file_headers));//2 bytes
		$size_central_directory         = pack('V', strlen($central_directory));  //4 bytes
		$offset_start_central         = pack('V', $old_offset); //4 bytes     
		$zipfile_comment_length       = pack('v', strlen($zipfile_comment));//2 bytes
		$endCentralDirectory  = $end_central_dir_signature . $number_this_disk . $number_disk_start . $total_number_entries . $total_number_entries_central . $size_central_directory . $offset_start_central . $zipfile_comment_length . $zipfile_comment; 
	
		$zip = $split_signature . $data_block . $central_directory . $endCentralDirectory;
		
		// We split the zip file
		$j = 0 ; 
		for ($i = 0; $i < strlen($zip); $i += $chunk_size) {
			$j++ ; 
			$out = substr($zip, $i, $chunk_size);
			
			if($i+$chunk_size < strlen($zip) ) {
			
				$sfilename = basename ($splitfilename,".zip"); 
				$path = str_replace(basename ($splitfilename), "", $splitfilename) ; 
				$sfilename = $path . $sfilename . ".z" . sprintf("%02d",$j);

			} else {
				$sfilename = $splitfilename;
			}
			echo "###".$sfilename."<br/>" ; 
			$fp = file_put_contents($sfilename, $out);
		}

	}
} 
?>