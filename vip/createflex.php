<?php

$alt_text = $_POST['alt_text'];

$json_text = json_decode($_POST['json_text'], true);
$fff = [
    "type" => "box",
    "layout" => "vertical",
    "contents" => [
        [
            "type"=> "image",
            "url"=> "https://tectony.co.th/wp-content/uploads/flexcards/Boss/F1111.png",
            "size"=> "full",
            "animated"=> true,
            "offsetBottom"=> "100px",
            "action"=> [
              "type"=> "uri",
              "label"=> "Line",
              "uri"=> "https://page.line.me/yql4546f?openQrModal=true"
            ],
            "aspectRatio"=> "1:1",
        ]
    ],
    "height" => "80px",
];
//print_r($json_text);
if($json_text['type'] == 'bubble'){

    if (isset($json_text['footer']) && isset($json_text['footer']['contents'])) {
        array_push($json_text['footer']['contents'],$fff);
    }else{
        $json_text['footer'] = [];
        $json_text['footer']['type'] = "box";
        $json_text['footer']['layout'] = "vertical";
        $json_text['footer']['spacing'] = "sm";
        $json_text['footer']['contents'] = [$fff];
    }
    $contents = $json_text;
    //echo "<script>console.log(" . json_encode($json_text) . ");</script>";
}else{

    $contents = $json_text['contents'];
    $count = count($contents);  
    for ($i = 0; $i < $count; $i++) { 
        $count2 = count($contents[$i]);
        
        if (isset($contents[$i]['footer'])) {
            
            array_push($contents[$i]['footer']['contents'], $fff);
            //array_push($contents[$i]['footer']['contents'], $fff);
            //$contents[$i]['footer']['contents'] = $fff;
        }else{
            $contents[$i]['footer'] = [];
            $contents[$i]['footer']['type'] = "box";
            $contents[$i]['footer']['layout'] = "vertical";
            $contents[$i]['footer']['spacing'] = "sm";
            $contents[$i]['footer']['contents'] = [$fff];
            
           // array_push($contents[$i]['footer']['contents'], $fff);
        }
        //echo "<script>console.log(" . isset($contents[$i]['footer']) . ");</script>";
        // ตรวจสอบว่ามี 'footer' และ 'contents' หรือไม่
       // if (isset($ddd[$i]['footer'])) {
           // 
       // }
       
        // แสดงผล
       
    }
    $json_text['contents'] = $contents;
    
    //
}
echo "<script>console.log(" . json_encode($json_text) . ");</script>";
?>
<div class="loader" id='loadingmessage'>
    <img
        src='https://preview.redd.it/gif-loading-files-update-ark-os-first-before-using-v0-ubbi1p7z7euc1.gif?width=640&auto=webp&s=284413e4a741aa0bfc7f562955b6927dddbe3b3e' />
</div>
<style>
/* HTML: <div class="loader"></div> */
.loader {
    background-color: 'black';
    max-width: max-content;
    margin: auto;
    margin-top: 50%
}
</style>
<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<script>
async function sendShare() {

    const result = await liff.shareTargetPicker([{
        "type": "flex",
        "altText": '<?php echo $alt_text; ?>',
        "contents": <?php echo json_encode($json_text); ?>
    }])

    if (result) {
        alert(`แชร์ข้อความ Flex Message สำเร็จแล้ว!`)
        liff.logout();
        liff.closeWindow();
    } else {
       // liff.closeWindow();
         liff.logout();
    }
}
async function main() {
    await liff.init({ liffId: "2006438841-7A2RNRKG" });

    if (liff.isLoggedIn()) {
        sendShare();
    } else {
        liff.login();
        liff.ready.then(() => { // Ensure the app is ready
            sendShare();
        });
    }
}
document.addEventListener("DOMContentLoaded", async () => {
    await main();
});
</script>