<?php

class EIApps {
  private function println($s) {
    printf("%s\n",$s);
  }

  private static function expand_app_ids($app_id) {
    if ( gettype( $app_id ) == "array" ) {
      $app_ids = $app_id;
    } else if ( $app_id == "_ei_all" ) {
      $app_ids = EIConfig::get_appIds();
    } else {
      $app_ids =  array( $app_id );
    }

    return $app_ids;
  }


  static function get_app_info( $app_id ) {
      $app_ids = EIApps::expand_app_ids($app_id);
      $out = '<apps>';
    foreach ($app_ids as $id ) {
      if(EIConfig::get_appVisible( $id )== "true"){
	$out .= '<app id=\'' . $id . '\'>';
	$out .= EIConfig::get_appInfoXML( $id )->asXML();
	$out .= '</app>';
      }
    }
     $out .= '</apps>';

    return $out;
  }

  static function get_app_help( $app_id ) {
      $app_ids = EIApps::expand_app_ids($app_id);
      $out = '<apps>';
    foreach ($app_ids as $id ) {
      if(EIConfig::get_appVisible( $id )== "true"){
	$out .= '<app id=\'' . $id . '\'>';
	$out .= EIConfig::get_apphelpXML( $id )->asXML();
	$out .= '</app>';
      }
    }
     $out .= '</apps>';

    return $out;
  }

  static function get_app_parameters( $app_id ) {
    $app_ids = EIApps::expand_app_ids($app_id);
    $out = ' <apps> ';
    foreach ($app_ids as $id ) {
      if(EIConfig::get_appVisible( $id )== "true"){
	$out .= '<app id=\'' . $id . '\'>';
	$out .= EIConfig::get_appParametersXML( $id )->asXML();
	$out .= '</app>';
      }
    }
    $out .= ' </apps> '; 

    return $out;
  }

  static function get_app_details( $app_id ) {
    $app_ids = EIApps::expand_app_ids($app_id);
    $out = '<apps>';
    foreach ( $app_ids as $id ) {
      if(EIConfig::get_appVisible( $id ) == "true"){
	$out .= '<app id=\'' . $id . '\'>';
	$out .= EIConfig::get_appInfoXML( $id )->asXML();
	$out .= EIConfig::get_appHelpXML( $id )->asXML();
	$out .= EIConfig::get_appParametersXML( $id )->asXML();
	$out .= '</app>';
      }
    }
    $out .= '</apps>'; 

    return $out;
  }


  static function execute( $app_id, $parameters ) {
    $execInfo = EIConfig::get_appExecXML($app_id);
    $output = "";
    $output = EIApps::execute_cmdline($app_id, $parameters);
    return $output;
  }

