# PacketViewer
セグメント内のパケット監視

WannaCryは、LANの中ではsmb(445/TCP)で感染するということなので、簡易な監視のツールを作ってみました。
ミラーしたセグメントのパケットをtcpdumpでキャプチャし、観測したパケットの数をPHPのスクリプトで可視化します。
使い方は、次のような感じ、、、

#tcpdump -ntttti eth0 port 445 | PacketViewer.php

PackerViewer.php は、定期的にhtmlファイルを生成します
利用者はブラウザでこのhtmlファイルを閲覧します。
htmlファイルは (例えば10秒毎に)自身をrefreshしています。

表示される、画像の１マスは、ノード（PCやサーバ）間の累積パケット数を色で表現しています。
通信がまったく観測されない場合は濃い青色で、観測されたパケット数が多いと黄色～赤に変化します。
また、マスの中の１文字は、観測したパケットの数に応じて、0-9,A-Z.....と表示しています。
マスの上にマウスを移動させると、対象端末のIPアドレスが表示されます。
表示には、IPアドレスの最後の桁（例えば A.B.C.Dの場合 D)だけを利用しています。

普段とは異なる端末間で445/TCPの通信が発生した場合の検知に役立つかも？

![表示例](https://github.com/crescentvenus/PacketViewer/blob/master/packet2.png)
