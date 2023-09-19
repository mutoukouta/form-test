<?php

require_once("form-setting.php");

// PHPMailer Settings
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once("php-lib/PHPMailer/src/Exception.php");
require_once("php-lib/PHPMailer/src/PHPMailer.php");
require_once("php-lib/PHPMailer/src/SMTP.php");

function token_chk($token){
	global $formConfig;
	$RECAPTUER = $formConfig["googleReCaptchaSeacretKey"];

	// 検証結果での判定
	$url = sprintf('https://www.google.com/recaptcha/api/siteverify?secret=%1$s&response=%2$s', $RECAPTUER, $token);
	$result = file_get_contents($url);
	$chk = json_decode($result);

 
	// 検証結果での判定
	$sts = true;
	if ($chk->success != true){	$sts = false; }	/* トークンエラー */
	if ($chk->score < 0.5){	$sts = false; }		/* スコア低いのは弾く */
 
	/** 処理終了 */
	return $sts;
}

// google reCAPCHAが設定されているか？
if(@$formConfig["googleReCaptchaKey"] && @$formConfig["googleReCaptchaSeacretKey"]){
	$message = null;
	if (isset($_REQUEST["recaptchaToken"]) == true)	{	/* 送信ボタンが押された ? */
		$chkResult = token_chk($_REQUEST["recaptchaToken"]);	/** トークンチェック */
	
		if ($chkResult == true){	/* トークンチェックOK */
			// 必要であれば処理を書く
		}else{
			$returnData = array(
				"result" => false,
				"msg" => "データ送信が正しく出来ませんでした。"
			);
			echo json_encode($returnData, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}
}
// メール送信処理の実行
echo json_encode(MailMain($formConfig), JSON_UNESCAPED_UNICODE);

// 一連処理
function MailMain($formConfig){
	// 固定タグの生成
	$insertTags = array();
	$insertTags = array();
	$insertTags["ClientIP"] = getClientIpAddress();

	// 送信データからタグの生成
	foreach($_POST as $key => $item){
		if(is_array($item)){ $item = implode(" / ", $item); }

		$allPost[] = $key . " : " . $item;
		$insertTags[$key] = $item;
	}
	$insertTags["AllPost"] = implode("\n", $allPost);

	// デフォルト（メール送信が実施されなかった場合）
	$returnData = array(
		"result" => false,
		"msg" => "メール送信に失敗しました。"
	);

	// 問い合わせ者のメール生成処理
	if(file_exists($formConfig["userMail"])){
		$mailSet = array();

		// メールテンプレートへのタグ挿入
		$mailUser = file_get_contents($formConfig["userMail"]);
		foreach($insertTags as $key => $item){
			$mailUser = str_replace("[--".$key."--]", $item, $mailUser);
		}
		$mailUser = preg_replace("/[--[^\]]+--]/i", "", $mailUser);
		$mailUser = explode("\n", $mailUser);

		// メールテンプレートの分解処理
		if( count($mailUser) > 4){
			$mailSet["subject"] = trim($mailUser[1]);
			unset($mailUser[0], $mailUser[1], $mailUser[2]);
			$mailSet["body"]  = trim(implode("\n", $mailUser));
		}else{
			// メールテンプレートが不正：次の処理に進まない
			return array(
				"result" => false,
				"msg" => "メール送信に失敗しました。"
			);
		}

		// メール送信先の設定
		if(preg_match("/^#(.*)#$/i", $formConfig["mail"]["userAddress"], $matches)){
			$mailSet["to"] = $insertTags[$matches[1]];
		}else{
			$mailSet["to"] = $formConfig["mail"]["userAddress"];
		}

		// メール送信実施
		$result = smtpSend($mailSet["to"], $mailSet["subject"], $mailSet["body"], $formConfig["mail"]);
		if(!$result){
			// 結果：次の処理に進まない
			return array(
				"result" => false,
				"msg" => "メール送信に失敗しました。"
			);
		}else{
			// 仮の結果（次の処理に引継ぎ用）
			$returnData = array(
				"result" => true,
				"msg" => "メール送信に完了しました。",
				"redirectURL" => $formConfig["thanksUrl"],
			);
		}
	}

	// 管理者のメール生成処理
	if(file_exists($formConfig["adminMail"])){
		$mailSet = array();

		// メールテンプレートへのタグ挿入
		$mailAdmin = file_get_contents($formConfig["adminMail"]);
		foreach($insertTags as $key => $item){
			$mailAdmin = str_replace("[--".$key."--]", $item, $mailAdmin);
		}
		$mailAdmin = preg_replace("/[--[^\]]+--]/i", "", $mailAdmin);
		$mailAdmin = explode("\n", $mailAdmin);

		// メールテンプレートの分解処理
		if( count($mailAdmin) > 4){
			$mailSet["subject"] = trim($mailAdmin[1]);
			unset($mailAdmin[0], $mailAdmin[1], $mailAdmin[2]);
			$mailSet["body"]  = trim(implode("\n", $mailAdmin));
		}else{
			// メールテンプレートが不正：次の処理に進まない
			return array(
				"result" => false,
				"msg" => "メール送信に失敗しました。"
			);
		}

		// メール送信先の設定
		if(preg_match("/^#(.*)#$/i", $formConfig["mail"]["adminAddress"], $matches)){
			$to = $insertTags[$matches[1]];
		}else{
			$to = $formConfig["mail"]["adminAddress"];
		}

		// メール送信実施
		$result = smtpSend($to, $mailSet["subject"], $mailSet["body"], $formConfig["mail"]);
		if(!$result){
			// 結果：次の処理に進まない
			return array(
				"result" => false,
				"msg" => "メール送信に失敗しました。"
			);
		}else{
			// 仮の結果（次の処理に引継ぎ用）
			$returnData = array(
				"result" => true,
				"msg" => "メール送信に完了しました。",
				"redirectURL" => $formConfig["thanksUrl"],
			);
		}
	}

	// 最終結果の出力
	return $returnData;
}

// メール送信本体
function smtpSend($to, $subject, $body, $mailConfig){
	// 文字エンコードを指定
	mb_language('uni');
	mb_internal_encoding('UTF-8');

	$mail = new PHPMailer();
	$mail->CharSet    = @$mailConfig["smtpCharSet"] ? $mailConfig["smtpCharSet"] : "utf-8";
	$mail->IsHTML((@$mailConfig["isHTML"] == true));

	// SMTPサーバの設定
	$mail->isSMTP();                          		// SMTPの使用宣言
	$mail->Host       = $mailConfig["smtpHost"];	// SMTPサーバーを指定
	$mail->SMTPAuth   = @$mailConfig["smtpAuth"]    ? $mailConfig["smtpAuth"] : true;	// SMTP authenticationを有効化
	$mail->Username   = $mailConfig["smtpUser"];	// SMTPサーバーのユーザ名
	$mail->Password   = $mailConfig["smtpPass"];	// SMTPサーバーのパスワード
	$mail->SMTPSecure = @$mailConfig["smtpSecure"]  ? $mailConfig["smtpSecure"] : "tls";	// 暗号化を有効（tls or ssl）無効の場合はfalse
	$mail->Port       = $mailConfig["smtpPort"];	// TCPポートを指定（tlsの場合は465や587）

	// 送受信先設定（第二引数は省略可）
	$mail->setFrom(@$mailConfig["fromAddress"], @$mailConfig["fromName"]);	// 送信者
	$mail->addAddress($to);													// 宛先
	if(@$mailConfig["replyAddress"]){
		$mail->addReplyTo($mailConfig["replyAddress"]); // 返信先
	}
	if(@$mailConfig["ccAddress"]){
		$mail->addCC($mailConfig["ccAddress"]); 		// CC宛先
	}
	// $mail->Sender = 'return@example.com'; // Return-path
	
	// 送信設定
	$mail->Subject  = $subject;
	$mail->Body     = $body;
	// $mail->SMTPDebug = 2;

	return $mail->Send();
	// if(!$mail->Send()){
	// 	echo "Error : " . $mailer->ErrorInfo;
	// }
}


// IP取得処理
function getClientIpAddress() {
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $xForwardedFor = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if (!empty($xForwardedFor)) {
            return trim($xForwardedFor[0]);
        }
    }
    if (isset($_SERVER['REMOTE_ADDR'])) {
        return (string)$_SERVER['REMOTE_ADDR'];
    }
    return "";
}