  private static function token($length) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
    return substr(str_shuffle($characters), 0, $length);
  }

  static function execute_cmdline( $app_id, $parameters ) {
    $execInfo = EIConfig::get_appExecXML($app_id);
    $sandboxProps = EIConfig::get_sandboxProps();
    $pAuxy = (EIConfig::get_appParametersARRAY($app_id));
    $paramsArr = $pAuxy["parameters"];

    // prefix to be attached to each parameter anme
    $param_prefix = "-";
    if( $paramsArr["@attr"] && array_key_exists("prefix",$paramsArr["@attr"])) 
	$param_prefix = $paramsArr["@attr"]["prefix"];

    // boolean to check compatible parameters or not
    $param_check = false;
    if( $paramsArr["@attr"] && array_key_exists("check",$paramsArr["@attr"]))
	$param_check = $paramsArr["@attr"]["check"];

    // the parameters template
    if ( $execInfo->cmdlineapp ) {
      $cmdline =  (string) $execInfo->cmdlineapp;
    } else {
      throw new Exception("Could not find environment cmdlineapp for '" 
			  . $app_id . "'");
    }
    // session ID
    //
    $sessiondir_str = sys_get_temp_dir()."/easyinterface_sessions/";
    $sessionid_str = EIApps::getSessionData();

    if (!file_exists($sessiondir_str)) {
      mkdir($sessiondir_str, 0755);
    }

    $sessiondir_str  .= $sessionid_str;
    mkdir($sessiondir_str,0755);
    // client ID
    //
    $clientid_str = "";
    if ( array_key_exists( '_ei_clientid', $parameters ) ) {    
      $clientid_str = $parameters['_ei_clientid'];
      unset( $parameters['_ei_clientid'] );
    }

    // outformat 
    //
    $outformat_str = "txt";
    if ( array_key_exists( '_ei_outformat', $parameters ) ) {    
      $outformat_str = $parameters['_ei_outformat'];
      unset( $parameters['_ei_outformat'] );
    }
    // files
    //   
    $files_str = "";
    $root_str = "";
    $stream_str = "";
    $execid_str = EIApps::token(5);

    // the temporal directory has the form
    //
    //    easyinterface_VWXYZ/_ei_files 
    //
    // where VWXYZ are some random characters generated by php. In
    // unix based systems, this directory is typically placed in
    // /tmp. In Windows it is placed in temp. 
    // 
    // **IMPORTANT** The use of _ei_files is essential, so clients
    // can reconstruct file names easily (i.e., whatever comes after
    // _ei_files is the name passed by the client)
    
    $aux = sys_get_temp_dir()."/easyinterface_".$execid_str; 
    $dir = str_replace("\\", "/", $aux);
    unset($aux);
    mkdir($dir, 0755);
    $dir_str = $dir . "/_ei_files";
    mkdir($dir_str, 0755);
    $dir_str = $dir . "/_ei_stream";
    mkdir($dir_str, 0755);
    $dir_str = $dir . "/_ei_tmp";
    mkdir($dir_str, 0755);
    $dir_str = $dir . "/_ei_download";
    mkdir($dir_str, 0755);

    if ( array_key_exists( '_ei_files', $parameters ) ) {    
      EIApps::build_directories($files_str,$root_str,$parameters);
      unset( $parameters['_ei_files'] );
    }

    $root_str = $dir;
    // outline
    //
    $outline_str = "";
    if ( array_key_exists('_ei_outline',$parameters) ) {
      $outline_str = implode(" ", $parameters['_ei_outline'] );
      unset( $parameters['_ei_outline'] );
    }

    $typesofparams = array("selectone","selectmany","flag");
    // other parameters
    //
    $parameters_str = "";
    // first get what parameters we have to check
    // then check this.
    // finish write the rest if param_check is false
    $local;
    $localprefix;
    $localcheck;
    $localtype;
    $paramsdone = array();
    foreach ($parameters as $key => $values) {
      $encontrado = false;
      foreach ($paramsArr as $typep => $paramsgroup){
	//Careful! 
	if(array_key_exists("@attr",$paramsgroup)){
	  //only exists one parameter of type "typep"
	  //paramsgroup = parameter
	  $actual = $paramsgroup;
	  if(!array_key_exists("name",$actual["@attr"])){
	    throw new Exception("Missing param name in the app config file");
	  }else if ($actual["@attr"]["name"]==$key){
	    //found parameter
	    $local = $actual; //copy to check after. 
	    $localtype = $typep;
	    $encontrado = true; 
	    break;
	  }
	}else if($typep != "@attr"){
	  //exists more than one parameter of type "typep"
	  //paramsgroup[N] = [N]parameter
	  foreach($paramsgroup as $k=>$actual){
	    if(!array_key_exists("name",$actual["@attr"])){
	      throw new Exception("Missing param name in the app config file");
	    }else if ($actual["@attr"]["name"]==$key){
	      $local = $actual;//copy to check after
	      $localtype = $typep;
	      $encontrado = true;
	      break;
	    }
	  }//end foreach paramsgroup
	  if($encontrado)
	    break;
	}
      }//end foreach paramsArr*/
      if($encontrado){
	//THE PARAMETER IS IN THE VARIABLE local
	$localprefix = $param_prefix;
	if(array_key_exists("prefix",$local["@attr"])){
	  $localprefix = $local["@attr"]["prefix"];
	}
	$localcheck = $param_check;
	if(array_key_exists("check",$local["@attr"])){
	  $localcheck = $local["@attr"]["check"];
	}
	if($localcheck=="true"){//check this parameter 
	  switch( $localtype ){
	  case "selectone":
	    if( count($values)!=1)
	      throw new Exception("This parameter (".$local["@attr"]["name"]
				  .") only accept one value");
	    if(!EIApps::checkvalue($local,$values[0]))
	      throw new Exception("Invalid parameter's value: ".$k
				  ."in parameter: ".$local["@attr"]["name"] );
	    break;

	  case "selectmany":
	    if(count($values)<1)
	      throw new Exception("This parameter (".$local["@attr"]["name"].
				  ") requeire at least one value");
	    foreach ($values as $v)
	      if(!EIApps::checkvalue($local,$v))
		throw new Exception("Invalid parameter's value: ".$v
				    ." in parameter: ".$local["@attr"]["name"] );
	    break;

	  case "flag":
	      $tval = "true";
	      $fval = "false";
	      if(array_key_exists("trueval",$local["@attr"]))
		$tval = $local["@attr"]["trueval"];
	      if(array_key_exists("falseval",$local["@attr"]))
		$fval = $local["@attr"]["falseval"];
	    if( count($values)>1)
	      throw new Exception("This parameter (".$local["@attr"]["name"]
				  .") only accept one value");
	    else if(count($values)==0) $values[0]=$fval;
	    if($values[0] != $tval && $values[0] != $fval)
	      throw new Exception("This flag parameter (".$local["@attr"]["name"].") only accept ".$tval." or ".$fval."");
	    break;

	  case "textfield":
	    break;

	  default: 
	    throw new Exception("Unknown type param: ".$localtype);
	    break;

	  }//end switch localtype check
	}//end localcheck
      }else{// end encontrado
	//this parameter is not specificated on the config file
        throw new Exception("This app doesnt accept more parameters".
			      " than the especificated");
      }

      //$key is the name of the param in the XML
      //$values is an array with all the values
      switch ($localtype){
      case "selectone": 
	$parameters_str .= " ".$localprefix."".$key ;
	$parameters_str .= " ".$values[0];
	break;
      case "selectmany":
	$parameters_str .= " ".$localprefix."".$key ;
	foreach($values as $val)
	  $parameters_str .= " ".$val;
	break;
      case "flag":
	  $explicit = "false";
	  if(array_key_exists("explicit",$local["@attr"]))
	    $explicit =  $local["@attr"]["explicit"];
	  $tval = "true";
	  $fval = "false";
	  if(array_key_exists("trueval",$local["@attr"]))
	    $tval = $local["@attr"]["trueval"];
	  if(array_key_exists("falseval",$local["@attr"]))
	    $fval = $local["@attr"]["falseval"];
	  if($explicit == "false" && $values[0] == $tval )
	    $parameters_str .= " ".$localprefix."".$key ;
	  else if ($explicit == "true")
	    $parameters_str .= " ".$localprefix."".$key." ".$values[0];
	break;
      case "textfield":
	  if(array_key_exists("passinfile",$local["@attr"])){
	    $name_tmpfile = tempnam($dir,$key."_");
	    file_put_contents($name_tmpfile,$values[0]);
	    chmod($name_tmpfile,0755);
	    $parameters_str .= " ".$localprefix."".$key;
	    $parameters_str .= " '".$name_tmpfile."'";
	  }else{
	    $parameters_str .= " ".$localprefix."".$key;
	    $parameters_str .= " '".$values[0]."'";
	  }
	break;
      }//end switch localtype write str
    }//end parameters
    $replace_pairs = array(
			   "_ei_files" => $files_str,
			   "_ei_root" => $root_str,
			   "_ei_outline" => $outline_str,
			   "_ei_parameters" => $parameters_str,
			   "_ei_clientid" => $clientid_str,
			   "_ei_outformat" => $outformat_str,
			   "_ei_sessionid" => $sessionid_str,
			   "_ei_sessiondir" => $sessiondir_str,
			   "_ei_execid" => $execid_str
			   );
    
    $cmdline = strtr( $cmdline, $replace_pairs);
    $cmdline = escapeshellcmd($cmdline);
 
   
    //echo $cmdline; 
    $outputLines = array();
    $sb_timeout = EIApps::get_timeout($sandboxProps);
    $sb_timeclean = EIApps::get_timeclean($sandboxProps);
    $sb_logpath = EIApps::get_logpath($sandboxProps);
    $sb_maxproc = EIApps::get_maxproc($sandboxProps);
    $launcher = "./launcher.sh ".$sb_timeout." ".$sb_maxproc." ".$sb_logpath." ".$cmdline;
    chdir("bin"); // we always execute in the bin directory
    exec($launcher, $outputLines);

    $output =  implode("\n", $outputLines);
    $cleaner = "./clean.sh ".$sb_timeclean." ".$root_str." ".$sb_logpath. " > /dev/null 2>/dev/null &";

    exec($cleaner);
    return $output;
  }

  private static function get_timeout($sandboxProps){
    if(!array_key_exists("timeout",$sandboxProps)){
      return 30;
    } else {
      return $sandboxProps["timeout"];
    }
  }

  private static function get_timeclean($sandboxProps){
    if(!array_key_exists("timeclean",$sandboxProps)){
      return 30;
    } else {
      return $sandboxProps["timeclean"];
    }
  }

  private static function get_maxproc($sandboxProps){
    if(!array_key_exists("max_proc",$sandboxProps)){
      return 30;
    } else {
      return $sandboxProps["max_proc"];
    }
  }

  private static function get_logpath($sandboxProps){
    if(!array_key_exists("logpath",$sandboxProps)){
      return "./exec.log";
    } else {
      return $sandboxProps["logpath"];
    }
  }


  private static function checkvalue($posibles, $actual){
    foreach($posibles["option"] as $opt){
      if(!array_key_exists("value",$opt["@attr"]))
	throw new Exception("Mising value of attribute in the configuration file.");
      if($opt["@attr"]["value"] == $actual)
	return true;
    }
    return false;
  }

  private static function valid_file_name( $fname ) {
    return preg_match("/^[a-zA-z0-9\-\+_\.\/]+$/",$fname) && strpos($fname,"..") == false;
  }

  private static function build_directories(& $files_str, $dir, $parameters )
  {
  
    foreach ( $parameters['_ei_files'] as $file ) {
      if ( !EIApps::valid_file_name( $file["name"] ) )
      	throw new Exception("Forbidden filename: ".$file["name"]
      			    .". Filenames can only contain the "
      			    ."following characters: [a-z][A-Z][0-9][_][-][.][/]."
      			    ." Spaces and others characters are forbidden."
                            ." It cannot contain .. as well");

      // if it is a file, save it
      if( array_key_exists('type',$file) && strcmp($file['type'],'text') == 0 ) {
	$filename = $dir."/".$file["name"];
        $dirname  = dirname($filename);

	// create the directories of the leading path
	if ( !file_exists($dirname) )
	  mkdir($dirname,0755,true);

	file_put_contents($filename,$file["content"]);
	$files_str .= " ".$filename;  // concatenate the filename to the list of filenames
      }
    }
  }

  private static function getSessionData(){
    session_start();
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
      // last request was more than 1 hour ago
      //TODO: clean folder session...
      session_unset();     // unset $_SESSION variable for the run-time 
      session_destroy();   // destroy session data in storage
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
    if(!isset($_SESSION["sessionID"])){
      $_SESSION["sessionID"] = EIApps::token(10);
    }
    return $_SESSION["sessionID"];
  }
}

?>
