<?php 
//
//netstatus.php - render webpage of dmr repeater status 
//Copyright (C) 2012 David Kierzokwski (kd8eyf@digitalham.info)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with this program; if not, write to the Free Software
//Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
$cachefile = 'cache.html';
$cachetime = 60; //CACHE PAGE FOR 60 SECONDS
if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
    include($cachefile);
    echo "<!-- Cached copy, generated ".date('H:i', filemtime($cachefile))." -->\n";
    exit;
}
ob_start();
?>
<html>
<body>
<link rel="stylesheet" href="netstatus.css" type="text/css">
<div id="header" class="fixed">
		<div class="nav">
			<ul>
			  <li><a href="netstatus.php" class="active" >NetStatus</a></li>
              <li><a href="lastheard.php" >LastHeard</a></li>		
			  <li><a href="calllog.php">Call Log</a></li>
			  </div>
			  </ul>
		</div>
	</div>
<div id="content" class="fixed">
    <div id="maincontent">
        <h2>DMR Network Status</h2>
        <? 
        include '/usr/local/include/dmrdb.inc' ;
        date_default_timezone_set( 'UTC' ) ;
        $Date = date( 'l F jS, Y', time() ) ;
        $DateTime = date( 'd M y, H:i:s', time() ) ;
        $Query = "SELECT `DmrID`, `Description` FROM  `Network` WHERE Network.Publish = '1' Group By DmrID" ;
        mysql_query( $Query ) or die( "MYSQL ERROR:" . mysql_error() ) ;
        $Result = mysql_query( $Query ) or die( mysql_errno . " " . mysql_error() ) ;
        while ( $SourceNet = mysql_fetch_array( $Result ) ) {
            $Net = $SourceNet[0] ;
            ?>
            <table  width="100%" border="0" cellspacing="0" >
                <tr>
                    <td colspan=10 class="networkheader"><? echo $SourceNet[Description]; ?></td>
                </tr>
                <tr>
                    <th>Country</th>
                    <th>State</th>
                    <th>Location</th>
                    <th>Frequency</th>
                    <th>Offset</th>
                    <th>Owner</th>
                    <th>&nbsp;&nbsp;STATUS&nbsp;&nbsp;</th>
                    <th>&nbsp;&nbsp;SLOT 1&nbsp;&nbsp;</th>
                    <th>&nbsp;&nbsp;SLOT 2&nbsp;&nbsp;</th>
                <tr>
            <?
    //RepeaterLog ON (Repeater.DmrID = RepeaterLog.DmrID)
    //LEFT OUTER JOIN Network ON Repeater.DmrID = Network.DmrID
            $Query = "SELECT Repeater.DmrID AS DmrID, Repeater.Role AS Role, Repeater.Country AS 
Country, Repeater.City AS City, Repeater.State AS State, Repeater.Frequency
 AS Frequency, Repeater.Offset AS Offset, Repeater.Trustee AS Trustee, 
Repeater.Publish AS Publish, Repeater.Override AS Override, Repeater.OverrideOnline
 AS OverrideOnline, Repeater.OverrideTs1Online AS OverrideTs1Online, Repeater
.OverrideTs2Online AS OverrideTs2Online, Network.Description AS Description, 
A.DateTime AS LastHeard, A.Ts1Online AS Ts1Online, A.Ts2Online AS Ts2Online
 FROM Repeater LEFT JOIN (SELECT t1.* FROM RepeaterLog AS t1 LEFT OUTER JOIN 
RepeaterLog AS t2 ON( t1.DmrID = t2.DmrID AND t1.DateTime < t2.DateTime ) 
Where t2.DmrID IS NULL) A ON A.DmrID = Repeater.DmrID LEFT JOIN Network ON ( 
Repeater.DmrID = Network.DmrID ) WHERE Repeater.Publish = '1' AND Repeater.SourceNet
 = '$SourceNet[DmrID]' GROUP BY DmrID; ";
            mysql_query( $Query ) or die( "MYSQL ERROR:" . mysql_error() ) ;
            $Result2 = mysql_query( $Query ) or die( mysql_errno . " " . mysql_error() ) ;
            $i = 1 ;
            while ( $Repeater = mysql_fetch_array( $Result2 ) ) {
                if ( $i % 2 != 0 ) $RowClass = "odd" ; else  $RowClass = "even" ;
                if ( $Repeater[Role]==1) $RowClass = "master" ;                   
                if ( $Repeater[Publish] == 1 ) {
                $LongAgo = ( strtotime( "now" ) - strtotime( $Repeater[LastHeard] ) ) ;

			echo "<td nowrap class=$RowClass>$Repeater[Country]</td>" ;
			echo "<td nowrap class=$RowClass>$Repeater[State]</td>" ;
			echo "<td nowrap class=$RowClass>$Repeater[City] </td>" ;
			echo "<td nowrap class=$RowClass>$Repeater[Frequency]</td>" ;
			echo "<td nowrap class=$RowClass>$Repeater[Offset]</td>" ;
			echo "<td width=100% nowrap class=$RowClass>$Repeater[Trustee]</td>" ;
        	if ( $Repeater[Override] == 1 ) {
				if ( $Repeater[OverrideOnline] == 1 ) {
					echo "<td class=online>ONLINE</td>" ;
					if ( $Repeater[OverrideTs1Online] == 1 ) { echo "<td class=online>LINKED</td>" ;
					} else { echo "<td class=local>LOCAL</td>" ; }
					if ( $Repeater[OverrideTs2Online] == 1 ) { echo "<td class=online>LINKED</td>" ;
					} else { echo "<td class=local>LOCAL</td>" ; }
				} else {
					echo "<td class=offline>LH: ".duration($LongAgo)."</td><td class=offline></td><td class=offline></td>"; }
			} else {
                if (($LongAgo > 60)){
					echo "<td class=offline>LH: ".duration($LongAgo)."</td><td class=offline></td><td class=offline></td>";
				} else {
					echo "<td class=online>ONLINE</td>" ;
					if ( $Repeater[Ts1Online] == 1 ) {
						echo "<td class=online>LINKED</td>" ;
					} else {
						echo "<td class=local>LOCAL</td>" ;
					}
					if ( $Repeater[Ts2Online] == 1 ) {
						echo "<td class=online>LINKED</td>" ;
					} else {
						echo "<td class=local>LOCAL</td>" ;
					}
				}
			}
		}
		echo "</tr>" ;
		$i++ ;
    }
	echo "</table>" ;
	echo "<br />" ;
    
    }?>
    </div>
   </div>
  </div>
  <div id="footer" class="fixed">
		<p class="credits">
            UNDER CONSTRUCTION  UNDER CONSTRUCTION  UNDER CONSTRUCTION  UNDER CONSTRUCTION  UNDER CONSTRUCTION  
		 </p>				   
	</div>
</body>
</html>
<?
function duration( $seconds )
{
	$days = floor( $seconds / 60 / 60 / 24 ) ;
	$hours = $seconds / 60 / 60 % 24 ;
	$mins = $seconds / 60 % 60 ;
	$secs = $seconds % 60 ;
	$duration = '' ;
	if ( $days > 0 ) {
		$duration = "$days"."D" ;
	} elseif ( $hours > 0 ) $duration .= "$hours" . "H" ;
	if ( $mins > 0 ) $duration .= "$mins" . "M" ;
	if ( ( $secs > 0 ) && ( $hours < 1 ) && ( $mins < 10 ) ) $duration .= "$secs" .
			"S" ;
	$duration = trim( $duration ) ;
	if ($seconds >= 365*24*60) {$duration = "NEVER";};
 
    if ( $duration == null ) $duration = '0' . 'S' ;
	return $duration ;
} 
$cached = fopen($cachefile, 'w');
fwrite($cached, ob_get_contents());
fclose($cached);
ob_end_flush(); 
?>