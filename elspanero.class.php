<?php
/*
 *
 * ElSpanero
 *
 * @author: "Arthur Dias dos Santos Lasso [ArTDsL]";
 * @file_name: "elspanero.class.php";
 * @date_creation: "2021-10-19";
 * @last_update: "2021-10-19";
 *
 *
 * \\ EN-US //
 * (c) 2021. Arthur  Dias  dos  Santos  Lasso  [ArTDsL].  This
 * project  is  available  100% free (no-cost)  at  GitHub (on
 * link:  https://github.com/ArTDsL/elspanero).   I made  this
 * code  to prove a point to  one of my friends  about  e-mail
 * span, that  means this is  ONLY FOR EDUCATIONAL  PROPOUSES,
 * I DO NOT  HELP,  ENDORSE,  USING  THIS FOR  ILLEGAL STUFFS,
 * IF YOU WANT TO DO THIS, DO AT YOUR OWN RISK / CONSEQUENCES!
 *
 *
 * \\ PT-BR //
 * (c) 2021. Arthur Dias dos Santos Lasso [ArTDsL]. Esse
 * projeto está disponível gratuitamente no GitHub (você
 * pode acessar o mesmo pelo link: https://github.com/Ar
 * TDsL/elspanero).  Esse projeto foi feito para  provar
 * um ponto sobre spans de e-mail a um amigo, nesse caso
 * o mesmo é para USO EDUCACIONAL!  Eu não  ajudo,  faço
 * apologia, compactuo com o uso desse código  para fins
 * ilegais, caso você queira fazer isso faça assumindo a
 * responsábilidade e risco pelos seus atos.
 *
 */
ini_set('memory_limit', '-1');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

