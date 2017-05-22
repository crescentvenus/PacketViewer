#!/usr/bin/php
<?php
/*
        Usage:
        tcpdump -nttttr xxxxx.pcap port 445 | ./PacketViewer.php    ... for captured file
        tcpdump -ntttti eth0 port 445 | ./PacketViewer.php          ... for real time capture
*/
$MIN_TH = 5;
$HTML = "/var/www/html/packet.html";
$fp=fopen("php://stdin","r");
for($i=1;$i<255;$i++){
        for($j=1;$j<255;$j++){
                $buf[$i][$j]=0;
        }
}
$n=0;$prev_min=0;
while($in=fgets($fp)){
        $t=explode(" ",$in);
        $date=$t[0]." ".$t[1];
        $tmp =explode(":",$t[1]);       // hh:mm:ss.xxxxx
        $min =$tmp[1];                  // min
        if(isset($t[3])){
                $s=explode(".",$t[3]);
                if(isset($s[3])) {
                        $ip1=$s[3];
                        $d=explode(".",$t[5]);
                        $ip2=$d[3];
                        $buf[$ip1][$ip2]++;
                }
        }
        $n++;
        if(($n % 2000)==0 || ($min - $prev_min) > $MIN_TH) {
                MkHTML($buf,$date);
                $prev_min = $min;
        }
}
fclose($fp);
MkHTML($buf,$date);
echo "Finished!!!\n";

function MkHTML($buf,$date){
        $date="<H3>$date</H3>";
        $header="<!DOCTYPE HTML><HTML><HEAD><meta http-equiv=\"refresh\" content=\"10\"></HEAD><BODY>";
        $msg="<H3>$date</H3>\n<TABLE>\n";

        for($i=1;$i<255;$i++){
                $sum=0;
                for($j=1;$j<255;$j++){
                        $sum+=$buf[$j][$i];
                }
                $iSum[$i]=$sum;
        }

        for($i=1;$i<255;$i++){
                $tmp="<TR><TD>$i</TD>";$sum=0;
                for($j=1;$j<255;$j++){
                        $var=$buf[$i][$j];
                        $pt=".";
                        if($var<63) {
                                if($var<10) {
                                        $pt=$var;
                                } else {
                                        $pt=chr(ord('A')+$var-10);
                                }
                        }
                        $sum+=$var;
                        $var=20*log($var+1);
                        $color=set_color($var);
                        if($iSum[$j]!=0) $tmp.= "<TD BGCOLOR=$color><A HREF=\"#\" title=\"10.8.0.$i->10.8.0.$j\">$pt</A></TD>";
                }
                if($sum !=0 ) {
                        $msg.=$tmp;
                        $msg.="</TR>\n";
                }
        }
        $msg.="</TABLE>\n<H3>$date</H3></BODY></HTML>\n";
        $fw=fopen($HTML,"w");
        fputs($fw,$header);
        fputs($fw,$msg);
        fclose($fw);
}

function set_color($x){
   if ($x<64) {
       $r=0; $g= $x*4 ; $b=255;
   } else {
       if ($x<128){
           $r=4*( $x -64 );$g=255;$b=255-$r;
       } else {
           if ($x<192){
               $b=4*( $x - 128 );$r=255;$g=255-$b;
           } else {
               $r=255;$g=0;$b=255-4*( $x -192);
           }
       }
   }
   return "#".sprintf("%02x%02x%02x",$r,$g,$b);
}
?>
