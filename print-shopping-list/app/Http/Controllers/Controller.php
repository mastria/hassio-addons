<?php

namespace App\Http\Controllers;

use Aws\AlexaForBusiness\AlexaForBusinessClient;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\Sts\StsClient;
use Illuminate\Routing\Controller as BaseController;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psy\Readline\Hoa\Console;

class Controller extends BaseController
{

    public function index()
    {
        // Parâmetros da autenticação
        $clientId = '';
        $clientSecret = '';
        $redirectUri = 'http://localhost:8000/';
        $scope = 'alexa:lists_read'; // Escopo da API da Alexa que você deseja acessar

        // Parâmetros da solicitação de token
       // $authCode = $_GET['code']; // Código retornado pela Amazon após a autorização

        // Configuração do cliente AWS STS para obter o token
        $stsClient = new StsClient([
            'version' => 'latest',
            'region' => 'us-east-1', // Defina a região conforme necessário
            'credentials' => new Credentials($clientId, $clientSecret)
        ]);

        dd($stsClient->getCallerIdentity());

        // Troca o código de autorização por um token de acesso
        try {
            $result = $stsClient->assumeRoleWithWebIdentity([
                'RoleArn' => 'arn:aws:iam::<YOUR_AWS_ACCOUNT_ID>:role/<YOUR_ROLE_NAME>',
                'RoleSessionName' => 'AssumedRoleSession',
                'WebIdentityToken' => $authCode,
                'ProviderId' => 'www.amazon.com',
            ]);

            // Obtém o accessToken a partir da resposta
            $accessToken = $result['Credentials']['AccessToken']; // Use AccessToken para autenticação

            // Configuração do cliente AWS AlexaForBusiness para acessar listas da Alexa
            $alexaClient = new AlexaForBusinessClient([
                'version' => 'latest',
                'region' => 'us-east-1', // Defina a região conforme necessário
                'credentials' => new Credentials($result['Credentials']['AccessKeyId'], $result['Credentials']['SecretAccessKey'], $result['Credentials']['SessionToken'])
            ]);

            // Consulta e lista as listas da Alexa
            $listsResult = $alexaClient->getListMetadata([
                'ProfileArn' => 'arn:aws:alexa:us-east-1:<YOUR_AWS_ACCOUNT_ID>:profile/default', // Perfil Alexa padrão
                'MaxResults' => 10, // Limite máximo de listas a serem retornadas
            ]);

            // Exibir as listas da Alexa
            echo "Listas da Alexa:\n";
            foreach ($listsResult['Lists'] as $list) {
                echo "- " . $list['ListName'] . "\n";
            }
        } catch (\Aws\Exception\AwsException $e) {
            echo "Erro ao obter o token ou acessar as listas da Alexa: " . $e->getMessage();
        }
        // $dompdf = new Dompdf();
        // $options = $dompdf->getOptions();
        // $options->setChroot([
        //     storage_path('fonts'),
        // ]);
        // $dompdf->setOptions($options);
        // $dompdf->loadHtml(view('shopping-list')->render());

        // // (Optional) Setup the paper size and orientation


        // // Render the HTML as PDF
        // $dompdf->render();
        // //$dompdf->stream("dompdf_out.pdf", array("Attachment" => false));
        // Storage::put('shopping-list.pdf', $dompdf->output());

        // // Print
        // if (config('app.printer_ip') == '' || is_null(config('app.printer_ip'))) {
        //     Log::warning('Printer IP not set');
        //     return response()->json(['message' => 'Printer IP not set'], 400);
        // }

        // Log::info('Printing in ' . config('app.printer_ip') . ':' . config('app.printer_port') . '...');

        // //$fp = pfsockopen(config('app.printer_ip'), config('app.printer_port'));
        // //fputs($fp, storage_path('app/shopping-list.pdf'));
        // //fputs($fp, file_get_contents(storage_path('app/shopping-list.pdf')), filesize(storage_path('app/shopping-list.pdf')));
        // //fclose($fp);
    }
}
