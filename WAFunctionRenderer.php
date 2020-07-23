<?php 
    $url = "";
    $hash = "";
    $urlsvg = array();
    $error = false;
    // https://ryansechrest.com/2012/07/send-and-receive-binary-files-using-php-and-curl/
    function DownloadData($url){
        $resource = curl_init();
        curl_setopt($resource, CURLOPT_URL, $url);
        curl_setopt($resource, CURLOPT_HEADER, 1);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($resource, CURLOPT_BINARYTRANSFER, 1);
        $file = curl_exec($resource);
        curl_close($resource);
        return substr(explode("\n\r", $file, 2)[1], 1);
    }
    function FormatFunction($functionstr){
        $functionstr = str_replace("<>", "\\ne", $functionstr);
        $functionstr = str_replace("ca", "\approx", $functionstr);
        $functionstr = str_replace("<=", "\le", $functionstr);
        $functionstr = str_replace(">=", "\ge", $functionstr);
        $functionstr = str_replace("pi", "\pi", $functionstr);
        $functionstr = str_replace("+-", "\pm", $functionstr);
        $matches = array();
        preg_match_all("/(\(([0-9]*)\/([0-9]*)\))/", $functionstr, $matches, PREG_OFFSET_CAPTURE); // https://regex101.com/        
        for($i = 0; $i < count($matches[0]); $i++){
            $functionstr = str_replace($matches[0][$i][0], "\\frac{".$matches[2][$i][0]."}{".$matches[3][$i][0]."}", $functionstr);
        }
        $functionstr = str_replace("(", "{(", $functionstr);
        $functionstr = str_replace(")", ")}", $functionstr);
        return $functionstr;
    }
    function CheckFunctionInput($functionstr, &$data){
        $resource = curl_init();
        curl_setopt($resource, CURLOPT_URL, "https://wikimedia.org/api/rest_v1/media/math/check/tex");
        curl_setopt($resource, CURLOPT_HEADER, 1);
        curl_setopt($resource, CURLOPT_POST, 1);
        curl_setopt($resource, CURLOPT_POSTFIELDS, "q=".urlencode(FormatFunction($functionstr)));
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($resource, CURLOPT_BINARYTRANSFER, 1);
        $resp = curl_exec($resource);
        curl_close($resource);
        $file_array = explode("\n\r", $resp, 2);
        $header_array = explode("\n", $file_array[0]);
        foreach($header_array as $header_value) {
            $header_pieces = explode(':', $header_value);
            if(count($header_pieces) == 2) {
                $headers[$header_pieces[0]] = trim($header_pieces[1]);
            }
        }
        $json = json_decode(substr($file_array[1], 1));
        if(isset($json->success) && $json->success == "true"){            
            $data = $headers['x-resource-location'];
            return true;
        }else{
            return false;
        }
    }
    if(isset($_GET['d'])){
        header('Content-Description: File Transfer');
        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="function.png"');
        echo DownloadData("https://wikimedia.org/api/rest_v1/media/math/render/png/".$_GET['d']);
        exit;
    }
    if(isset($_GET['f'])){
        $data = null;
        $functions = explode("\r\n", $_GET['f']);
        //$function = preg_replace("", "");        
        foreach($functions as $func){
            if(CheckFunctionInput($func, $data)){
                $url = "https://wikimedia.org/api/rest_v1/media/math/render/png/".$data;
                array_push($urlsvg, "https://wikimedia.org/api/rest_v1/media/math/render/svg/".$data);
                $hash = $data;
            }else{
                $error = true;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <title>WhatsApp Math Function Renderer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <base target="_blank" />
    <style>
        body{
            margin:0px;
            padding:0px;
            height:100vh;
            font-family:'Segoe UI', Verdana, sans-serif;
            display: flex; /* https://css-tricks.com/snippets/css/a-guide-to-flexbox/ */
            flex-direction:column;
        }
        header{
            width:100%;
            padding:20px 10px;
            box-sizing:border-box;
            background:#273443;
            position:relative;
        }
        header *{
            margin:0px;
        }
        header h1{
            font-size:25px;
            color:white;
            margin-left:70px;
        }
        header h2{
            font-size:20px;
            color:rgba(255, 255, 255, 0.7);
            margin-left:70px;
        }
        header svg{
            width:60px;
            height:60px;
            position:absolute;
            top:50%;
            left:10px;
            transform:translate(0px, -50%);
            border-radius:50%;
        }
        a{
            text-decoration:none;
            cursor:pointer;
            color:rgb(133, 156, 255);
        }
        a:hover{
            text-decoration:underline;
        }
        content{
            width:100%;
            flex-grow:1;
            justify-content:center;

            display:flex;
        }
        content h3{
            color:red;
            text-align:center;
        }
        content *{
            align-self:center;
        }
        content .image_container {
            width:90%;
            max-width:300px;
            overflow:auto;
        }
        content .image_container img{            
            max-height:100px;
            display:block;
            width:100%;
            margin:20px 0px;
        }
        form{
            width:80%;
            max-width:300px;
            text-align:center;
        }
        form *{
            display:block;
            width:100%;
            box-sizing:border-box;
            margin:7px 0px;
        }
        form input[type=text], form textarea{
            border:3px solid #FF5722;
            border-radius:5px;
            padding:5px 13px;
            border-top-color:#FF5722;
            border-left-color:#FF5722;
            border-bottom-color:#FFC107;
            border-right-color:#FFC107;
            text-align:left;
            resize:vertical;
            height:300px;
        }
        form input[type=submit]{
            border:3px solid #FF5722;
            border-radius:21px;
            border-top-color:#FF5722;
            border-left-color:#FF5722;
            border-bottom-color:#FFC107;
            border-right-color:#FFC107;
            padding:5px;
            cursor:pointer;
            background:#F4511E;
        }
        form input[type=submit]:hover{
            background:#FFE082;
        }
        footer {
            width: 100%;
            background: #232f3c;
            color: rgba(255, 255, 255, 0.6);
            padding:5px 10px;
            box-sizing:border-box;
        }
        footer a{
            color:rgb(202, 212, 255);
            
        }

        .share{
            border-radius:50%;
            width:50px;
            height:50px;
        }
        .whatsapp{
            background-image:url("");
        }
    </style>
    <?php if($url != ""){ ?>
    <meta property="og:title" content="<?php echo $_GET['f']; ?>" />
    <meta property="og:image" itemprop="image" content="<?php echo $url; ?>" />
    <!-- CORS ?! --> 
    <!--<meta property="og:image" itemprop="image" content="<?php echo "https://ha.kurzweb.de/".basename(__FILE__); ?>?d=<?php echo $hash; ?>" />-->
    <meta property="og:type" content="website" />
    <meta property="og:description" content="Achtung Mathe Funktion!" />
    <?php } ?>
</head>
<body>
    <header>
        <h1>WhatsApp Math Function Renderer</h1>
        <h2>Sende formatierte Mathe-Funktionen mit einem Messenger deiner Wahl</h2>
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
             viewBox="-5 -5 50 50" width="39" height="39" style="enable-background:new 0 0 39 39;" xml:space="preserve">
            <style type="text/css">
                .st0{fill:#00E676;}
                .icon_logo_white{fill:#FFFFFF;}
            </style>

            <path class="st0" d="M10.7,32.8l0.6,0.3c2.5,1.5,5.3,2.2,8.1,2.2l0,0c8.8,0,16-7.2,16-16c0-4.2-1.7-8.3-4.7-11.3
                c-3-3-7-4.7-11.3-4.7c-8.8,0-16,7.2-15.9,16.1c0,3,0.9,5.9,2.4,8.4l0.4,0.6l-1.6,5.9L10.7,32.8z"/>
            <path class="icon_logo_white" d="M32.4,6.4C29,2.9,24.3,1,19.5,1C9.3,1,1.1,9.3,1.2,19.4c0,3.2,0.9,6.3,2.4,9.1L1,38l9.7-2.5
                c2.7,1.5,5.7,2.2,8.7,2.2l0,0c10.1,0,18.3-8.3,18.3-18.4C37.7,14.4,35.8,9.8,32.4,6.4z M19.5,34.6L19.5,34.6c-2.7,0-5.4-0.7-7.7-2.1
                l-0.6-0.3l-5.8,1.5L6.9,28l-0.4-0.6c-4.4-7.1-2.3-16.5,4.9-20.9s16.5-2.3,20.9,4.9s2.3,16.5-4.9,20.9C25.1,33.8,22.3,34.6,19.5,34.6
                z M28.3,23.5L27.2,23c0,0-1.6-0.7-2.6-1.2c-0.1,0-0.2-0.1-0.3-0.1c-0.3,0-0.5,0.1-0.7,0.2l0,0c0,0-0.1,0.1-1.5,1.7
                c-0.1,0.2-0.3,0.3-0.5,0.3h-0.1c-0.1,0-0.3-0.1-0.4-0.2l-0.5-0.2l0,0c-1.1-0.5-2.1-1.1-2.9-1.9c-0.2-0.2-0.5-0.4-0.7-0.6
                c-0.7-0.7-1.4-1.5-1.9-2.4l-0.1-0.2c-0.1-0.1-0.1-0.2-0.2-0.4c0-0.2,0-0.4,0.1-0.5c0,0,0.4-0.5,0.7-0.8c0.2-0.2,0.3-0.5,0.5-0.7
                c0.2-0.3,0.3-0.7,0.2-1c-0.1-0.5-1.3-3.2-1.6-3.8c-0.2-0.3-0.4-0.4-0.7-0.5h-0.3c-0.2,0-0.5,0-0.8,0c-0.2,0-0.4,0.1-0.6,0.1
                l-0.1,0.1c-0.2,0.1-0.4,0.3-0.6,0.4c-0.2,0.2-0.3,0.4-0.5,0.6c-0.7,0.9-1.1,2-1.1,3.1l0,0c0,0.8,0.2,1.6,0.5,2.3l0.1,0.3
                c0.9,1.9,2.1,3.6,3.7,5.1l0.4,0.4c0.3,0.3,0.6,0.5,0.8,0.8c2.1,1.8,4.5,3.1,7.2,3.8c0.3,0.1,0.7,0.1,1,0.2l0,0c0.3,0,0.7,0,1,0
                c0.5,0,1.1-0.2,1.5-0.4c0.3-0.2,0.5-0.2,0.7-0.4l0.2-0.2c0.2-0.2,0.4-0.3,0.6-0.5c0.2-0.2,0.4-0.4,0.5-0.6c0.2-0.4,0.3-0.9,0.4-1.4
                c0-0.2,0-0.5,0-0.7C28.6,23.7,28.5,23.6,28.3,23.5z"/>    
        </svg>
    </header>
    <content>
        <?php if(count($urlsvg) != 0){ ?>
        <div class="image_container">
        <?php for($i = 0; $i < count($urlsvg); $i++){ ?>
        <img src="<?php echo $urlsvg[$i]; ?>" />
        <?php } ?>
        </div>
        <!--<a class="share whatsapp" href="https://wa.me/?text=urlencodedtext">Per Whats-App Teilen</a>-->
        <?php }elseif($error){ ?>
        <form method="get" target="_self">
            <h3>Mit der Funktion ist wohl etwas falsch!</h3>
            <textarea type="text" name="f" value="<?php echo $_GET['f']; ?>"></textarea>
            <input type="submit" />
        </form>
        <?php }else{ ?>        
        <form method="get" target="_self">
            <textarea type="text" name="f" placholder="x^2" autocomplete="off" ></textarea>
            <input type="submit" />
            <a href="https://en.wikipedia.org/wiki/Help:Displaying_a_formula">Formatierungshilfe</a>
        </form>
        <?php } ?>
    </content>
    <footer>
        <div>
            Dieses Projekt nutzt die Wikimedia REST-api. <a href="https://github.com/ShortDevelopment/WhatsApp-Math-Function-Renderer">Mehr Details auf GitHub</a>
        </div>
        <div>
            &copy; <?php echo date("Y"); ?> <a href="https://shortdevelopment.github.io/">Lukas Kurz</a> &amp; <a href="https://www.wikimedia.org/">Wikimedia</a>
        </div>
    </footer>
</body>
</html>