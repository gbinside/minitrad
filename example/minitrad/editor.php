<?php
include('translation.php');

#LOGIN
$pwd = isset($_REQUEST['pwd']) && !empty($_REQUEST['pwd']) ? $_REQUEST['pwd'] : null;
if ( (isset($_COOKIE["LoggedEditor"]) && $_COOKIE["LoggedEditor"] ) || ('password'==$pwd)) {
    $logged=true;
    setcookie("LoggedEditor", 1, time()+3600);
} else {
    $logged=false;
}

if (isset($_REQUEST['logout'])) {
	$logged=false;
	setcookie("LoggedEditor", "", time()-7200);
	Header( "HTTP/1.1 302 Found" ); 
	Header( "Location: ?" ); 
	exit();
}

if ($logged) {
	$chiave = t('Key');
	#NEW
	$new_key = isset($_REQUEST['key']) && !empty($_REQUEST['key']) ? $_REQUEST['key'] : null;
	if ($new_key) {
	  	require_once "spyc.php";
	    foreach ($_REQUEST as $k=>$v) {
	        if (strlen($k)!=2 || empty($v)) {
	            continue;
	        }
	        $filename = $k.'.yaml';
	        $arra = file_exists($filename) ? Spyc::YAMLLoad($filename) : array();
	        $arra[$new_key] = $v;
	        $yaml = Spyc::YAMLDump($arra,4,80);
	        file_put_contents($filename, $yaml);
	    }
	}
	
	#DELETE
	$del_key = isset($_REQUEST['del']) && !empty($_REQUEST['del']) ? base64_decode ( $_REQUEST['del'] ) : null;
	if ($del_key) {
	  	require_once "spyc.php";
	  	foreach (glob("??.yaml") as $filename) {
	    	$arra = Spyc::YAMLLoad($filename);
	        unset($arra[$del_key]);
	        $yaml = Spyc::YAMLDump($arra,4,80);
	        file_put_contents($filename, $yaml);
	  	}    
	}
	
	#EDIT
	$edit_key = isset($_REQUEST['edit']) && !empty($_REQUEST['edit']) ? base64_decode ( $_REQUEST['edit'] ) : null;
	if ($edit_key) {
	    $_REQUEST['search'] = $edit_key;
	}
	
	#RICERCA
	$search_string = isset($_REQUEST['search']) && !empty($_REQUEST['search']) ? $_REQUEST['search'] : null;
	if ($search_string) { #faccio la ricerca sugli yaml
		$risultati = array();
		$lingua = array();
		require_once "spyc.php";
		
		foreach (glob("??.yaml") as $filename) {
			$pezzi = explode('.', $filename);
			$lingua[$pezzi[0]]=Spyc::YAMLLoad($filename);
		}
		$lingue = array_keys($lingua);
		foreach ($lingua as $langcode=>$trad_array) {
			foreach ($trad_array as $kiave=>$traduzione) {
				if (FALSE===strpos($kiave, $search_string) && $search_string!='*') {
	      //if (!preg_match('/'.$search_string.'/i', $kiave)) {
					continue;
				}
				if (array_key_exists($kiave, $risultati)) {
					$risultati[$kiave][$langcode] = $traduzione;
				} else {
					$risultati[$kiave] = array($chiave=>$kiave, $langcode=>$traduzione);
				}
			}		
		}					
		if (!empty($risultati)) { 
			$table_head = array_keys(current ($risultati));
		}
	} 
} #if ($logged)


###### VIEW ####################################################################################################################
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
<style>
#new {
  display:none;
}
#reset {
    position: absolute;
    left: 80px;
    top: 150px;
}
</style>

</head>

<body>
<?php if ($logged): ?>
<div class="container">
<div class="row">
<div class="span8 offset2" style="position: relative;">
<a href="?logout"><?php print t("logout");?></a>
<form class="ricerca">
<fieldset>					
<h2><?php print t("Search");?></h2>
<input type="text" name="search" placeholder="<?php print t("Search");?>" value="<?php print $search_string ?>" />
<span class="help-block"><?php print t("Insert * to match all the placeholder");?></span>
<button type="submit" class="btn"><?php print t("Find");?></button>
</fieldset>
</form>
<form class="ricerca" method="POST" action="?" id="reset">
  <button type="submit" class="btn"><?php print t("Reset");?></button>
</form>

<?php if ($search_string): 
	if (!empty($risultati)):
	?>			
	<table class="table table-striped table-bordered table-hover" id="risultati">
		<caption><?php print t("Results for");?> "<?php print $search_string ?>"</caption>
		<thead>
			<tr>
				<?php foreach ($table_head as $head): ?>
					<th><?php print $head ?></th>
				<?php endforeach ?>
          <th>Del</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($risultati as $riga): ?>
			<tr>
				<?php foreach ($table_head as $k): ?>
					<td>
          <?php if ($k==$chiave): ?><a href="?edit=<?php print base64_encode($riga[$chiave]) ?>"><?php else:?><?php endif ?>
<?php if (array_key_exists( $k , $riga)) { print $riga[$k]; } else {print '&nbsp;';} ?>
          <?php if ($k==$chiave): ?></a><?php else:?><?php endif ?>
          </td>
				<?php endforeach ?>	
        <td>
        <a class="dellink" href="?del=<?php print base64_encode($riga[$chiave]) ?>&search=<?php print $search_string ?>">
        <i class="icon-remove"></i></a></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php else: ?>	
    <div id="notfound">
		  <div class="well"><?php print t("no results");?></div>
      <a href="javascript:;" id="shownew"><?php print t("Do you want to create the placeholder?");?></a>
    </div>
	<?php endif ?>
  <form name="new" id="new" method="POST" action="?search=<?php print $search_string ?>">
  <h2><?php print t("New");?></h2>
  <fieldset>					
  <label><?php print t("Placeholder/Key");?></label>
  <input type="text" name="key" placeholder="<?php print t("Placeholder/Key");?>" value="<?php print $search_string ?>" />
  <?php foreach($lingue as $langcode): ?>
    <label><?php print $langcode?></label>
    <textarea name="<?php print $langcode?>"><?php
      if ($edit_key) print $risultati[$search_string][$langcode];
    ?></textarea>      
  <?php endforeach ?>
  <button type="submit" class="btn"><?php print t("Save");?></button>
  </fieldset>

  </form>
<?php endif ?>

</div>  
</div>
</div>

<?php else: /*logged */?>
	<div class="container">
	<div class="row">
	<div class="span4 offset4">
	<form method="POST">
	<label><?php print t("Password");?></label>
	<input type="password" name="pwd" />
	</form>
	</div>  
	</div>
	</div>
<?php endif /*logged */?>
</body>

<script>
$( function () {
	$('#shownew').click( function () {
		$('#new').show();
		$('#notfound').hide();
	});
  
	$('.dellink').click ( function () {
	    return confirm("<?php print t("Delete this record?");?>");
	});
  
  <?php if ($edit_key): ?>
	$('#new h2').html('<?php print t("Edit");?>');
	$('#new').show();
	$('#notfound').hide();
	$('.ricerca').hide();
	$('#risultati').hide();
  <?php endif ?>
  
	$('#new').submit( function () {
		$(this).attr('action', "?search="+$('[name="key"]').val());
		return true;
	});
});
</script>
</html>