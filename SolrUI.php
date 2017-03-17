<?php
include 'SpellCorrector.php';
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 50;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$check = isset($_REQUEST['stateCheck']) ? $_REQUEST['stateCheck'] : "false";


$results = false;
$count = 0;
if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  $CorrectedQuery = "";
  $PostSpellCheck = "";
  $queryWords = explode(" ",$query);
  foreach($queryWords as $eachQueryWord){
  $CorrectedQuery = $CorrectedQuery . SpellCorrector::correct($eachQueryWord) . " ";
  }
  $initialQuery = $query;
  if($query != rtrim($CorrectedQuery)){
    $PostSpellCheck = rtrim($CorrectedQuery);
    if($check == "false"){
      $query = $PostSpellCheck;
    }
    
  }else{
    $PostSpellCheck = "";
  }

  require_once('Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, 'solr/moncore1');
  $additionalParameters = array('sort' => 'pageRankFile desc');
  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
     if($_REQUEST['solrAddParam']=="pageRank")
    {
      $results = $solr->search($query, 0, $limit,$additionalParameters);
    }
    else
    {
       $results = $solr->search($query, 0, $limit);
    }
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}
$csv = array();
$file = fopen("/home/meghananyakp/CrawledData/SolrInput.csv", "r");
while (false !== ($line = fgetcsv($file)))
{
$csv[$line[1]] = $line[0];
}

?>

<html>
  <head>
    <title>PHP Solr Client Example</title>
  </head>
  <body>
    <form  name="myForm" accept-charset="utf-8" method="get">
      <label for="q">Search:</label>

      <input id="q" name="q" type="text" size="40" value="<?php echo htmlspecialchars($initialQuery, ENT_QUOTES, 'utf-8'); ?>"/>
      <input id="stateCheck" name="stateCheck" type="hidden" value="<?php echo htmlspecialchars($check, ENT_QUOTES, 'utf-8'); ?>"/>
      <input id="SpellCorrectedQuery" type="hidden" value="<?php echo htmlspecialchars($PostSpellCheck, ENT_QUOTES, 'utf-8'); ?>"/>
      <input id="UserTypedQuery" type="hidden" value="<?php echo htmlspecialchars($initialQuery, ENT_QUOTES, 'utf-8'); ?>"/>
      <?php if(strlen($PostSpellCheck) > 0 && $check == "false"){
       echo "<p>Showing results for " . "<a href='javascript:sumbitSpellCorrectedQuery();'>".$PostSpellCheck ."</a></p>";
       echo "<p>Search instead for " . "<a href='javascript:sumbitUserTypedQuery();'>".$initialQuery ."</a></p>"; 
       }?>

       <?php if($check == "true"){
       echo "<p>Did you mean" . "<a href='javascript:sumbitSpellCorrectedQuery();'>".$PostSpellCheck ."</a></p>";
       }?>

      <input type="submit"/><br/><br/>
      <!--<input type="radio" name="solrAddParam" value="pageRank"/>PageRank Ranking<br/>-->
      <input type="radio" checked="checked" name="solrAddParam" value="pageRank" <?php if (isset($_REQUEST['solrAddParam']) && $_REQUEST['solrAddParam'] == 'pageRank') echo ' checked="checked"';?>/>PageRank Ranking<br/>
      <input type="radio" name="solrAddParam" value="solr" <?php if (isset($_REQUEST['solrAddParam']) && $_REQUEST['solrAddParam'] == 'solr') echo ' checked="checked"';?>/>Solr<br/>
</form>
<?php

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  $crawl_result = array();
  $display = 1;
  foreach ($results->response->docs as $doc)
  {
	if($count == 10)
		break;
	$getCont = file_get_contents($doc->id);
	if(array_key_exists(sha1(strip_tags($getCont)),$crawl_result)){
		$diplay = 0;

		continue;
	}else{
		$display = 1;
		$crawl_result[sha1(strip_tags($getCont))] = 0;
		$count++;
	}


?>
      <li>
        <table style="border: 1px solid black; text-align: left">
          <tr>
            <th>title</th>
            <td><?php echo $doc->title; ?></td>
          </tr>
          <tr>
            <th> author</th>
            <?php if($doc->author){ ?>
            <td><?php echo $doc->author; ?></td>
            <?php }else { ?>
            <td> N/A </td>
            <?php }?>
          </tr>
          <tr>
            <th>created </th>
            <?php if($doc->created){ ?>
            <td><?php echo $doc->created; ?></td>
            <?php }else { ?>
             <td> N/A </td>
	     <?php }?>
          </tr>
          <tr>
            <th>stream_size</th>
            <td><?php echo ($doc->stream_size)/1024; ?></td>
          </tr>
	  <tr>
            <th></th>
            <td><a href="<?php echo $csv[$doc->id]; ?> ">Link to the site</a></td>
          </tr>
        </table>
      </li>
<?php
  }

?>
    </ol>
<?php
}
?>
  </body>
<script src="jquery-1.10.2.js" type="text/javascript"></script>
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="spellcheckAutocomplete.js"></script>
  <script type="text/javascript">
  function sumbitSpellCorrectedQuery(){
    $correctedQuery = document.getElementById("SpellCorrectedQuery").value;
    document.getElementById("q").value = $correctedQuery;
    document.getElementById("stateCheck").value = "false";
    document.myForm.submit();
  }
  function sumbitUserTypedQuery(){
    $userQuery = document.getElementById("UserTypedQuery").value;
    document.getElementById("q").value = $userQuery;
    document.getElementById("stateCheck").value = "true";
    document.myForm.submit();


  }
  $(document).ready(function(){
  jQuery("#q").keyup(function(){
            jQuery.ajax({
               url: 'AutoComplete.php',
               data: 'q=' + $("#q").val(),
               dataType: "json",
               success: function(data) 	{
               jQuery("#q").spellcheckAutocomplete(
               {
               threshold: 0.9,
               source:data
               }
               );
              }
            });
           });

        });
  </script>
</html>
