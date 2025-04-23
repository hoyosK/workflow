<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PhpOffice\PhpWord\IOFactory;
use app\core\Response;
function responseErrorHttpCodeForCore($status = 200, $errorCode = '', $msg = '') {
    return [
        'status' => 0,
        'error' => $errorCode,
        'msg' => $msg,
    ];
}

class ACSEL_WS {

    private $url;
    public $from;
    public $rawResponse;

    private $urlAuth;
    private $payloadAuth;

    function __construct($request = false, $gestiones = false) {

        $from = false;

        /*define('ACSEL_USER', 'eperez@abkbusiness.com');
        define('ACSEL_PASS', '14458846');*/

        if ($request){
            $from = $request->header('x-from-app');
        }
        $this->from = ($from) ? $from : 'app';
        if(!$gestiones){
            $this->url = getenv('URL_ACSEL');
        }
        else{
            $this->url = getenv('URL_COTIZADOR');
        }

    }

    public function setAuthData($authUrl, $authPayload) {
        $this->urlAuth = $authUrl;
        $this->payloadAuth = $authPayload;
    }

    public function validateToken($tokenJWT) {
        $url = "{$this->url}/session/check_token";

        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $tokenJWT,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_USERPWD, "sam:oR1wT2cA");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        /*curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'token' => $tokenJWT
        ]);*/
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING , "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $response;
    }

    public function makeLogin() {

        $endPoint = $this->urlAuth;

        /*$data = array(
            'usuario' => $user,
            'contrasenia' => $password,
            'origen' => 'services'
        );*/

        $data = $this->payloadAuth;

        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json'
        );

        $headersReceived = '';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endPoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING , "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // voy a traer las cookies
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headersReceived) {
            $len = strlen($header);
            $headersReceived .= $header;
            return $len;
        }
        );

        $response = curl_exec($ch);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headersReceived, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        $response = json_decode($response, true);
        curl_close($ch);

        if (!isset($response['status'])) {
            return false;
        }

        $response['cookie'] = $cookies['JSESSIONID'] ?? false;

        return $response;
    }

    public function tokenJWT() {
        $token = $this->makeLogin();
        if (!empty($token['data']['token'])) {
            return $token;
        }
    }

    public function get($endpoint, $header, $isXML = false) {
        $token = $this->tokenJWT();

        if (!$token) {
            $this->rawResponse = 'Error en autenticación';
            return false;
        }


        if (!empty($token['data']['token'])) {
            $endpoint = "{$endpoint}";

            $headers = array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token['data']['token'],
                'Cache-Control: no-cache',
                'Postman-Token: e7996cd9-e6ed-4f96-81b3-2191e913eac2',
                'Host: agentes.elroble.com',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Cookie: JSESSIONID='.$token['cookie'],
            );
            if(is_array($header) && !$isXML){
                $headers = [];
                foreach ($header as $key => $value) {
                    $headers[] = "$key: $value";
                }
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            //$streamVerboseHandle = fopen('php://temp', 'w+');
            //curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);


            curl_setopt($ch, CURLOPT_URL, $endpoint);
            //curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_ENCODING , "");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response = @json_decode($response, true);
            $response['httpcode'] = $httpcode;



            curl_close($ch);
            return $response['data']??[];
        }
        else {
            responseErrorHttpCodeForCore('200', 'BMWS-ZCRRGXBZAX', 'Error en autenticación');
        }
    }

    public function post($endpoint, $content, $header = false, $responseXML = false) {

        //$timingStart = microtime(true);

        $token = $this->tokenJWT();

        // var_dump('Tiempo en iniciar sesión: ' . microtime(true) - $timingStart);

        if (!$token) {
            $this->rawResponse = 'Error en autenticación';
            return false;
        }

        $contentR = json_encode($content);

        //var_dump($contentR);
        if (is_string($content)) {

            $cleanedJson = preg_replace('/>\s+</', '><', $content);
            // Eliminar espacios en blanco y saltos de línea entre las etiquetas XML
            //$cleanedJson = preg_replace('/>\s+</', '><', $contentR);

            // Decodificar el JSON
            $decodedJson = json_decode($cleanedJson, true);


            //$content = json_decode($content,true);
            $dataArray = $decodedJson;
            //var_dump($decodedJson);
        }
        else{
            $content = $contentR;
            $dataArray = json_decode($content, true);

        }

        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token['data']['token'],
            'Cache-Control: no-cache',
            'Postman-Token: e7996cd9-e6ed-4f96-81b3-2191e913eac2',
            'Host: agentes.elroble.com',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
        );

       //  dd($token['data']['token']);
        if (!empty($token['data']['token']) && $responseXML) {

            $quitarSaltosDeLinea = function (&$array) use (&$quitarSaltosDeLinea) {
                if (is_array($array)) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            $quitarSaltosDeLinea($value); // Llamada recursiva para arrays anidados
                        } elseif (is_string($value)) {
                            // Eliminamos los saltos de línea solo si el valor es una cadena
                            $value = str_replace(["\n", "\r"], "", $value);
                        }
                    }
                }
            };

            $quitarSaltosDeLinea($dataArray);
            $content = @json_encode($dataArray);
        }
        else if(empty($token['data']['token'])){
            responseErrorHttpCodeForCore('200', 'BMWS-2UMBPHERUX', 'Error en autenticación');
        }

        //dd($headers);

        /*if(is_array($header) && !$responseXML){

            foreach ($header as $key => $value) {
                $headers[] = "$key: $value";
            }
        }*/

        //dd($headers);

        /*var_dump($endpoint);
        var_dump($headers);
        var_dump($content);
        die('asdfasdf');*/

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING , "");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $arrInfo = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //$arrInfo = curl_exec($ch);
        // voy a traer las cookies
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headersReceived) {
            $len = strlen($header);
            $headersReceived .= $header;
            return $len;
        });

        // var_dump($arrInfo);
        //var_dump('Tiempo en ejecutar servicio: ' . microtime(true) - $timingStart);

        // $arrInfo = curl_exec($ch);

        // $arrInfo = '<ORDEN><IDORDEN>154380</IDORDEN><CODPROD>360M</CODPROD><NUMPOL>288</NUMPOL><FECINI>10/08/2023</FECINI><FECFIN>10/08/2024</FECFIN><FECING>10/08/2023</FECING></ORDEN><ASEGURADOS><ASEGURADO><CODCLI>00000000000124579865</CODCLI><PARENTESCO>0001</PARENTESCO><NOMBRES>ana lucia viera</NOMBRES><APELLIDO_PATERNO>analucia viera</APELLIDO_PATERNO><APELLIDO_MATERNO>ana lucia viera</APELLIDO_MATERNO><SEXO>M</SEXO><FecNac>13/02/1997</FecNac><CARNE>502-885817</CARNE><OBS>Ok</OBS></ASEGURADO><ASEGURADO><CODCLI>00000000000124580009</CODCLI><PARENTESCO>0002</PARENTESCO><NOMBRES>conyuguE ana lucia viera</NOMBRES><APELLIDO_PATERNO>conyugueana lucia viera</APELLIDO_PATERNO><APELLIDO_MATERNO>conyugueana lucia viera</APELLIDO_MATERNO><APELLIDO_CASADA>conyugueana lucia viera</APELLIDO_CASADA><SEXO>F</SEXO><FecNac>13/02/1997</FecNac><CARNE>502-885818</CARNE><OBS>Ok</OBS></ASEGURADO><ASEGURADO><CODCLI>00000000000124580010</CODCLI><PARENTESCO>0006</PARENTESCO><NOMBRES>dependienteana lucia viera</NOMBRES><APELLIDO_PATERNO>dependienteana lucia viera</APELLIDO_PATERNO><APELLIDO_MATERNO>dependienteana lucia viera</APELLIDO_MATERNO><SEXO>M</SEXO><FecNac>13/02/2008</FecNac><CARNE>502-885819</CARNE><OBS>Ok</OBS></ASEGURADO></ASEGURADOS><DESC_ERR>OK</DESC_ERR>';

        //dd($arrInfo);
        $this->rawResponse = (!empty($arrInfo)) ? (string) $arrInfo : 'El servicio devolvió una cadena vacía ""';

        //dd($arrInfo);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headersReceived, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        curl_close($ch);
        $array = [];

        //dd($arrInfo);
        if($responseXML){

            //Chapuz para unificar el xml
            /*$xmlStringModified = '<ROOT>' . PHP_EOL . $arrInfo . PHP_EOL . '</ROOT>';
            $xmlStringModified = preg_replace('/<\?xml.*\?>/', '', $xmlStringModified);*/
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($arrInfo);

            if (!$xml) {

                $arrInfo = "<RESPUESTA>{$arrInfo}</RESPUESTA>";
                $xml = simplexml_load_string($arrInfo);

                if (!$xml) {
                    libxml_clear_errors();
                    return false;
                }
            }

            // Chapus para convertir en json
            $json = json_encode($xml);
            $array = json_decode($json,true);
            // $array['cookie'] = $cookies['JSESSIONID'] ?? false;
        }
        else{
            $array = json_decode($arrInfo, true);
            // $array['cookie'] = $cookies['JSESSIONID'] ?? false;
        }

        /*var_dump('Tiempo en parsear xml: ' . microtime(true) - $timingStart);
        die();*/

        $array['httpcode'] = $httpcode ?? '';

        return $array;


    }

    public function put($endpoint, $request, $content) {
        $token = $this->tokenJWT();

        if ($token) {
            $appContent = $content == "json" ? "application/json" : "multipart/form-data";
            $headers = array(
                'Authorization: bearer ' . $token['data']['token'],
                'Content-Type: ' . $appContent
            );
            //dd($headers);
            //dd($request);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            // curl_setopt($ch, CURLOPT_PUT, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_ENCODING , "");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $arrInfo = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            //dd($arrInfo);exit;
            $arrInfo = @json_decode($arrInfo, true);
            curl_close($ch);

            $arrInfo['httpcode'] = $httpcode;

            return $arrInfo;
        }
        else {
            responseErrorHttpCodeForCore('200', 'BMWS-CJMQ3UTFWC', 'Error en autenticación');
        }
    }

    public function delete($endpoint) {
        $token = $this->tokenJWT();

        if ($token) {
//        $appContent = $content == "json" ? "application/json" : "multipart/form-data";
            $headers = array(
                'Authorization: bearer ' . $token['data']['token'],
//            'Content-Type: '.$appContent
            );
            //dd($headers);
            //dd($request);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            // curl_setopt($ch, CURLOPT_PUT, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_ENCODING , "");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $arrInfo = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //dd($arrInfo);exit;
            $arrInfo = @json_decode($arrInfo, true);
            curl_close($ch);

            $arrInfo['httpcode'] = $httpcode;

            return $arrInfo;
        }
        else {
            responseErrorHttpCodeForCore('200', 'BMWS-IPYHGNLA2D', 'Error en autenticación');
        }
    }

    public function getUrl() {
        return $this->url;
    }

    public function parseXml($data) {

        $data = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $data);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($data);
        //dd(libxml_get_errors());

        if (!$xml) {

            $arrInfo = "<RESPUESTA>{$data}</RESPUESTA>";
            $xml = simplexml_load_string($data);

            if (!$xml) {
                libxml_clear_errors();
                return false;
            }
            else {
                return $xml;
            }
        }
        else {
            return $xml;
        }
    }
}
