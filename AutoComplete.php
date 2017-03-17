<?php
include 'SpellCorrector.php';
include 'PorterStemmer.php';
$strip = "/['_.-].*$/";#regular expression to remove unwanted characters
$var = $_GET['q'];
$var = strtolower($var);#casefolding
$MultipleWords = explode(" ",$var);
$QueryLength = count($MultipleWords);
$countWordsInQuery = 0;
$ConcatTopQueryTerms = "";
$WeightTermMap = array();
if($QueryLength == 1)
{
  $eachWord = $var;
}else
{
  for($i=0;$i<$QueryLength-1;$i++){
    $ConcatTopQueryTerms = $ConcatTopQueryTerms . $MultipleWords[$i] . " ";
  }
  $eachWord = $MultipleWords[$QueryLength-1];
}
$url = "http://localhost:8983/solr/moncore1/suggest?q=".$eachWord."&wt=json&indent=true";
$jsonObject = file_get_contents($url);
$jsonDecode = json_decode($jsonObject);
$Results_Array = $jsonDecode->{'suggest'}->{'suggest'}->{$eachWord}->{'suggestions'};
foreach($Results_Array as $Array_Key)
{
  $term = $Array_Key->{'term'};
  $weight = $Array_Key->{'weight'};
  $PatternMatching = preg_replace($strip, " " ,$term);
  $StemmedTerm = PorterStemmer::Stem($PatternMatching);#stemming to handle inflectional word forms
  if(!(in_array($StemmedTerm, $WeightTermMap))){
    $WeightTermMap[$weight] = $StemmedTerm;
  }
  
}
$KeyWeights = array_keys($WeightTermMap);
rsort($KeyWeights);
$index = 0;
foreach($KeyWeights as $key)
{
  $TermValue[$index] = $WeightTermMap[$key];
  $index++;
}
if($QueryLength == 1){
$TopQuerySuggestions = $TermValue;
}

else{
  $counter = 0;
  foreach($TermValue as $lastQueryTerm)#handling multiple word length query
  {
    $TopQuerySuggestions[$counter] = $ConcatTopQueryTerms . $lastQueryTerm;
    $counter++;
  }
}


echo json_encode($TopQuerySuggestions);
?>
