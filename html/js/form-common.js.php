<?php 
include("../form-setting.php"); 
function setJsonDepth($data, $ansKeys = array(), $depth = 0){
	$first = (count($ansKeys) == 0);
	$indent = str_repeat("    ", $depth + 1);
	foreach($data as $key => $item){
		if($first){
			$ansKeys = [];
		}
		if(is_array($item)){
			$ansKeys[] = $key;
			$item = setJsonDepth($item, $ansKeys, $depth + 1);
			$data[$key] = $indent.$key.' : {' . "\n" . implode(", \n", $item) . "\n". $indent.'}';
		}else{
			$ansKeys[] = $key;
			if( !(@$ansKeys[0] == "rules" && $key == "pattern") ){
				$item = setJsonValue($item);
			}
			$data[$key] = $indent.$key.' : '.$item;
		}
	}
	return $data;
}

function setJsonValue($item){
	switch(gettype($item)){
		case "boolean" : $item = $item ? 'true' : 'false'; break;
		case "string"  : $item = '"'.$item.'"'; break;
		case "integer" : break;
	}
	return $item;
}
$data = setJsonDepth($formConfig["validate"], array());
$data = "{\n" . implode(", \n", $data) . "\n}";
// echo "<pre>";
// echo $data;
// exit;
?>
var formName = "<?=$formConfig["formName"]?>";

// 確認画面の生成処理
function confirmGenerate(){
	var Confirm = document.querySelector(formName).cloneNode(true);
	$(Confirm).find("input[value='SEND MESSAGE']").remove();
	$(Confirm).find("input[type='hidden']").remove();
	$(Confirm).find("button[type=submit]").remove();
	val = $(Confirm).find("[name=purpose]:checked").val();
	$(Confirm).find("[name=purpose]").parent().parent().parent().removeClass("radio");
	$(Confirm).find("[name=purpose]").parent().parent().parent().find(".radio").removeClass("radio");
	$(Confirm).find("[name=purpose]").parent().parent().replaceWith('<div class="inner" style="white-space:pre;padding-left:1em;">' + val + '</div>');
	$(Confirm).find("input, textarea").replaceWith(function() {
		$(this).replaceWith('<div class="inner" style="white-space:pre;padding-left:1em; text-align: left;">' + $(this).val() + '</div>')
	});
	return "以下の内容で送信します。<br>よろしいでしょうか？" + "<form>" + ($(Confirm).html()) + "</form>";
}

$().ready(function() {
	$.validator.setDefaults({
		errorElement: "div",
		submitHandler: function() {
			var Confirm = confirmGenerate();

			Swal.fire({
				title: '送信前確認',
				html : Confirm,
				showCancelButton: true,
				confirmButtonText: '送信',
				cancelButtonText: '戻る',
				reverseButtons: true,
				width : '80%',
				showClass: {
					popup: 'animated  faster',
					// icon: 'animated heartBeat delay-1s'
				},
				hideClass: {
					popup: 'animated fadeOutUp faster',
					// icon: 'animated heartBeat delay-1s'
				},
			}).then((result) => {
				// 送信ボタンの検知
				if (result.isConfirmed) {
					<?php if(@$formConfig["googleReCaptchaKey"] && @$formConfig["googleReCaptchaSeacretKey"]){ ?>

					// googleReCaptchaStart
					grecaptcha.enterprise.ready(function() {
						grecaptcha.enterprise.execute('<?=$formConfig["googleReCaptchaKey"]?>', {action: 'submit'}).then(function(token){
							// Add your logic to submit to your backend server here.
							var mailForm = document.querySelector(formName);
							var recaptchaToken = mailForm.querySelector('[name="recaptchaToken"]');
							if(!recaptchaToken){
								recaptchaToken = document.createElement("input");
								recaptchaToken.setAttribute("name", "recaptchaToken");
								recaptchaToken.setAttribute("type", "hidden");
								mailForm.appendChild(recaptchaToken);
							}
							recaptchaToken.value = token;
					<?php } ?>

							// フォーム送信処理
							$.ajax({
								url : $(formName).attr('action'),
								type: $(formName).attr('method'),
								data: $(formName).serialize(),
							})
							.done(function(data) {	// 送信成功時の処理
								var result = JSON.parse(data)
								if(result.result){
									if(result.redirectURL){
										location.href = result.redirectURL;
									}else{
										Swal.fire({
											icon: 'success',
											title: result.msg,
										});
									}
								}else{
									Swal.fire({
										icon: 'error',
										title: result.msg,
									})
								}
							})
							.fail(function() {	// 送信失敗時の処理
								Swal.fire({
									icon: 'error',
									title: '送信失敗しました。',
								})
							});
					<?php if(@$formConfig["googleReCaptchaKey"] && @$formConfig["googleReCaptchaSeacretKey"]){ ?>

						});
					});
					<?php } ?>

				}
			})
		}
	});

	// validate the comment form when it is submitted
	// validate signup form on keyup and submit
	$(formName).validate(<?=$data?>);	
});	