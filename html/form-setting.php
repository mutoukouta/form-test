<?php
// 送信メールの方式
$formConfig["mail"] = array(
	"userAddress"  => "", 					// 確認メール送信先指定（メールアドレス or #{input[name]}#でフォーム入力の内容から取得）
	"adminAddress" => "smutou@nssx.co.jp", 	// フォーム送信先メールアドレス
	"fromName"     => "テスト送信者", 				// 送信元表示名
	"fromAddress"  => "include@test.com", 	// 送信元アドレス：送信に使うメールアドレス
	"replyAddress" => "include@test.com", 	// 返信先アドレス：返信設定のアドレス、fromと同じなら指定不要
	"sendType"     => "smtp", 						// 送信方式：smtp or sendmail
	"isHTML"       => false, 						// HTMLメールで送るか
	"smtpHost"     => "smtp.lolipop.jp", 		// SMTPサーバー
	"smtpUser"     => "mailtest@ex-code.sub.jp", 	// SMTPユーザー名
	"smtpPass"     => "Mail_Test_1234", 				// SMTPパスワード
	"smtpPort"     => 465, 							// SMTPポート番号（tlsの場合は465や587）
	"smtpAuth"     => true, 						// SMTP authenticationを有効化
	"smtpSecure"   => "ssl",						// 暗号化を有効（tls or ssl）無効の場合はfalse
	"smtpCharSet"  => "utf-8",						// メールの文字コード指定	
);

// サンクスページ指定：空の場合は送信状況メッセージのみ
$formConfig["thanksUrl"] = "";						

// メールテンプレート、指定がない or ファイルパスが間違っている場合は送信されない。
$formConfig["userMail"]  = __DIR__."html/mail-template/user.tpl";	// 問合者へのメールテンプレート
$formConfig["adminMail"] = __DIR__."html/mail-template/admin.tpl";	// 管理者へのメールテンプレート

// フォームを特定する要素名
$formConfig["formName"] = "form"; 

// グーグルリキャプチャーキーの設定：指定した場合はリキャプチャー有効にする。（キー自体の有効性かは別問題）
$formConfig["googleReCaptchaKey"]        = "6Lf6JgsoAAAAAABIkSkkZxOneDX5zVp9BM8oCMmI"; 		// キーID
$formConfig["googleReCaptchaSeacretKey"] = "6Lf6JgsoAAAAAAykLSAs0GGr4k5_1G1EIJFdPcyS"; 		// 秘密鍵


// -----------------------------------------------------------------
// バリデーションルールの記載(jQuery.validateの形式)
// jQueryValidateではrule内のpatternは["]無しだがここでは必要
// -----------------------------------------------------------------
$formConfig["validate"] = array(
	"rules" => array(
		"purpose" => array( "required" => true ),
		"name" =>    array( "required" => true ),
		"email" =>   array( "required" => true, "email" => true ),
		"tel" =>     array( "pattern" => "/(^0\d{1,3}-\d{1,4}-\d{4}$|^0\d{9,10}$)/i" )
	),
	"messages" => array(
		"purpose" => "お問い合わせ内容を選択してください。",
		"name" =>    "お名前は必須です",
		"email" => array(
			"required" => "メールアドレスは必須です。",
			"email" => "メールアドレスが正しくありません。"
		),
		"tel" => array(
			"pattern" => "電話番号は形式が正しくありません。"
		)
	)
);

