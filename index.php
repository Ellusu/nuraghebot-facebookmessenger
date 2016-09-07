<?php
/**
 *  titolo: nuraghebot-facebookmessenger
 *  autore: Matteo Enna (http://matteoenna.it)
 *  licenza: GPL3
 **/

    header('Content-Type: text/html; charset=utf-8');

    $access_token = "<access_token>";
    $verify_token = "<verify_token>";
    $hub_verify_token = null;
    
    
    
    $content = file_get_contents('php://input');
    
    if(!$content || $content==NULL || $content=="" || !is_array(json_decode(file_get_contents('php://input'), true))) die();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if(empty($input['entry'][0]['messaging'][0]['message'])) die();
    
    $sender = $input['entry'][0]['messaging'][0]['sender']['id'];
     
    
    
    if(array_key_exists('text',$input['entry'][0]['messaging'][0]['message'])) {
        $message = $input['entry'][0]['messaging'][0]['message']['text'];
    } elseif(is_array($input['entry'][0]['messaging'][0]['message']['attachments'][0]['payload'])) {
        $message = array(
                        'latitude' => $input['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['coordinates']['lat'],
                        'longitude' => $input['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['coordinates']['long'] 
                         
                         );
    }else{
        send($access_token, $sender, "Formato non valido");
        die;
    }
    
    
    controller($access_token, $sender, $message, $mode);
    
    
    function controller($access_token, $sender, $message){        
    
        if(strlen($message)<4 && !is_array($message)){
            send($access_token, $sender, "Zona non trovata");
        }
        
        $risultati =array();
        
        $simple = file_get_contents("data/nuraghe.csv");
        
        $righe=explode(chr(10),$simple);
        if (is_array($message))
        {
            foreach($righe as $s){
                $response =array();
                $col = explode(';',$s);
                //echo "---".$col[6]."<br>";
                $col[10]=str_replace('"','',$col[10]);
                $col[3]=str_replace('"','',$col[3]);
                
                $lat   =  str_replace('"', '', $col[3]);
                $long  =  str_replace('"', '', $col[10]);
                
                if(($col[10]<$message["latitude"]+0.02 && $col[10]>$message["latitude"] - 0.02) && ($col[3]<$message["longitude"]+0.02 && $col[3]>$message["longitude"] - 0.02)){ /*|| stripos($col[2],$bidda) || stripos($col[5],$bidda)*/
                    $response = array (
                        'id'=>  str_replace('"', '', $col[0]),
                        'tipo'=>  str_replace('"', '', $col[9]),
                        'comune'=>  str_replace('"', '', $col[6]),
                        'lat'=>  str_replace('"', '', $col[10]),
                        'long'=>  str_replace('"', '', $col[3]),
                        'nome'=>  str_replace('"', '', $col[8]),
                        'provincia'=>  str_replace('"', '', $col[5]),
                        'zona'=>  str_replace('"', '', $col[2]),   
                        'gmaps'=>  'https://www.google.com/maps/place/'.$long.'+'.$lat.'/@'.$long.','.$lat.',15z'
                    );
                    $risultati[]=$response;
                    
                }
                
            }
        }else{
            foreach($righe as $s){
                $response =array();
                $col = explode(';',$s);
                
                $lat   =  str_replace('"', '', $col[3]);
                $long  =  str_replace('"', '', $col[10]);
                
                if(stripos($col[6],$message) /*|| stripos($col[2],$bidda) || stripos($col[5],$bidda)*/){
                    $response = array (
                        'id'=>  str_replace('"', '', $col[0]),
                        'tipo'=>  str_replace('"', '', $col[9]),
                        'comune'=>  str_replace('"', '', $col[6]),
                        'lat'=>  $lat,
                        'long'=>  $long,
                        'nome'=>  str_replace('"', '', $col[8]),
                        'provincia'=>  str_replace('"', '', $col[5]),
                        'zona'=>  str_replace('"', '', $col[2]),   
                        'gmaps'=>  'https://www.google.com/maps/place/'.$long.'+'.$lat.'/@'.$long.','.$lat.',15z'
                    );
                    $risultati[]=$response;
                    
                }
                
            }
        }
        $tot = count($risultati);
        if($tot<0) $tot=0;
        send($access_token, $sender, "Risultati trovati ".$tot);
        
        if($tot >200){
            send($access_token, $sender, "Consigliamo di usare una parola chiave più precisa");
        }
        
        $testo = '';
        $acapo=" - ";
        foreach ($risultati as $k => $res){
            $testo = $res['id'].' - '.$res['nome'];
            $testo .= $acapo;
            $testo .= $res['comune'].' ('.$res['provincia'].')';
            $testo .= $acapo;
            $testo .= $res['gmaps'];
            send($access_token, $sender, $testo);
            $testo = "";
        }
        
        send($access_token, $sender, "Fine");
        die;
    }

    
    
    function send($access_token, $sender, $message) {
        $url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$access_token;


        //Initiate cURL.
        $ch = curl_init($url);
        
        //The JSON data.
        $jsonData = '{
            "recipient":{
                "id":"'.$sender.'"
            },
            "message":{
                "text":"'.$message.'"
            }
        }';
        
        //Encode the array into JSON.
        $jsonDataEncoded = $jsonData;
        
        //Tell cURL that we want to send a POST request.
        curl_setopt($ch, CURLOPT_POST, 1);
        
        //Attach our encoded JSON string to the POST fields.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        
        //Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        
        //Execute the request
        $result = curl_exec($ch);
                
    }
    
    
?>
