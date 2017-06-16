#!/usr/bin/php
<?php
/*
        Usage:
        tcpdump -nttttr xxxxx.pcap port 445 | ./PacketViewer.php    ... for captured file
        tcpdump -ntttti eth0 port 445 | ./PacketViewer.php          ... for real time capture
*/
// このスクリプトが出力するhtmlファイル名（このスクリプトで書き込みできること。）
$HTML = "/var/www/html/port445.html";

// 観測対象のサブネットアドレスと表示のカラーを設定
$NET_ADDR=array("192.168.1"=>"lightgreen","192.168.2"=>"yellow","10.0.224"=>"lightblue");

// ドメインコントローラやファイルサーバー等のホスト名を設定。
$SERVER=array("192.168.1.240"=>"DC-1","192.168.1.241"=>"File-1","192.168.2.242"=>"File-2");

// ノードのマトリクス初期化
foreach($NET_ADDR as $NET =>$color){
        for($i=1;$i<255;$i++){
                $ip_array[]="$NET.$i";
        }
}
foreach($ip_array as $ip){
        foreach($ip_array as $jp){
                $buf[$ip][$jp]=0;
        }
}

//　パケットの処理
$fp=fopen("php://stdin","r");
$n=0;$prev_min=0;
while($in=fgets($fp)){
    if(strpos($in,"IP")>0){
        $t=explode(" ",$in);
        $date=$t[0]." ".substr($t[1],0,8);
        $tmp =explode(":",$t[1]);       // hh:mm:ss.xxxxx
        $min =$tmp[1];                  // min
        $t[3]=substr($t[3],0,strrpos($t[3],"."));       // sIP
        $t[5]=substr($t[5],0,strrpos($t[5],"."));       // dIP
        $sIP= $t[3];
        $dIP=$t[5];
        if(isset($buf[$sIP][$dIP])){
                $buf[$sIP][$dIP]++;
        } else {
                @$unkBuf["$sIP - > $dIP"]++;    // ノードマトリクスに定義されていない宛先
        }
        $n++;
    }
}
fclose($fp);
MkHTML($buf,$unkBuf,$date,10);
echo "Finished!!!\n";

function MkHTML($buf,$unkBuf,$date,$refresh){
        global $NET_ADDR;
        global $SERVER;
        global $HTML;
        $total=0;
        $style=" style=\"font-family:monospace; font-size:small\"";
        $header="<!DOCTYPE HTML><HTML lang=\"ja\"><HEAD><meta charset=\"UTF-8\">\n<meta http-equiv=\"refresh\" content=\"$refresh\"></HEAD><BODY>\n";
        $msg="<H3>$date</H3>\n<TABLE>\n";

        foreach($buf as $sIP=>$dIP_r){
                $sum=0;
                foreach($dIP_r as $dIP=>$var){
                        $sum+=$buf[$sIP][$dIP];
                }
                $iSum[$sIP]=$sum;
        }

        foreach($buf as $sIP=>$dIP_r){
                if(isset($SERVER[$sIP])){
                        $Pi=$SERVER[$sIP];
                } else {
                        $Pi="-";
                }
                $Pi="$sIP($Pi)";
                $n_addr=substr($sIP,0,strrpos($sIP,"."));
                if(isset($NET_ADDR[$n_addr])){
                        $n_color=$NET_ADDR[$n_addr];
                } else {
                         $n_color="red";
                }
                $tmp="<TR $style><TD BGCOLOR=$n_color>$Pi</TD>";$sum=0;
                foreach($dIP_r as $dIP=>$var){
                        $var=$buf[$sIP][$dIP];
                        if($var<100) {
                                $pt=sprintf("%02d",$var);
                        } else {
                                if($var>1000) {
                                        $pt=">>";
                                } else {
                                        $pt=" >";
                                }
                        }
                        if($var!=0) $total++;
                        $sum+=$var;
                        $c=20*log($var+1);
                        $color=set_color($c);
                        if($iSum[$dIP]!=0) {
                                if(isset($LocalDB[$dIP])) {
                                        $Pj=$LocalDB[$dIP];
                                } else {
                                         if(isset($SERVER[$dIP])){
                                                $Pj=$SERVER[$dIP];
                                         } else {
                                                $Pj="-";
                                        }
                                }
                                $Pj="$dIP($Pj)";
                                if($sIP == $dIP){
                                        $tmp.= "<TD BGCOLOR=\"gray\"></TD>";
                                } else {
                                        if($var==0){
                                                $tmp.= "<TD BGCOLOR=\"#505050\">..</TD>";
                                        } else {
                                                $tmp.= "<TD BGCOLOR=$color><A HREF=\"#\" title=\"$Pi->$Pj($var)\">$pt</A></TD>";
                                        }
                                }
                        }
                }
                if($sum !=0 ) {
                        $msg.=$tmp;
                        $msg.="</TR>\n";
                }
        }
        $date=trim($date);
        $msg.="</TABLE>\n<H3>$date($total)</H3>\n";
        $footer="不明な宛先の通信<BR>\n";
        foreach($unkBuf as $info=>$counts){
                $footer.="<LI>$info($counts)</LI>\n";
        }
        $footer.="</BODY></HTML>\n";
        $fw=fopen($HTML,"w");
        fputs($fw,$header);
        fputs($fw,$msg);
        fputs($fw,$footer);
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
