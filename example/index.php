<?php
require('minitrad/translation.php');
?>

<html>
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
</head>
<body>
<a href="?langcode=it">it</a>
<a href="?langcode=de">de</a>
<a href="?langcode=fr">fr</a>
<a href="?langcode=en">en</a>

<ul>
	<li> "this is english" ==&gt; <?php print t("this is english"); ?></li>
	<li> "this is %lingua" ==&gt; <?php print t("this is %lingua", array('%lingua'=>'italiano &nbsp;')); ?></li>
	<li> "this is @lingua" ==&gt; <?php print t("this is @lingua", array('@lingua'=>'italiano &nbsp;')); ?></li>
	<li> "this is !lingua" ==&gt; <?php print t("this is !lingua", array('!lingua'=>'italiano &nbsp;')); ?></li>
	<li> "this translation is missing" ==&gt; <?php print t("this translation is missing"); ?></li>
	<li> "_text_place_1" ==&gt; <?php print t("_text_place_1"); ?></li>
  <?php for ($i=1; $i<4; $i++): ?>
  <li> there is/are <?php print $i ?> stone[s] ==&gt; <?php print format_plural($i, "There is @count stone", "There are @count stones") ?></li>
  <?php endfor ?>
  
</ul>


<a href="minitrad/editor.php" >Editor</a>
<?php print $language->language ?>
</body>
</html>