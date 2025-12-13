<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class EnviarResumo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:enviar-resumo';

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
        // Verificar se o Telegram está configurado
        $telegramBotToken = config('telegram.bots.mybot.token');

        if (empty($telegramBotToken) || $telegramBotToken === 'YOUR-BOT-TOKEN') {
            $this->warn('TELEGRAM_BOT_TOKEN não configurado. Envio de mensagens desabilitado.');
            return Command::SUCCESS;
        }

        $telegramChatIds = config('telegram.chat_ids');

        if (empty($telegramChatIds) || (count($telegramChatIds) === 1 && empty($telegramChatIds[0]))) {
            $this->warn('TELEGRAM_CHAT_IDS não configurado. Nenhum destinatário para enviar mensagens.');
            return Command::SUCCESS;
        }

        $client = new Client(array(
            'cookies' => true
        ));

        $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/login', [
            'timeout' => 30,
            'form_params' => [
                'account' => config('intelbras.user'),
                'password' => config('intelbras.password'),
                'validateCode' => '',
                'lang' => 'en'
            ]
        ]);

        // Plantas
        $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/index/getPlantListTitle');

        $plantas = json_decode($response->getBody(), true);

        if (!is_array($plantas) || count($plantas) == 0) {
            throw new Exception('Erro ao verificar as plantas');
        }

        // Inversores
        $message = null;
        $total = 0;
        $i = 1;
        foreach ($plantas as $planta) {
            $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/panel/getDevicesByPlantList', [
                'form_params' => [
                    'plantId' => $planta['id'],
                    'currPage' => '1',
                ]
            ]);

            $retorno = json_decode($response->getBody(), true);

            if (isset($retorno['result']) && $retorno['result'] == '1') {
                foreach ($retorno['obj']['datas'] as $estacao) {
                    $message .= "⚡*Inversor {$i} ({$estacao['alias']})*:\n" .
                        ((isset($planta['plantName']) && $planta['plantName']) ? "Planta: {$planta['plantName']}\n" : "") .
                        "Energia gerada: {$estacao['eToday']}kWh\n" .
                        "Potência atual: {$estacao['pac']}W\n" .
                        "Status: {$this->status($estacao['status'])}\n\n";

                    $total += floatval($estacao['eToday']);
                    $i++;
                }
            } else {
                throw new Exception('Erro ao verificar a energia da planta ' . $planta['name']);
            }
        }

        $message .= "\n🔋*Total:* {$total}kWh";

        foreach ($telegramChatIds as $chatId) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        }

        $this->info(str_replace('*', ' ', $message));
    }
}
