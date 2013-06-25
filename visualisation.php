<?php
	/*
		Thesis Student Mentor Visualisation
		
		Developed by Makuc Ziga (2013)
		Licensed under the Creative Commons Attribution ShareAlike 2.5 Slovenia
			http://creativecommons.org/licenses/by-sa/2.5/
		and
			http://creativecommons.si/
		
		Visualisation template and function getResults() and connectedComponents() were taken from
			site "https://github.com/mziga/pda", and are work of Makuc Ziga, licensed under the
			Creative Commons Attribution ShareAlike 2.5 Slovenia license.
	*/


	
/* THIS TO BE CHANGED PRIOR TO USE OF THIS APP */
//Example file can be retrieved from site: http://eprints.fri.uni-lj.si/cgi/search/advanced?screen=Public%3A%3AEPrintSearch&_action_search=Search&_fulltext__merge=ALL&_fulltext_=&title_merge=ALL&title=&creators_name_merge=ALL&creators_name=&abstract_merge=ALL&abstract=&keywords_merge=ALL&keywords=&subjects_merge=ALL&type=thesis&thesis_type=engd&department_merge=ALL&department=&editors_name_merge=ALL&editors_name=&refereed=EITHER&publication_merge=ALL&publication=&date=&tags_merge=ALL&tags=&satisfyall=ALL&order=-date%2Fcreators_name%2Ftitle
	$path_to_JSON_file = "http://path-to-url/export_fri_JSON.js"; 
/* ************************************** */

