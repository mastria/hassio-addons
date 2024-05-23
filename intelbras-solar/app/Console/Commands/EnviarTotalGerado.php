<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class EnviarTotalGerado extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intelbras:total-gerado';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica a quantidade total de energia gerada no dia e envia por mensagem via Telegram';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $telegramChatIds = config('telegram.chat_ids');

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

        //Totais
        $response = $client->request('POST', 'http://solar-monitoramento.intelbras.com.br/panel/intelbras/getInvTotalData?plantId=55126');

        $retorno = json_decode($response->getBody(), true);

        if (isset($retorno['result']) && $retorno['result'] == '1') {
            foreach ($telegramChatIds as $chatId) {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => "☀️ Energia total gerada hoje: *{$retorno['obj']['eToday']}kWh*",
                    'parse_mode' => 'Markdown',
                ]);
            }

            $this->info('☀️ Energia total gerada hoje: ' . $retorno['obj']['eToday'] . 'kWh');
        } else {
            $this->error('Erro ao verificar a energia gerada');
        }
    }
}