Class ElSpanero_Class{
	public function Script_GetConfigFile(){
		if(file_exists('config.json')){
			$json_file = file_get_contents('config.json');
			$json = json_decode($json_file, true);
			return $json_file;
		}else{
			return throw new Exception("ElSpanero_ERROR :: Configuration 'config.json' file won't exist...");
			exit;
		}
	}
	public function Script_GetEmailAccounts(){
		if(file_exists('accounts.json')){
			$json_file = file_get_contents('accounts.json');
			$json = json_decode($json_file, true);
			return $json_file;
		}else{
			return throw new Exception("ElSpanero_ERROR :: File 'accounts.json' won't exist...");
			exit;
		}
	}
	public function Script_GetEmailList(){
		if(file_exists('mails_toSend.json')){
			$json_file = file_get_contents('mails_toSend.json');
			$json = json_decode($json_file, true);
			return $json_file;
		}else{
			return throw new Exception("ElSpanero_ERROR :: File 'mails_ToSend.json' won't exist...");
			exit;
		}
	}
	public function Script_GetHTMLEmailTemplate(){
		if(file_exists('template.template.html')){
			$template = file_get_contents('template.template.html');
			return $template;
		}else{
			return throw new Exception("ElSpanero_ERROR :: HTML Template won't exist...");
			exit;
		}
	}
	public function Script_GetTxtEmailTemplate(){
		if(file_exists('template.template.txt')){
			$template = file_get_contents('template.template.txt');
			return $template;
		}else{
			return throw new Exception("ElSpanero_ERROR :: Text Template won't exist...");
			exit;
		}
	}
	public function Script_GetAttachments(){
		if(file_exists('attachments')){
			$files = scandir('attachments');
			$count = 0;
			$newArray = array();
			foreach($files as $key => $value){
				if($value != "." && $value != ".." && $value != ".DS_Store" && $value != ".DO_NOT_DELETE"){
					$count++;
					$newArray[$count] = 'attachments/'.$value;
				}
			}
			return json_encode($newArray, JSON_PRETTY_PRINT);
		}else{
			return throw new Exception("ElSpanero_ERROR :: Attachments folder won't exist...");
			exit;
		}
	}
	public function Script_CreateCharges(){
		$config_file = json_decode(file_get_contents('config.json'));
		$charge_package_total = (int)$config_file->email_perCharge;
		$email_list = json_decode($this->Script_GetEmailList(), true);
		$total_list = count($email_list);
		if($total_list < $charge_package_total){
			//this prevent list has less emails than charge set to send, if this happens charge will sent all at once.
			$charge_package_total = $total_list;
		}
		$number_ofLists = ceil($total_list / $charge_package_total);
		if($number_ofLists <= 0){
			$number_ofLists = 1;
		}
		$last_mail = 1;
		for($list = 1; $list <= $number_ofLists; $list++){
			$charge_file = fopen("_charges/mail_charge_00".$list.'.json', "w") or die ("ElSpanero_ERROR :: Unable to create charge file, check for permissions on '_charges' directory...");
			for($i = 1; $i <= $charge_package_total; $i++){
				if(array_key_exists($last_mail, $email_list) == false){
					$mail_list[$i]['email'] = null;
					break;
				}else{
					$mail_list[$i]['email'] = $email_list[$last_mail]['email'];
					$last_mail++;
				}
			}
			fwrite($charge_file, json_encode($mail_list, JSON_PRETTY_PRINT));
		}
		return "ElSpanero_SUCCESS :: Charges has been created!";
	}
	public function Script_SetCharge($script_id){
		$config_file = json_decode(file_get_contents('config.json'));
		if(!file_exists('_charges/mail_charge_00'.$script_id.'.json')){
			return throw new Exception("ElSpanero_ERROR :: Charge ID not found on '_charges' folder...");
			exit;
		}
		$charge = json_decode(file_get_contents('_charges/mail_charge_00'.$script_id.'.json'), true);
		$send_limit = $config_file->send_limit;
		$count = 0;
		$all_count = 0;
		$charge_separator = 1;
		$charge_json = array();
		$mail_list = array();
		foreach($charge as $key => $value){
			if($count == $config_file->send_limit){
				$count = 0;
				$charge_json['mail_account_'.$charge_separator] = $mail_list;
				$mail_list = array();
				$charge_separator++;
			}
			array_push($mail_list, $value['email']);
			$count++;
			$all_count++;
			if($all_count >= $key){
				$charge_json['mail_account_'.$charge_separator] = $mail_list;
			}
		}
		//header('Content-Type: application/json'); //DEBUG//
		return json_encode($charge_json, JSON_PRETTY_PRINT);
	}
	public function MakeRainBullets($charge_id = null){
		//well.. like it says, this activate the script!
		$config_file = json_decode(file_get_contents('config.json'));
		if($config_file->is_new == true){
			$config_file->is_new = false;
			$config_file->last_send_package_ts = 0;
			$config_file->next_send_package_ts = 0;
			$config_file->last_send = 0;
		}
		if($charge_id == null){
			$charge_id = ($config_file->last_send + 1);
		}
		//alow process start
		if($config_file->last_send == 0 || $time() >= $config_file->next_send_package_ts){
			$email_accounts = json_decode($this->Script_GetEmailAccounts(), true);
			$total_accounts = count($email_accounts);
			$mail_charge = json_decode($this->Script_SetCharge($charge_id), true);
			$mail_sent_count = 0;
			
			foreach($email_accounts as $key_email => $value_email){
				$mail = new PHPMailer(true);
				$username = $value_email['email'];
				$password = $value_email['password'];
				try{
					//
					$mail->SMTPDebug = 0;
					$mail->isSMTP();
					$mail->Host = "smtp.gmail.com";
					$mail->Username = $username;
					$mail->password = $password;
					$mail->SMTPSecure = "ssl";
					$mail->Port = 465;
					//Mail Sender
					$mail->setFrom($username, $config_file->sender_name);
					//Mail List
					$mail->addAddress($username, $config_file->sender_name);
					foreach($mail_charge as $key_list => $value_list){
						for($i = 0; $i < $config_file->send_limit; $i++){

							if(!array_key_exists('mail_account_'.$key_email, $mail_charge)){
								break;
							}else{
								if(array_key_exists($i, $mail_charge['mail_account_'.$key_email])){
									if($mail_charge['mail_account_'.$key_email][$i] != null){
										$mail->addBCC($mail_charge['mail_account_'.$key_email][$i]);
									}
								}
							}
						}
					}
					//Attachment
					$email_attachments = json_decode($this->Script_GetAttachments(), true);
					$attachments_count = count($email_attachments);
					if($attachments_count > 0){
						foreach($email_attachments as $attachment){
							$mail->addAttachment($attachment);
						}
					}
					//Content
					$mail->isHTML(true);
					$mail->CharSet = 'UTF-8';
					$mail->Subject = $config_file->mail_subject;
					$mail->Body = $this->Script_GetHTMLEmailTemplate();
					$mail->AltBody = $this->Script_GetTxtEmailTemplate();
					//
					if($mail->send()){
						echo '<br><hr>ElSpanero_SUCCESS :: Email charge sent!<hr><br>';
					}

				}catch (Exception $e){
					return "ElSpanero_ERROR :: Error on send E-mail | Error: ".$mail->ErrorInfo;
				}
				$mail_sent_count++;
				if($mail_sent_count >= $total_accounts){
					break;
				}
			}
			$config_file->last_send = ($config_file->last_send + 1);
			$config_file->last_send_package_ts = time();
			$config_file->next_send_package_ts = time() + 3600; //1 hour delay
			file_put_contents('config.json', json_encode($config_file));
			exit;
		}
	}
}

//              /!\ TEST AREA /!\              //
$script = new ElSpanero_Class();
$script->Script_CreateCharges(); //Create E-mail charge from 'mail_toSend.json' mail list.
echo $script->MakeRainBullets(); //Run Script
//          /!\ END OF TEST AREA /!\          //