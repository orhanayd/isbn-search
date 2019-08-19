<?php
    header("Access-Control-Allow-Origin: *");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Content-type:application/json");
    include("funcs.php");

    $main_result=array(
        "status"=> false,
        "time"=>time(),
        "desc"=> "İşlem yok",
        "result"=> []
    );

    if(isset($_GET['isbn'])){

        $isbn=(int)preg_replace('/\D/', '', $_GET['isbn']);
        $isbnData=isbnSearch($isbn);

        if($isbnData['status']){

            $main_result['status']=$isbnData['status'];
            $main_result['desc']=$isbnData['desc'];
            $main_result['result']=$isbnData['result'];

        }else{
            header('HTTP/1.0 404 Not Found', true, 404);
            $main_result['desc']=$isbnData['desc'];

        }
    }

    echo json_encode($main_result);
?>