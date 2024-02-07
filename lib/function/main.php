<?php
class B4code{
    var $Ip;
    var $APILocation;
    var $apikey;
    public function __construct($apikey){
        $this->APILocation = "https://stopbot.net/";
        $this->apikey = $apikey;
    }
    public function GetIP(){
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];
        
        switch(true){
            case (filter_var($client, FILTER_VALIDATE_IP)):
                $this->Ip = $client;
                break;
            case(filter_var($forward, FILTER_VALIDATE_IP)):
                $this->Ip = $forward;
                break;
            default:
                $this->Ip = $remote;
                break;
        }
    }
    public function RedirectTo($keyname){
        $RetryingCurl = 1;
        RetryingCurl:
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->APILocation."/api/shorterlink?apikey=".$this->apikey."&ip=".$this->Ip."&keyname=".$keyname."&ua=".urlencode($_SERVER['HTTP_USER_AGENT'])."&url=".urlencode($_SERVER['REQUEST_URI'])."&".rand(1,1000000));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); # In some versions of PHP, SSL verification may be required for the PHP cURL function.
        	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); #
		$response = curl_exec($ch);
        switch(true){
            case ($response):
                return $response;
                break;
            case ($RetryingCurl >= 5):
                break;
            default:
                goto RetryingCurl;
                $RetryingCurl += 1;
                break;
        }
    }
    public function redirect($to){
        $message = array();
        switch ($to){
            case 'STOPBOTNET 405':
                header("HTTP/1.1 405 Method Not Allowed");
                $message["title"] = $_SERVER['SERVER_NAME'];
                $message["head"] = "[405]";
                $message["desc1"] = $_SERVER['SERVER_NAME'];
                $message["desc2"] = "Method Not Allowed";
                $message["contact"] = "contact@".$_SERVER['SERVER_NAME'];
                echo $this->respondHtml($message);
                break;
            case 'STOPBOTNET 403':
                header("HTTP/1.1 403 Forbidden");
                $message["title"] = $_SERVER['SERVER_NAME'];
                $message["head"] = "[403]";
                $message["desc1"] = $_SERVER['SERVER_NAME'];
                $message["desc2"] = "You do not have permission to access this page.";
                $message["contact"] = "contact@".$_SERVER['SERVER_NAME'];
                echo $this->respondHtml($message);
                break;
            case 'STOPBOTNET 404':
                header("HTTP/1.1 404 Not Found");
                $message["title"] = $_SERVER['SERVER_NAME'];
                $message["head"] = "[404]";
                $message["desc1"] = $_SERVER['SERVER_NAME'];
                $message["desc2"] = "The file you are looking for was not found.";
                $message["contact"] = "contact@".$_SERVER['SERVER_NAME'];
                echo $this->respondHtml($message);
                break;
            case '200':
                $message["title"] = $_SERVER['SERVER_NAME'];
                $message["head"] = "[200]";
                $message["desc1"] = $_SERVER['SERVER_NAME'];
                $message["desc2"] = "please contact administrator for more info.";
                $message["contact"] = "abuse@".$_SERVER['SERVER_NAME'];
                echo $this->respondHtml($message);
                break;
            default:
                header("Location: ".$to);
                break;
        }
    }
    public function UpdateAPIResponseJS($keyname){
        $RetryingCurl = 1;
        RetryingCurl:
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->APILocation."/api/shorterlink?apikey=".$this->apikey."&ip=".$this->Ip."&keyname=".$keyname."&js=1&".rand(1,1000000));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); # In some versions of PHP, SSL verification may be required for the PHP cURL function.
	    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); # 
		$response = curl_exec($ch);
        switch(true){
            case ($response):
                return $response;
                break;
            case ($RetryingCurl >= 5):
                break;
            default:
                goto RetryingCurl;
                $RetryingCurl += 1;
                break;
        }
    }
    public function PostResponseJS(){
        $stat = false;
        header('Content-Type: application/json; charset=utf-8');
        switch(true){
            case !isset($_GET['keyname']):
                break;
            case !preg_match("/[a-z0-9A-Z\.\_\-]{1,64}/", @$_GET['keyname']):
                break;
            default:
                $dat = $this->UpdateAPIResponseJS($_GET['keyname']);
                $datJSON = json_decode($dat, true); 
                switch(@$datJSON['AddVisitorStatus']){
                    case 1:
                        $stat = true;
                        break;
                }
                break;
        }
        
        echo json_encode(array("stat"=>$stat));
    }
    public function RedirectWithJs($to){
        ?>
<html>
	<head>
	    <?php require_once"lib/function/encrypter.php"; ?>
        <title><?php echo $_SERVER['SERVER_NAME']; ?></title>
	</head>
	<body style="display: none">
    </body>
    <script>
        function drawVisor() {
            const canvas = document.getElementById("visor");
            const ctx = canvas.getContext("2d");

            ctx.beginPath();
            ctx.moveTo(5, 45);
            ctx.bezierCurveTo(15, 64, 45, 64, 55, 45);

            ctx.lineTo(55, 20);
            ctx.bezierCurveTo(55, 15, 50, 10, 45, 10);

            ctx.lineTo(15, 10);

            ctx.bezierCurveTo(15, 10, 5, 10, 5, 20);
            ctx.lineTo(5, 45);

            ctx.fillStyle = "#2f3640";
            ctx.strokeStyle = "#f5f6fa";
            ctx.fill();
            ctx.stroke();
            }

            const cordCanvas = document.getElementById("cord");
            const ctx = cordCanvas.getContext("2d");

            let y1 = 160;
            let y2 = 100;
            let y3 = 100;

            let y1Forward = true;
            let y2Forward = false;
            let y3Forward = true;

            function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, innerWidth, innerHeight);

            ctx.beginPath();
            ctx.moveTo(130, 170);
            ctx.bezierCurveTo(250, y1, 345, y2, 400, y3);

            ctx.strokeStyle = "white";
            ctx.lineWidth = 8;
            ctx.stroke();

            if (y1 === 100) {
                y1Forward = true;
            }

            if (y1 === 300) {
                y1Forward = false;
            }

            if (y2 === 100) {
                y2Forward = true;
            }

            if (y2 === 310) {
                y2Forward = false;
            }

            if (y3 === 100) {
                y3Forward = true;
            }

            if (y3 === 317) {
                y3Forward = false;
            }

            y1Forward ? (y1 += 1) : (y1 -= 1);
            y2Forward ? (y2 += 1) : (y2 -= 1);
            y3Forward ? (y3 += 1) : (y3 -= 1);
        }
        drawVisor();
        animate();
    </script>
    <script>
        async function fetchData(){
            try {
                const response = await fetch("rsc/rjs.json?keyname=<?php echo $_GET['q']; ?>");
                const data = await response.json();
                if(data.stat == true){
                    document.getElementsByTagName("html")[0].innerHTML = '<meta http-equiv="refresh" content="0;url=<?php echo $to; ?>" />';
                }
                return data;
            } catch (error) {
                console.error('Failed fetch:', error);
            }
        }
    </script>
    <script>
		function x8sf7sj(){
			var cookieEnabled;
			var webdriver;
			if(navigator.cookieEnabled == true){
				cookieEnabled = true;
			}else{
				cookieEnabled = false;
			}if(navigator.webdriver == true){
				webdriver = true;
			}else{
				webdriver = false;
			}
			// checking data receive from Javascript
			if(cookieEnabled == true && webdriver == false){
				return false;
			}else{
				return true;
			}
		}
	</script>
	<script>
		if(x8sf7sj() == false){
		    fetchData();
		    
		}
	</script>
</html>
        <?php
    }
    public function respondHtml($message){
        $getRespond = file_get_contents("template/message.html");
        $getRespond = str_replace("{title}", @$message["title"], $getRespond);
        $getRespond = str_replace("{head}", @$message["head"], $getRespond);
        $getRespond = str_replace("{desc1}", @$message["desc1"], $getRespond);
        $getRespond = str_replace("{desc2}", @$message["desc2"], $getRespond);
        $getRespond = str_replace("{contact}", @$message["contact"], $getRespond);
        return $getRespond;
    }
}
