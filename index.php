<?php require_once('Connections/local.php'); 

	$seconds = 300000;
	set_time_limit ($seconds);

	//path to your plex media server
	$plexMediaServerFolder = 'C:\\Users\\*****Username****\\AppData\\Local\\Plex Media Server\\';
	
	$mediaFolder = $plexMediaServerFolder.'Media\\localhost';

	class MyDB extends SQLite3{
		
		function __construct(){
			$this->open($plexMediaServerFolder.'Plug-in Support\\Databases\\com.plexapp.plugins.library.db');
		}
		
	}
	$db = new MyDB();
	if(!$db){
	  echo $db->lastErrorMsg();
	} else {
	  //echo "Opened database successfully\n";
	}

	$sql ="SELECT * from media_items order by id asc";
	
	$ret = $db->query($sql);
	while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
     
		$id = $row['id'];
		$metadata_item_id = $row['metadata_item_id'];
		
	  	$getMetaData ="SELECT * from metadata_items where id = $metadata_item_id";

		$retMetaData = $db->query($getMetaData);
		
		while($rowMetaData = $retMetaData->fetchArray(SQLITE3_ASSOC) ){
			 
			if($rowMetaData['metadata_type'] == 1){
				
				$title = $rowMetaData['title'];
				$title = preg_replace('/\'/', '', $title);
				
				//echo 'Title: '.$title.' ';
				
				$getMetaParts ="SELECT * from media_parts where media_item_id = $id";
				$retMetaParts = $db->query($getMetaParts);
				while($rowMetaParts = $retMetaParts->fetchArray(SQLITE3_ASSOC) ){
			
					$hash = $rowMetaParts['hash'];
					
					//echo $hash.'<br>';
					
					mysql_select_db($database_local, $local);
					$query_config = "SELECT hash FROM indexFileFolderSync where hash = '$hash'";
					$config = mysql_query($query_config, $local) or die (mysql_error());
					$row_config = mysql_fetch_assoc($config);
					$totalRows_config = mysql_num_rows($config);
					
					if($totalRows_config == 0){
						
						echo $id." - ";
						echo $title. ' <br> ';
			
						$path = explode('\\', $rowMetaParts['file']);
						
						$pathNew = '';
						
						for($i=0;$i<(count($path)-1);$i++){
							
							$pathNew .= $path[$i].'\\';
							
						}
						
						$firstLetter = $hash[0];
						$folderHash = substr($hash,1).'.bundle';
						
						$pathNew = utf8_decode($pathNew);
						
						//if(preg_match("/\'/", $pathNew)){
							
							exec('copy "'.$mediaFolder.'\\'.$firstLetter.'\\'.$folderHash.'\\Contents\\Indexes\\" "'.$pathNew.'"');
							$insertSQL = sprintf("INSERT INTO indexFileFolderSync (`title`, `hash`) VALUES ('$title', '$hash')");
							mysql_select_db($database_local, $local);
							$Result1 = mysql_query($insertSQL, $local) or die (mysql_error());
							
						//}
						
					}
					 
				}
				
			}
			 
		}
		 
   }
   $db->close();
?>