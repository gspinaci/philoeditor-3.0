<?PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start() ;
$docContent = $_POST['data'] ;
$docData = $_POST['document'] ;
$statData = json_decode($_POST['stats']);
$user =json_decode(base64_decode($_COOKIE['user']),true);
$saveType=$_POST['type'];
$versions=$_POST['versioni'];
$fileDoc=$_POST['path']."/00 - Metadata/infoDoc.json";
$infoDoc=json_decode(file_get_contents($fileDoc),true);
$base ="../files" ; 
$statfile=$_POST['path']."/00 - Metadata/stats.json";
$maxId=count($infoDoc)+1;
if($saveType == "save"){
	//$oldPath=$docData['path'];
	$newPath = preg_split('/\//',$docData['path']) ;
	$id=explode('.',$newPath[3]);
	array_pop($newPath);
	$newPath[]=$id[0];
	if (isset ($docData['authors'])&& in_array($user['name'],$docData['authors'])) {//c'è un auotore e l'autore sono io o sono un amministratore
		foreach($infoDoc as $key=>$val){
			if($val['id'] == $id[0]){
				$infoDoc[$key]["tei"]=$docData['tei'];
			}
		}
		file_put_contents($fileDoc, json_encode($infoDoc,JSON_PRETTY_PRINT));
		$ok=file_put_contents("../".$docData['path'],$docContent);
		$result["success"]="true";
		$result["reason"]="File salvato correttamente";
	}
	else {
		http_response_code(401);
		$result["success"] = false;
		$result["reason"] = "La tua attuale autenticazione non consente questa operazione";
		echo json_encode($result);
		exit;
	}
}
else {//salva come nuovo
	if (isset($user['name']) ) {
		$name = $user['name'] ;
	} else {
		$name = 'unknown' ;	
	}
	$pathParts = preg_split('/\//',$docData['path']) ;			
	$oldPath=$docData['path'];
	$newPath = preg_split('/\//',$oldPath) ;
	array_pop($newPath);
	$newPath[]=$maxId;
	$newPathStr=join($newPath,"/");
	$newPathStr=$newPathStr.".html";
	$myfile = fopen("../".$newPathStr, "w");
	$ok=fwrite($myfile, $docContent);
	fclose($myfile);
	$newEl=array("id"=>$maxId,"order"=>$docData['name'],"label"=>$docData['label'],"authors"=>array($user['name']),"path"=>$newPathStr,"versions"=>array($versions['old'],$versions['new']),"tei"=>$docData['tei']);
	$infoDoc[]=$newEl;
	file_put_contents($fileDoc, json_encode($infoDoc,JSON_PRETTY_PRINT));
}
if ($ok) {
	$stats = json_decode(file_get_contents($statfile),true );
	$x = &$stats ;
	for ($i = 2; $i< count($newPath); $i++) {//l'indice parte da due in modo da saltare il nome dell'opera
		if (!isset($x[$newPath[$i]])) {
			$x[$newPath[$i]] = array() ;
		}
		if (($i+1)==count($newPath))
			$x[$newPath[$i]]=($statData);
		$x = &$x[$newPath[$i]] ; 
	}
	$ok = file_put_contents($statfile,json_encode($stats,JSON_PRETTY_PRINT))  ;
	$result["success"] = true;
	$result["reason"] = "Nuovo file salvato correttamente";
}
else {
	http_response_code(403);
	$result["success"] = false;
	$result["reason"] = "Non ho potuto scrivere il file";	
}

function search_key($array,$key,$value) {
        $return = array() ;
        foreach ($array as $rec) {
                if (isset($rec[$key]) && $rec[$key] == $value) {
                        array_push($return, $rec) ;
                };
        }
        return $return ;
}


echo json_encode($result);
die() ; 

?>
