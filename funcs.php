<?php
    include("env.php");

    $local=false;
    if($_SERVER['REMOTE_ADDR']!=="127.0.0.1"){
        $local=true;
    }

    function getHTMLByID($id, $html) {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $dom->validateOnParse = true;
        $node = $dom->getElementById($id);
        $node=$dom->saveXML($node);
        if(strlen($node) > 0){
            return $node;
        }
        return false;
    }

    function clearText($text){
        return str_replace(array("  ", "\n", "\r"),array("", "", ""), strip_tags($text));
    }

    function clearText2($text){
        $text=str_replace(array("  "),array(""), strip_tags($text));
        $result=[];
        $text = explode("\n", $text);
        foreach($text as $key=>$item){
            if(strlen($item)=="0"){
                unset($text[$key]);
            }
        }
        foreach($text as $key=>$item){
            if(is_numeric($item)){
                $result['isbn']=$item;
            }else{
                if($key===4){
                    $result['author']=$item;
                }elseif($key===5){
                    $result['publisher']=$item;
                }
            }
        }
        return $result;
    }

    function called_times($isbn){
        $isbn=(int)$isbn;
        $manager = new MongoDB\Driver\Manager($_ENV["mongo_conn"]);
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
                array("isbn"=>$isbn),
                array('$inc' => array('called_times' => 1))
        );
        return $manager->executeBulkWrite('isbn.data', $bulk);
    }

    function isbnSave($data){
        global $local;
        if($local){
            return true;
        }
        $manager = new MongoDB\Driver\Manager($_ENV["mongo_conn"]);
        $bulk = new MongoDB\Driver\BulkWrite;
        $data['time']=time();
        $data['visible']=true;
        $data['called_times']=1;
        $bulk->insert($data);
        return $manager->executeBulkWrite('isbn.data', $bulk);
    }

    function isbnGetFromDb($isbn){
        $isbn=(int)$isbn;
        $manager = new MongoDB\Driver\Manager($_ENV["mongo_conn"]);
        $query = new MongoDB\Driver\Query(
            array(
                'isbn'=>$isbn,
                'visible'=>true
            ),
            array(
                'limit'=>1,
                'sort'=>array("_id"=>-1)
            )
        );
        $cursor = $manager->executeQuery('isbn.data', $query);
        $data = $cursor->toArray();
        if(count($data)>0){
            called_times($isbn);
            return $data[0];
        }else{
            return false;
        }
    }

    function isbnSearch($isbn){
        $isbn=(int)$isbn;
        $result = array("status"=>false, "desc"=>"", "result"=>"");
        $dbResult=isbnGetFromDb($isbn); // db den isbn bilgilerini çekiyoruz.
        if($dbResult){ // db de isbn var mı kontrol
            /**
             * isbn verileri db de varsa bilgilieri db den çekiyoruz.
             */
            unset($dbResult->_id, $dbResult->visible, $dbResult->time, $dbResult->called_times); // gereksiz db verileri
            $result['status']=true;
            $result['desc']="Veriler yüklendi, dikkat verilerin doğruluğunu asla kabul etmiyoruz. - from db";
            $result['result']=$dbResult;


        }else{
            /**
             * isbn verileri db de yoksa api ya bağlanıp çekiyoruz...
             */
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $_ENV['isbn_api'].$isbn."&sira=src");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
            $headers = array();
            $headers[] = 'Connection: keep-alive';
            $headers[] = 'Pragma: no-cache';
            $headers[] = 'Cache-Control: no-cache';
            $headers[] = 'Upgrade-Insecure-Requests: 1';
            $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36';
            $headers[] = 'Sec-Fetch-Mode: navigate';
            $headers[] = 'Sec-Fetch-User: ?1';
            $headers[] = 'Dnt: 1';
            $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3';
            $headers[] = 'Sec-Fetch-Site: none';
            $headers[] = 'Referer: '.$_ENV['isbn_api'].$isbn."&sira=src";
            $headers[] = 'Accept-Encoding: gzip, deflate, br';
            $headers[] = 'Accept-Language: en-US,en;q=0.9,tr;q=0.8,ru;q=0.7,zh-CN;q=0.6,zh;q=0.5,fr;q=0.4,pt;q=0.3,la;q=0.2,az;q=0.1,nl;q=0.1';
            $headers[] = 'Cookie: laravel_session=bbed6c267bf811bc29bf3f5e1ed1437e46844370; XSRF-TOKEN=eyJpdiI6IjNydG1VdmhIaTJEdnZZb1U1NmZaUnc9PSIsInZhbHVlIjoiNThNZTFzXC82NVpBTWVwZkVkQzZkM0tXT01ibzd5N3kxc3FCXC94NStPZ0tUcDFEMDFoTGs1QStuTSsrbGlrVFdmVDM1RFFtTzRmSisxQ0hiYUlWVWE0dz09IiwibWFjIjoiMjM5NDc5M2NmMThlZGQyYzUyZTVlMTNkNDRlZGVmMDMyNDI4YzdlM2RmNDAxNDIxYzAxMDEzZjBmMDE1MmY4MyJ9';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $resultCurl = curl_exec($ch);
            if (curl_errno($ch)) {
                $result['desc']="API HATA !!";
            }else{
                /**
                 * buralar çok karışık çünkü verileri aldığımız yer çok karışık bro :)
                 */
                $check_one = getHTMLByID('grid', $resultCurl);
                if($check_one){
                    $replaced_result = str_replace(array("class", "col3-change col-sm-3 col-lg-3 col-md-3 col-xs-3"), array("id", "bookDiv"), $check_one);
                    $check_two = getHTMLByID("bookDiv", $replaced_result);
                    if($check_two){
                        $others = clearText2(getHTMLByID("hover-image-area", $check_two));
                        if(count($others)<1){
                            $result['desc']="Lütfen daha sonra tekrar deneyiniz. 1";
                        }else{
                            $result['status']=true;
                            $result['desc']="Veriler yüklendi, dikkat verilerin doğruluğunu asla kabul etmiyoruz. - from api";
                            // parse start
                            $result['result']=array(
                                "title"=>clearText(getHTMLByID("autherText text-center cursorPointer", $check_two)),
                                "author"=>$others['author'],
                                "publisher"=>$others['publisher'],
                                "isbn"=>(int)$others['isbn']
                            );
                            isbnSave($result['result']);
                        }
                        
                    }else{
                        $result['desc']="Sonuç bulanamadı. Doğru bir ISBN numarası girdiğinden emin olunuz veya kayıtlarımızda bulunamadı.";
                    }    
                }else{
                    $result['desc']="Lütfen daha sonra tekrar deneyiniz. 2";
                }
    
            }
            curl_close($ch);
        }

        return $result;
    }
?>