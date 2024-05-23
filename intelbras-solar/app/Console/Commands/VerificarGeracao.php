<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class VerificarGeracao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intelbras:verificar-geracao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar a quantidade de energia gerada por estação e envia por mensagem via Telegram';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function status(string $status)
    {
        $_status = [
            '1' => 'Normal',
            '-1' => 'Desconectado',
            '3' => 'Falha',
            '4' => 'Desligado',
        ];

        return $_status[$status] ?? 'Desconhecido';
    }

    public function handle()
    {

        // Configure o cliente Guzzle HTTP
        $client = new Client([
            'base_uri' => 'http://supervisor/core/api/',
            'headers' => [
                'Authorization' => 'Bearer ' . env('SUPERVISOR_TOKEN'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Verifique se o sensor já existe
        try {
            $response = $client->request('GET', 'states/sensor.intelbras_solar');

            // Verifique se o sensor já existe
            if ($response->getStatusCode() === 200) {
                // O sensor já existe, não é necessário criar
                echo "O sensor 'sensor.intelbras_solar' já existe. Não é necessário criar novamente.\n";
            }
        } catch (Exception $e) {
            // O sensor não existe, vamos criá-lo
            echo "O sensor 'sensor.intelbras_solar' não existe. Criando...\n";

            // Defina os dados para criar o sensor
            $dadosSensor = [
                'state' => 25, // Valor inicial do sensor
                'attributes' => [
                    'friendly_name' => 'Intelbras Solar', // Nome amigável do sensor
                    'unit_of_measurement' => '°C', // Unidade de medida
                ],
            ];

            // Faça uma solicitação POST para criar o sensor
            $response = $client->request('POST', 'states/sensor.intelbras_solar', [
                'json' => $dadosSensor,
            ]);

            // Verifique se a criação do sensor foi bem-sucedida
            if ($response->getStatusCode() === 201) {
                echo "O sensor 'sensor.intelbras_solar' foi criado com sucesso!\n";
            } else {
                echo "Erro ao criar o sensor 'sensor.intelbras_solar'\n";
            }
        }

        // $telegramChatIds = config('telegram.chat_ids');

        // $client = new Client(array(
        //     'cookies' => true
        // ));

        // $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/login', [
        //     'timeout' => 30,
        //     'form_params' => [
        //         'account' => config('intelbras.user'),
        //         'password' => config('intelbras.password'),
        //         'validateCode' => '',
        //         'lang' => 'en'
        //     ]
        // ]);

        // // Geradores
        // $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/panel/getDevicesByPlantList', [
        //     'form_params' => [
        //         'plantId' => config('intelbras.plant_id'),
        //         'currPage' => '1',
        //     ]
        // ]);

        // $retorno = json_decode($response->getBody(), true);

        // if (isset($retorno['result']) && $retorno['result'] == '1') {

        //     $message = null;
        //     $total = 0;
        //     $i = 1;
        //     foreach ($retorno['obj']['datas'] as $estacao) {
        //         $message .= "⚡*Gerador {$i} ({$estacao['alias']})*:\n" .
        //             "Energia gerada: {$estacao['eToday']}kWh\n" .
        //             "Potência atual: {$estacao['pac']}W\n" .
        //             "Status: {$this->status($estacao['status'])}\n\n";

        //         $total += floatval($estacao['eToday']);
        //         $i++;
        //     }

        //     $message .= "\n🔋*Total:* {$total}kWh";

        //     foreach ($telegramChatIds as $chatId) {
        //         Telegram::sendMessage([
        //             'chat_id' => $chatId,
        //             'text' => $message,
        //             'parse_mode' => 'Markdown',
        //         ]);
        //     }

        //     $this->info(str_replace('*', ' ', $message));
        // } else {
        //     $this->error('Erro ao verificar a energia gerada');
        // }
    }
}