// Function that receives URL as input and returns contents of desired site
function getResults($url){
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function connectedComponents ($aEdges) {
	// Developed by Csaba Gabor
	// given an edge array, $aEdges, this will return an array
	// of connected components. Each element of the returned
	// array, $aTrees, will correspond to one component and
	// have an array of the vertices in that component.
	$aTrees = array();
	$result = array();
	$aAdj = array();
	$ctr=-1;
	foreach ($aEdges as $br){ // Construct V/E adjacancy array
		foreach ($br as $i=>$v){
			if (!array_key_exists($v,$aAdj)){
				$aAdj[$v]=array($br[1-$i]);
			}else{
				array_push ($aAdj[$v], $br[1-$i]);
			}
		}
	}
	foreach ($aAdj as $v=>$aTrees[++$ctr]){ // Now build distinct
		for ($i=0;$i<sizeof($aTrees[$ctr]);++$i){ // components
			$aV = &$aTrees[$ctr];
			/* If $aAdj[$aV[$i]] is not array, warning is showed, but function works. If anything else is changed it does not work, so
				when this happens, "IGNORE" is added to array. When showing, ignore every row which contains "IGNORE" at the end */
				/* This is also changed in this algorithm in comparison to original */
			if(is_array($aAdj[$aV[$i]])){
				$merged = array_merge($aV, $aAdj[$aV[$i]]);
			}else{
				$merged = array_merge($aV, array("IGNORE"));
			}
			$aV = array_keys(array_flip($merged));
			unset ($aAdj[$aV[$i]]);
		}
	}
	
	//Return only valid clusters
	$internal_counter_i=0;
	for($i=0; $i<sizeof($aTrees); $i++){
		if($aTrees[$i][sizeof($aTrees[$i])-1] != "IGNORE"){
			for($j=0;$j<sizeof($aTrees[$i]); $j++){
				$result[$internal_counter_i][$j]=$aTrees[$i][$j];
			}
			$internal_counter_i++;
		}
	}
	return $result;
} 

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Vizualizacija povezav študentov in mentorjev za diplome na FRI</title>
	<link rel="stylesheet" href="jquery-ui.css" />
</head>

<body>
<style type="text/css">
.background {
  fill: #eee;
}

line {
  stroke: #fff;
}

text.active {
  fill: red;
}

</style>

<!-- d3 plugin -->
<script src="d3.v3.min.js"></script>
<script src="jquery-1.9.1.js"></script>
<script src="jquery-ui.js"></script>

<center><h1>Vizualizacija povezav študentov in mentorjev za diplome na FRI</h1>
<p>Število prikazanih študentov: <input id="amount" size="2" style="border: 0; color: #f6931f; font-weight: bold;" /></p>
<div style="width:500px" id="slider-range-max"></div>
<p>Charge: <input id="amount2" size="2" style="border: 0; color: #f6931f; font-weight: bold;" /></p>
<div style="width:500px" id="slider-range-min"></div>
<div id="gv_viz"></div>
<?php

	$url = $path_to_JSON_file;
	$ret_json = getResults($url); // Retrieve result
	$results = json_decode($ret_json, true); // Decode result
	
	$mentors = array(); // List of mentors
	$students = array(); // List of students
	$students_mentors = array(); // List of students and corresponding mentors

	// Set default number of shown students
	$number_of_results=$_GET[show_last];
	if($number_of_results>count($results)){
		$number_of_results=count($results);
	}else if ($number_of_results<0){
		$number_of_results=count($results);
	}else if ($_GET[show_last]==""){
		$number_of_results=15; //Set default value to show 15 students only
	}else{
		$number_of_results=$_GET[show_last];
	}
	// Build nodeSet
	for($i=0; $i<$number_of_results; $i++){
		// There can be more than one mentor
		for($mentor=0; $mentor<count($results[$i][mentors]); $mentor++){
			// Some mentors do not have "honourific"
			if($results[$i][mentors][$mentor][name][honourific]!=""){
				$mentor_name = $results[$i][mentors][$mentor][name][honourific].' '.$results[$i][mentors][$mentor][name][given].' '.$results[$i][mentors][$mentor][name][family];
			}else{
				$mentor_name = $results[$i][mentors][$mentor][name][given].' '.$results[$i][mentors][$mentor][name][family];
			}
			if(!in_array($mentor_name, $mentors, true)){
				array_push($mentors, $mentor_name);
			}
			// Store connections
			$students_mentors[$i][$mentor]=$number_of_results+array_search($mentor_name, $mentors);
		}
		// Only one student
		$student_name = $results[$i][creators][0][name][given].' '.$results[$i][creators][0][name][family].','.$results[$i][eprintid];
		if(!in_array($student_name, $students, true)){
				array_push($students, $student_name);
		}

	}
	print_r($students_mentors);

	// Print ouput (nodeSet and linkSet)
	echo PHP_EOL;
	echo '<script>';
	
	$mentor_has_students = array();
	// Print linkSet
	echo 'var linkSet = [';
	$first=0;
	$max_connections=0;
	$i_row=0;
	// Go through every student
	for($i=0; $i<count($students_mentors); $i++){
		// Go through every mentor that student has
		for($mentor=0; $mentor<count($students_mentors[$i]); $mentor++){
			if($first!=0){
				echo ','.PHP_EOL;
			}else{
				$first++;
			}
			// Print links
			echo '{sourceId: "'.($i+1).'", "value":0, targetId: "'.($students_mentors[$i][$mentor]+1).'", status: "0"}';
			// Check how many students each mentor has
			$mentor_has_students[$students_mentors[$i][$mentor]]+=1;
			if($mentor_has_students[$students_mentors[$i][$mentor]]>$max_connections){
				// Store number of maximal stundents that one mentor has
				$max_connections=$mentor_has_students[$students_mentors[$i][$mentor]];
			}
			// Store every student-mentor connection
			// +1, because connectedComponents alghorithms requires IDs greater than 0
			$connected_components[$i_row]= array($i+1, $students_mentors[$i][$mentor]+1);
			$i_row++;
		}
	}
	// Check which are connected
	$aComponents = connectedComponents ($connected_components);
	$number_of_groups = sizeof($aComponents);
	

	echo '];'.PHP_EOL;
	
	// Print nodeSet
	echo 'var nodeSet = [';
	// Go through every student
	for($i=0; $i<count($students); $i++){
		$group=-1;
		// Check to which group he belongs to
		for($j=0; $j<sizeof($aComponents); $j++){
			for($k=0;$k<sizeof($aComponents[$j]); $k++){
				if($aComponents[$j][$k]==($i+1)){
					$group=$j;
					break;
				}
			}
			if($group!=-1){
				break;
			}
		}
		// Print node with corresponding URL to eprints site and group ID; note that count (degree of node) is always 1 if printing student
		echo '{id: "'.($i+1).'", name: "'.explode(',', $students[$i])[0].'", group: "'.$group.'", hlink: "http://eprints.fri.uni-lj.si/'.explode(',', $students[$i])[1].'/", count: "1"},'.PHP_EOL;

	}
	// Go through every mentor
	for($i=0; $i<count($mentors); $i++){
		$group=-1;
		// Check to which group he belongs to
		for($j=0; $j<sizeof($aComponents); $j++){
			for($k=0;$k<sizeof($aComponents[$j]); $k++){
				if($aComponents[$j][$k]==(count($students)+$i+1)){
					$group=$j;
					break;
				}
			}
			if($group!=-1){
				break;
			}
		}
		// Print node, without corresponding URL (as he does not have one yet) and with group ID; also degree of node is stored
		echo '{id: "'.(count($students)+$i+1).'", name: "'.explode(',', $mentors[$i])[0].'", group: "'.$group.'", hlink: "#", count: "'.$mentor_has_students[(count($students)+$i)].'"}';
		if($i<(count($mentors)-1)){
			echo ','.PHP_EOL;
		}
	}
	echo '];'.PHP_EOL;
	echo '</script>';
?>


<script type="text/javascript">
	// Template from GitHub was used and modified for appropriate needs: http://bl.ocks.org/mbostock/4062045/
	// Template from GitHub was used and modified for appropriate needs: https://github.com/mziga/pda (work of Ziga Makuc).
    var width = 1200;
    var height = 900;
	<?
	// If number of groups (number of different mentors) is less than 10, use this scale (looks more transparent as if 20 was chosen by default)
	if($number_of_groups <= 10){
		echo 'colorScale = d3.scale.category10().domain(d3.range(10)); //Define number of colors - number of groups-clusters-connected components';
	}else{
		echo 'colorScale = d3.scale.category20().domain(d3.range(20)); //Define number of colors - number of groups-clusters-connected components';
	}
	?>
	
	// Append visualisation to #gv_viz
	var svg = d3.select("#gv_viz").append("svg:svg")
		.attr("width", width)
		.attr("height", height);
		  
		  
	/* Legend (help menu) */
	var legend = d3.select("#gv_viz").append("svg:svg")
		.attr("width", 200 )
		.attr("height", 600)
		.style("margin-left", 30 + "px")
		.style("margin-bottom", height-590 + "px")
		.on("mousedown", legend_mouse_out);

	legend.append("rect")
		.attr("class", "background")
		.attr("width", 200)
		.attr("height", 20)
		.on("mouseover", legend_mouse_click);
	  
    legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 15)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "bold 14px Arial")
		.attr("fill", "Black")
		.text("Informacije");

	var node_hash = [];
	var type_hash = [];

	// Create a hash that allows access to each node by its id
	nodeSet.forEach(function(d, i) {
		node_hash[d.id] = d;
		type_hash[d.type] = d.type;
	});

	// Append the source object node and the target object node to each link
	linkSet.forEach(function(d, i) {
		d.source = node_hash[d.sourceId];
		d.target = node_hash[d.targetId];
	});
	
	// Create a force layout and bind Nodes and Links
	var force = d3.layout.force()
				.charge(-800)
				.nodes(nodeSet)
				.links(linkSet)
				.size([width, height])
				.linkDistance( function(d) { if (width < height) { return width*1/8; } else { return height*1/8; } } ) // Controls edge length
				.on("tick", tick)
				.start();
	
	// Draw lines for Links between Nodes
	var link = svg.selectAll(".gLink")
				.data(force.links())
				.enter().append("g")
				.append("a")
				.attr("xlink:href", function(d) { return d.elink; })
				.attr("class", "gLink")
				.append("line")
				.attr("class", "link")
				.style("stroke", "#AAA" )
				.style("stroke-width", "2" )
				.attr("x1", function(d) { return d.source.x; })
				.attr("y1", function(d) { return d.source.y; })
				.attr("x2", function(d) { return d.target.x; })
				.attr("y2", function(d) { return d.target.y; });

	// Create Nodes
	var node = svg.selectAll(".node")
				.data(force.nodes())
				.enter().append("g")
				.attr("class", "node")
				.on("mouseover", nodeMouseover)
				.on("mouseout", nodeMouseout)
				.call(force.drag);

	// Append circles to Nodes
	node.append("circle")
		.attr("x", function(d) { return d.x; })
		.attr("y", function(d) { return d.y; })
		.attr("r", function(d,i) {return ((d.count/<? echo $max_connections; ?>)*12); }   ) // Size of node is defined by number of connections to this node - normalized with max connections; If max connections to one node is 6..size will be 10. If other has 3 connections size will be 5.
		.style("fill", "White") // Make the nodes hollow looking
		.style("stroke-width", 5) // Give the node strokes some thickness
		.style("stroke", function(d, i) { colorVal = colorScale(d.group); return colorVal; } ) // Node stroke colors; colour of stroke is defined by which group node is in
	    .call(force.drag);
 
	// Append text to Nodes
	node.append("a")
		.attr("xlink:href", function(d) { return d.hlink; })
		.append("text")
		.attr("x", function(d) { return 10; } )
		.attr("y", function(d) { return -15; } )
		.attr("text-anchor", function(d) { return "start"; })
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font",  function(d) {
			if(d.id>=<? echo (count($students)+1); ?>){
				return 	"bold 14px Arial";
			}else{
				return 	"normal 14px Arial";
			}
		})
		
		.attr("fill", function(d) {
			if(d.id>=<? echo (count($students)+1); ?>){
				return "Black";
			}else{
				return colorScale(d.group);
			}
			
		})
		.attr("dy", ".35em")
		.text(function(d) { return d.name; });
				
	

	function tick() {
		link
			.attr("x1", function(d) { return d.source.x; })
			.attr("y1", function(d) { return d.source.y; })
			.attr("x2", function(d) { return d.target.x; })
			.attr("y2", function(d) { return d.target.y; });
		node
			.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
	}

	function nodeMouseover() {
		d3.select(this).select("circle").transition()
			.duration(150)
			.attr("r", function(d,i) { return 12; } );

		d3.select(this).select("text").transition()
			.duration(150)
			.style("font", "bold 14px Arial")
			.attr("fill", function(d) {
			if(d.id>=<? echo (count($students)+1); ?>){
				return "Black";
			}else{
				return colorScale(d.group);
			}
			
		})
	}
	
	function nodeMouseout() {
		d3.select(this).select("circle").transition()
			.duration(150)
			.attr("r", function(d,i) {return ((d.count/<? echo $max_connections; ?>)*12); }   ) // Size of node is defined by number of connections to this node - normalized with max connections; If max connections to one node is 6..size will be 10. If other has 3 connections size will be 5.
		
		d3.select(this).select("text").transition()
			.duration(150)
			.style("font",  function(d) {
				if(d.id>=<? echo (count($students)+1); ?>){
					return 	"bold 14px Arial";
				}else{
					return 	"normal 14px Arial";
				}
			})
			.attr("fill", function(d) {
			if(d.id>=<? echo (count($students)+1); ?>){
				return "Black";
			}else{
				return colorScale(d.group);
			}
			
		})
	}


	/* Function for similarity slider */
	$(function() {
		$( "#slider-range-max" ).slider({
			range: "max",
			min: 0,
			max: <? echo count($results); ?>,
			value: <? echo $number_of_results; ?>,
			slide: function( event, ui ) {
				$( "#amount" ).val( ui.value );
					window.location="visualisation.php?show_last="+ui.value+"";
				}
		});
		$( "#amount" ).val( $( "#slider-range-max" ).slider( "value" ) );
	});
	/* Function for force slider */

	$(function() {
		$( "#slider-range-min" ).slider({
			range: "min",
			min: 0,
			max: 100,
			value: 20,
			slide: function( event, ui ) {
				$( "#amount2" ).val( ui.value);
				force.charge(-(100-ui.value)*10);
				force.start();
			}
		});
		$( "#amount2" ).val( $( "#slider-range-min" ).slider("value" ));
	});

	function legend_mouse_click(){
		legend.selectAll("text.title").remove();
		
		legend.append("rect")
			.attr("class", "info")
			.style("fill", "gray")
			.style("fill-opacity", 0.1)
			.attr("width", 200)
			.attr("height", 600);
	  
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 15)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "bold 14px Arial")
		.attr("fill", "Black")
		.text("Klik za izklop");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 30)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Barva vozlišča predstavlja skupino,");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 45)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("kateri študent ali mentor pripada.");

	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 75)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Odebeljen tekst črne barve");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 90)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("predstavlja mentorja.");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 120)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Velikost vozlišča predstavlja");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 135)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("število študentov, ki");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 150)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("jih ima nek mentor. ");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 180)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("S klikom na študenta, vas");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 195)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("preusmeri na stran,");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 210)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("kjer je diploska naloga.");

	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 240)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("S prvim drsnikom kontrolirate");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 255)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("število zadnjih prikazanih diplom.");
	
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 285)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Z drugim drsnikom kontrolirate");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 300)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("privlačnost med vozlišči.");
}

	function legend_mouse_out() {
		legend.selectAll("rect.info").remove();
		legend.selectAll("text.info").remove();
		legend.selectAll("text.title").remove();
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 15)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "bold 14px Arial")
			.attr("fill", "Black")
			.text("Informacije");
	}
</script>		

</body>
</html>
