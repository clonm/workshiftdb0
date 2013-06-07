<?php
ini_set('display_errors',1);
$php_includes = '/home/bsc1933/public_html/cvsworkshift/workshiftdb0/php_includes/';
require_once "$php_includes/includes/google-api-php-client/src/Google_Client.php";
require_once "$php_includes/includes/google-api-php-client/src/contrib/Google_DriveService.php";

class UploadWorkshiftBackup {
  
  protected $client;
  protected $service;
  protected $house_id;

  public function __construct() {
    $this->client = new Google_Client();
    // Get your credentials from the APIs Console
    $this->client->setClientId('302399642910.apps.googleusercontent.com'); 
    // add your client id and secret(you got it when you created your account)
    $this->client->setClientSecret('zxQ0RxsuWRMh6ipsr1RnxYPc');
    $this->client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
    //This is standard Uri for installed applications,may change in web applications. 
    $this->client->setScopes(array('https://www.googleapis.com/auth/drive'));
    $this->service = new Google_DriveService($this->client);
    $this->client->setAccessToken($this->get_access_token());
    $this->house_id = array();
    $this->house_id['co'] = '0B6lOwIuS9NCtY0o2Z1BVdVN0VXM';    
    $this->house_id['wol'] = '0B6lOwIuS9NCtLUpWYXFMenRPSkU';
    $this->house_id['wil'] = '0B6lOwIuS9NCtb0k4WEVrTTlnelk';
    $this->house_id['stb'] = '0B6lOwIuS9NCtLWE0V0pPLTJudUU';
    $this->house_id['she'] = '0B6lOwIuS9NCtX2JBZmxFQzZvajA';
    $this->house_id['roc'] = '0B6lOwIuS9NCtTHZaMjY3cmhFT2s';
    $this->house_id['rid'] = '0B6lOwIuS9NCtblJpUnVqVDRCRG8';
    $this->house_id['nsc'] = '0B6lOwIuS9NCtSnMxSjRfN1JyQmM';
    $this->house_id['lot'] = '0B6lOwIuS9NCtZ1JVLUFiTElKRUE';
    $this->house_id['kng'] = '0B6lOwIuS9NCta08xMVRjeWRtTk0';
    $this->house_id['kid'] = '0B6lOwIuS9NCtT3lOWGVfWUo0Z2s';
    $this->house_id['hoy'] = '0B6lOwIuS9NCtWHVFb3FEbnc5NzA';
    $this->house_id['hip'] = '0B6lOwIuS9NCtU2Y2X0FVZWFFMzA';
    $this->house_id['fen'] = '0B6lOwIuS9NCtVnZ5R2JhQmtRdEE';
    $this->house_id['euc'] = '0B6lOwIuS9NCtVVVGUlR0WWFZaDA';
    $this->house_id['dav'] = '0B6lOwIuS9NCtWjViRU5mdzF0ZDA';
    $this->house_id['con'] = '0B6lOwIuS9NCtd2htUC0wdFdNaVE';
    $this->house_id['clo'] = '0B6lOwIuS9NCtZmY3VVM2RFA4bUU';
    $this->house_id['caz'] = '0B6lOwIuS9NCta1lkazN6dW9QNnc';
    $this->house_id['ath'] = '0B6lOwIuS9NCtUFNiZDFjWTJQcTA';
    $this->house_id['aca'] = '0B6lOwIuS9NCtNEhNbXNoR0JFYVk';
  }

  protected function get_house_id($house) {
	return $this->house_id[$house];
  }
  
  public function upload($uploadfile, $house) {
    $file = new Google_DriveFile();
    $filename = basename($uploadfile);
    $file->setTitle($filename);
    $file->setDescription('backup');
    $file->setMimeType('application/zip');
    $parent = new Google_ParentReference();
    $parent->setId($this->get_house_id($house));
    $file->setParents(array($parent));
    $data = file_get_contents($uploadfile);
    $createdFile = $this->service->files->insert($file, array(
          'data' => $data,
          'mimeType' => 'application/zip',
          ));
  }

  protected function get_access_token() {
    $pass_name = 'google_drive_access.txt';
    $access_token = null;
    if (file_exists($pass_name)) {
      $passfile = fopen($pass_name,'r');
      $token = rtrim(fgets($passfile));
      fclose($passfile);
      return $token;
    } else {
      $authUrl = $this->client->createAuthUrl();
      //Request authorization
      print "Administrator: As a one-time operation, run this in " .
      "a shell prompt, and then please visit:\n$authUrl\n\n";
      print "Please enter the auth code, and :\n";
      $authCode = trim(fgets(STDIN));
      // Exchange authorization code for access token
      $access_token = $this->client->authenticate($authCode);
      $passfile = fopen($pass_name,'w');
      fputs($passfile,$access_token);
      fclose($passfile);
      return $access_token;
    }
  }
}

?>

